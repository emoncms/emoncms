<?php
/**
 * PHPUnit bootstrap for Unit tests.
 *
 * Sets up the constants, globals, and helper functions that the emoncms
 * classes expect so they can be loaded without a full web-request context.
 */

// Emoncms guards every file with this check.
define('EMONCMS_EXEC', 1);

// Ensure the project root is the working directory so relative requires
// inside model files resolve correctly (e.g. "Modules/user/rememberme_model.php").
chdir(dirname(__DIR__, 2));

// Minimal global $settings that the User and EmonLogger constructors read.
$GLOBALS['settings'] = [
    'interface' => [
        'enable_rememberme'  => false,
        'email_verification' => false,
        'appname'            => 'emoncms',
        'enable_password_reset' => false,
        'default_language'   => 'en',
    ],
    'log' => [
        'enabled'  => false,
        'location' => '/tmp',
        'level'    => 2,
    ],
    'ui_read_only_mode' => false,
];

// Helper functions used by user_model.php that normally live in core.php.
if (!function_exists('tr')) {
    function tr($text) { return $text; }
}
if (!function_exists('generate_secure_key')) {
    function generate_secure_key($length) {
        return bin2hex(random_bytes($length));
    }
}
if (!function_exists('guidv4')) {
    function guidv4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
if (!function_exists('get_client_ip_env')) {
    function get_client_ip_env() { return '127.0.0.1'; }
}
if (!function_exists('is_https')) {
    function is_https() { return false; }
}

// Load the classes needed by User's constructor.
require_once __DIR__ . '/../../Lib/EmonLogger.php';
require_once __DIR__ . '/../../Modules/user/rememberme_model.php';
require_once __DIR__ . '/../../Modules/user/user_model.php';
