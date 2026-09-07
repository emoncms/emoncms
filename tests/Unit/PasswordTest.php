<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Lib/password.php.
 *
 * The point of these is the migration: accounts written under the old
 * sha256 scheme have to keep verifying, and have to be recognised as
 * needing a rewrite, while nothing produced by the new code is ever
 * mistaken for a legacy hash.
 */
final class PasswordTest extends TestCase
{
    // --- hash_password() / verify_password() ---

    #[Test]
    public function hash_password_produces_a_bcrypt_hash(): void
    {
        $hash = hash_password('correcthorsebatterystaple');

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertLessThanOrEqual(255, strlen($hash), 'must fit users.password');
    }

    #[Test]
    public function hash_password_salts_each_hash_separately(): void
    {
        $this->assertNotSame(
            hash_password('samepassword'),
            hash_password('samepassword')
        );
    }

    #[Test]
    public function verify_password_accepts_the_password_it_hashed(): void
    {
        $this->assertTrue(verify_password('s3cret!', hash_password('s3cret!')));
    }

    #[Test]
    public function verify_password_rejects_a_different_password(): void
    {
        $this->assertFalse(verify_password('wrong', hash_password('s3cret!')));
    }

    #[Test]
    public function verify_password_rejects_an_empty_stored_hash(): void
    {
        // A row with no password set must not be loggable into with anything
        $this->assertFalse(verify_password('anything', ''));
        $this->assertFalse(verify_password('', ''));
    }

    #[Test]
    public function verify_password_keeps_every_character_significant_past_72_bytes(): void
    {
        // bcrypt truncates at 72 bytes, so without the prehash these two
        // would be the same password
        $base = str_repeat('a', 72);
        $hash = hash_password($base . 'X');

        $this->assertTrue(verify_password($base . 'X', $hash));
        $this->assertFalse(verify_password($base . 'Y', $hash));
    }

    // --- legacy sha256 format ---

    private function legacyHash(string $password, string $salt): string
    {
        return hash('sha256', $salt . hash('sha256', $password));
    }

    #[Test]
    public function verify_password_accepts_a_legacy_sha256_hash(): void
    {
        $salt = 'a1b2c3d4e5f6';

        $this->assertTrue(
            verify_password('oldpassword', $this->legacyHash('oldpassword', $salt), $salt)
        );
    }

    #[Test]
    public function verify_password_rejects_a_legacy_hash_with_the_wrong_salt(): void
    {
        $this->assertFalse(
            verify_password('oldpassword', $this->legacyHash('oldpassword', 'realsalt'), 'othersalt')
        );
    }

    #[Test]
    public function password_is_legacy_hash_recognises_a_sha256_digest(): void
    {
        $this->assertTrue(password_is_legacy_hash($this->legacyHash('x', 'y')));
    }

    #[Test]
    public function password_is_legacy_hash_rejects_a_bcrypt_hash(): void
    {
        $this->assertFalse(password_is_legacy_hash(hash_password('x')));
    }

    #[Test]
    public function password_is_legacy_hash_rejects_a_64_character_non_hex_string(): void
    {
        $this->assertFalse(password_is_legacy_hash(str_repeat('z', 64)));
    }

    // --- password_needs_upgrade() ---

    #[Test]
    public function password_needs_upgrade_is_true_for_a_legacy_hash(): void
    {
        $this->assertTrue(password_needs_upgrade($this->legacyHash('x', 'y')));
    }

    #[Test]
    public function password_needs_upgrade_is_false_for_a_freshly_written_hash(): void
    {
        $this->assertFalse(password_needs_upgrade(hash_password('x')));
    }

    #[Test]
    public function password_needs_upgrade_is_true_for_a_weaker_cost_than_configured(): void
    {
        // Written below the configured cost of 10, so a login should rewrite it
        $weak = password_hash(password_prehash('x'), PASSWORD_BCRYPT, ['cost' => 4]);

        $this->assertTrue(password_needs_upgrade($weak));
    }

    // --- password_hash_config() ---

    #[Test]
    public function password_hash_config_resolves_the_configured_algorithm(): void
    {
        $config = password_hash_config();

        $this->assertSame('bcrypt', $config['name']);
        $this->assertSame(PASSWORD_BCRYPT, $config['algo']);
        $this->assertSame(10, $config['options']['cost']);
    }
}
