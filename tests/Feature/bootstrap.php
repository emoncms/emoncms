<?php
/**
 * PHPUnit bootstrap for Feature tests.
 *
 * Feature tests send real HTTP requests to the running emoncms instance
 * and assert on the JSON responses.  They also connect to the database
 * directly so they can create and clean up test users without going
 * through the API.
 */

// Resolve the project root so require paths work from anywhere.
chdir(dirname(__DIR__, 2));

// Parse settings.ini for DB credentials and the base URL.
$ini = parse_ini_file('settings.ini', true);
$sqlConf = $ini['sql'] ?? [];

$dbHost = trim($sqlConf['server']   ?? 'localhost', '"\'');
$dbName = trim($sqlConf['database'] ?? 'emoncms',   '"\'');
$dbUser = trim($sqlConf['username'] ?? 'emoncms',   '"\'');
$dbPass = trim($sqlConf['password'] ?? '',           '"\'');

$GLOBALS['test_mysqli'] = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($GLOBALS['test_mysqli']->connect_errno) {
    throw new RuntimeException(
        "Feature test DB connection failed: " . $GLOBALS['test_mysqli']->connect_error
    );
}

// Base URL of the running emoncms instance – override with env var if needed.
$GLOBALS['feature_base_url'] = rtrim(
    getenv('EMONCMS_BASE_URL') ?: 'http://localhost/original',
    '/'
);

// Load the shared base test case so PHPUnit can find it before scanning test files.
require_once __DIR__ . '/ApiTestCase.php';
