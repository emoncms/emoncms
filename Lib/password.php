<?php
/*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

   ---------------------------------------------------------------------
   Emoncms - open source energy visualisation
   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// ---------------------------------------------------------------------------------------------------------
// Password hashing
//
// Passwords were stored as sha256(salt . sha256(password)): salted, but a single
// round of a hash designed to be fast. That is the wrong shape for a password:
// if the users table ever leaks, commodity hardware tests billions of candidates
// a second and a salt only prevents precomputation, not cracking. These helpers
// move storage to bcrypt or argon2id, whichever settings['password']['algo']
// selects: both are deliberately expensive and both carry their own salt.
//
// All three formats are readable side by side, so existing accounts keep working
// and the algorithm can be changed at any time without locking anyone out:
// password_verify() reads the algorithm and its parameters out of the stored
// hash itself, so what settings say only affects what gets WRITTEN. Each account
// is rewritten to the configured algorithm the next time its owner authenticates.
//
// The legacy format is told apart by the stored value, not by a flag or by the
// salt column: legacy is a 64 character hex digest, everything since is crypt
// format starting with "$". The two cannot be confused.
//
// Which algorithm to use is set in settings under 'password'. See
// password_hash_config() below for the options and the tradeoff.
// ---------------------------------------------------------------------------------------------------------

/**
 * Resolve the configured password hashing algorithm and its parameters.
 *
 * settings['password']['algo'] is either 'bcrypt' (the default) or 'argon2id'.
 *
 * argon2id is the stronger algorithm: its cost is memory as well as time, which
 * is what makes it expensive to attack with GPUs, where memory rather than
 * arithmetic is the scarce resource. Prefer it where you control the server and
 * can spare the RAM.
 *
 * bcrypt is the default because it is the one that is always safe to ship.
 * argon2 is only present if PHP was built with libargon2, which is not
 * guaranteed across emonPi images, Docker and shared hosting; its hashes are 97
 * characters against bcrypt's 60, so on an install running this code before the
 * schema update has widened users.password they would be truncated on write and
 * lock the account out; and its whole point is allocating tens of MiB per hash,
 * which on a Pi that is also running MySQL and feed processing is both slow and
 * a cheap way for an unauthenticated visitor to exhaust memory through the login
 * endpoint. Tune it down far enough to be comfortable there and it is weaker
 * than bcrypt anyway.
 *
 * Anything misconfigured falls back to bcrypt with a note in the error log,
 * rather than throwing: a bad setting must not be able to take logins down.
 *
 * The resolved value is cached for the request, which also keeps any fallback
 * warning to one line per request rather than one per password check.
 *
 * @return array {name: string, algo: string|int, options: array}
 */
function password_hash_config()
{
    global $settings;

    static $resolved = null;
    if ($resolved !== null) return $resolved;

    $conf = (isset($settings['password']) && is_array($settings['password'])) ? $settings['password'] : array();
    $algo = isset($conf['algo']) ? strtolower(trim($conf['algo'])) : 'bcrypt';

    if ($algo === 'argon2id') {
        if (defined('PASSWORD_ARGON2ID')) {
            $memory  = isset($conf['argon2_memory_cost']) ? (int) $conf['argon2_memory_cost'] : 65536;
            $time    = isset($conf['argon2_time_cost'])   ? (int) $conf['argon2_time_cost']   : 3;
            $threads = isset($conf['argon2_threads'])     ? (int) $conf['argon2_threads']     : 1;

            // Clamp rather than let a typo reach password_hash(), which throws
            // on out of range values. libargon2 needs at least 8 KiB per thread.
            if ($threads < 1) { error_log("emoncms: password argon2_threads must be at least 1, using 1"); $threads = 1; }
            if ($time < 1)    { error_log("emoncms: password argon2_time_cost must be at least 1, using 1"); $time = 1; }
            $min_memory = 8 * $threads;
            if ($memory < $min_memory) {
                error_log("emoncms: password argon2_memory_cost $memory KiB is below the minimum for $threads thread(s), using $min_memory KiB");
                $memory = $min_memory;
            }

            $resolved = array(
                'name' => 'argon2id',
                'algo' => PASSWORD_ARGON2ID,
                'options' => array(
                    'memory_cost' => $memory,
                    'time_cost'   => $time,
                    'threads'     => $threads
                )
            );
            return $resolved;
        }

        // Configured but the PHP build cannot do it. Existing argon2id hashes
        // written elsewhere still verify, this only affects what we write.
        error_log("emoncms: settings['password']['algo'] is argon2id but this PHP build has no argon2 support, falling back to bcrypt");

    } else if ($algo !== 'bcrypt') {
        error_log("emoncms: unknown settings['password']['algo'] '$algo', falling back to bcrypt");
    }

    // Pinned, not left to PHP's default, which has already moved from 10 to 12
    // and will keep drifting. A cost that changes when someone upgrades PHP
    // makes login times unpredictable across a mixed fleet, and 12 on a Pi is
    // an uncomfortably long wait.
    $cost = isset($conf['bcrypt_cost']) ? (int) $conf['bcrypt_cost'] : 10;
    if ($cost < 4 || $cost > 31) {
        error_log("emoncms: password bcrypt_cost $cost is out of range, using 10");
        $cost = 10;
    }

    $resolved = array(
        'name' => 'bcrypt',
        'algo' => PASSWORD_BCRYPT,
        'options' => array('cost' => $cost)
    );
    return $resolved;
}

// bcrypt ignores anything past 72 bytes and stops at the first null byte, while
// is_valid_password allows up to 250 characters. Feeding it a fixed length
// digest instead of the raw password keeps every character significant. base64
// rather than raw bytes so the value can never contain a null. argon2id has no
// such limit, but the prehash is applied to both: it has to stay for verifying
// hashes already written, and it costs nothing to keep it uniform.
function password_prehash($password)
{
    return base64_encode(hash('sha256', $password, true));
}

// True for the legacy format: sha256 hex digest, exactly 64 hex characters.
// Both bcrypt and argon2id produce crypt format starting with "$", so neither
// can be mistaken for it.
function password_is_legacy_hash($stored)
{
    return is_string($stored) && strlen($stored) === 64 && ctype_xdigit($stored);
}

function hash_password($password)
{
    $config = password_hash_config();
    return password_hash(password_prehash($password), $config['algo'], $config['options']);
}

// Verify against any of the three formats. The algorithm and its parameters come
// from the stored hash, not from settings, so changing the setting never locks
// anyone out and switching back and forth is safe. $salt is only read for legacy
// rows, where it was stored separately.
function verify_password($password, $stored, $salt = '')
{
    if (!is_string($stored) || $stored === '') return false;

    if (password_is_legacy_hash($stored)) {
        return hash_equals($stored, hash('sha256', $salt . hash('sha256', $password)));
    }

    return password_verify(password_prehash($password), $stored);
}

// True if the stored hash should be replaced after a successful verification:
// it is still in the legacy format, or it was written with a different algorithm
// or weaker parameters than settings now ask for. This is what makes changing
// the setting migrate every account as its owner next logs in.
function password_needs_upgrade($stored)
{
    if (password_is_legacy_hash($stored)) return true;

    $config = password_hash_config();
    return password_needs_rehash($stored, $config['algo'], $config['options']);
}
