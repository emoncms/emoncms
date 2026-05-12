<?php
/**
 * PHPUnit bootstrap for Integration tests.
 *
 * Connects to the real MySQL database so tests can exercise the full
 * round-trip behaviour of model methods (register, login, etc.).
 *
 * Database credentials are read from the project's settings.ini so the
 * bootstrap does not hard-code any secrets.
 */

define('EMONCMS_EXEC', 1);

// Ensure relative require paths inside model files resolve from the project root.
chdir(dirname(__DIR__, 2));

// Parse settings.ini for the SQL section.
$ini = parse_ini_file('settings.ini', true);
$sqlConf = $ini['sql'] ?? [];

$dbHost = trim($sqlConf['server']   ?? 'localhost', '"\'');
$dbName = trim($sqlConf['database'] ?? 'emoncms',   '"\'');
$dbUser = trim($sqlConf['username'] ?? 'emoncms',   '"\'');
$dbPass = trim($sqlConf['password'] ?? '',           '"\'');

// Create a shared mysqli connection available to all test cases.
$GLOBALS['test_mysqli'] = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($GLOBALS['test_mysqli']->connect_errno) {
    throw new RuntimeException(
        "Integration test DB connection failed: " . $GLOBALS['test_mysqli']->connect_error
    );
}

// Minimal $settings global that User's constructor reads.
$GLOBALS['settings'] = [
    'interface' => [
        'enable_rememberme'      => false,
        'email_verification'     => false,
        'appname'                => 'emoncms',
        'enable_password_reset'  => false,
        'default_language'       => 'en',
    ],
    'log' => [
        'enabled'  => false,
        'location' => '/tmp',
        'level'    => 2,
    ],
    'ui_read_only_mode' => false,
];

// Helper functions used by user_model.php that live in core.php.
if (!function_exists('tr')) {
    function tr($text) {
 return $text; }
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
    function get_client_ip_env() {
 return '127.0.0.1'; }
}
if (!function_exists('is_https')) {
    function is_https() {
 return false; }
}

require_once __DIR__ . '/../../Lib/EmonLogger.php';
require_once __DIR__ . '/../../Modules/user/rememberme_model.php';
require_once __DIR__ . '/../../Modules/user/user_model.php';
