<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the User model.
 *
 * These tests hit the real MySQL database so they verify the full
 * round-trip behaviour of register, login, password management, and
 * API-key lookup — things that cannot be tested with mocks alone.
 *
 * Every test works on users whose names start with the TEST_PREFIX
 * constant.  tearDownAfterClass() removes all of them so the database
 * is left clean after the suite runs.
 *
 * Run with:  php vendor/bin/phpunit --testsuite Integration
 */
class UserModelIntegrationTest extends TestCase
{
    /** Prefix applied to every test username to avoid collisions. */
    private const TEST_PREFIX = 'phpunittest';

    private static mysqli $mysqli;
    private User $user;

    // -------------------------------------------------------------------------
    // Suite-level setup / teardown
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        static::$mysqli = $GLOBALS['test_mysqli'];
    }

    public static function tearDownAfterClass(): void
    {
        // Remove every user whose username starts with the test prefix.
        $prefix = self::TEST_PREFIX . '%';
        $stmt = static::$mysqli->prepare("DELETE FROM users WHERE username LIKE ?");
        $stmt->bind_param("s", $prefix);
        $stmt->execute();
        $stmt->close();
    }

    // -------------------------------------------------------------------------
    // Per-test setup
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        // Fresh User instance for every test; no redis so rate-limiting is off.
        $this->user = new User(static::$mysqli, null);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns a unique username that fits within the model's 30-char limit.
     * The suffix is 8 hex characters (4 random bytes).
     */
    private function uniqueUsername(): string
    {
        return self::TEST_PREFIX . bin2hex(random_bytes(4));
    }

    /**
     * Registers a test user and asserts success, then returns the result array
     * so individual tests can inspect apikey_read / apikey_write.
     */
    private function registerTestUser(string $username, string $password = 'Correct1!'): array
    {
        $result = $this->user->register($username, $password, $username . '@example.test', 'UTC');
        $this->assertTrue($result['success'], "register() failed: " . ($result['message'] ?? ''));
        return $result;
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    /** @test */
    public function register_creates_user_in_database(): void
    {
        $username = $this->uniqueUsername();
        $result = $this->registerTestUser($username);

        $this->assertGreaterThan(0, $result['userid']);
        // get_id() must now return the same id.
        $this->assertSame((int) $result['userid'], (int) $this->user->get_id($username));
    }

    /** @test */
    public function register_returns_api_keys(): void
    {
        $username = $this->uniqueUsername();
        $result = $this->registerTestUser($username);

        $this->assertNotEmpty($result['apikey_read']);
        $this->assertNotEmpty($result['apikey_write']);
        // Keys must be 32-character hex strings.
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['apikey_read']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['apikey_write']);
    }

    /** @test */
    public function register_rejects_duplicate_username(): void
    {
        $username = $this->uniqueUsername();
        $this->registerTestUser($username);

        $second = $this->user->register($username, 'Another1!', $username . '_2@example.test', 'UTC');
        $this->assertFalse($second['success']);
        $this->assertStringContainsStringIgnoringCase('exists', $second['message']);
    }

    // -------------------------------------------------------------------------
    // login()
    // -------------------------------------------------------------------------

    /** @test */
    public function login_succeeds_with_correct_credentials(): void
    {
        $username = $this->uniqueUsername();
        $password = 'Correct1!';
        $this->registerTestUser($username, $password);

        $result = $this->user->login($username, $password, false);
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        $username = $this->uniqueUsername();
        $this->registerTestUser($username, 'Correct1!');

        $result = $this->user->login($username, 'WrongPassword1!', false);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('incorrect', $result['message']);
    }

    /** @test */
    public function login_fails_for_nonexistent_user(): void
    {
        $result = $this->user->login($this->uniqueUsername(), 'SomePass1!', false);
        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // change_password()
    // -------------------------------------------------------------------------

    /** @test */
    public function change_password_allows_login_with_new_password(): void
    {
        $username = $this->uniqueUsername();
        $oldPass  = 'OldPass1!';
        $newPass  = 'NewPass1!';
        $reg = $this->registerTestUser($username, $oldPass);

        $change = $this->user->change_password($reg['userid'], $oldPass, $newPass);
        $this->assertTrue($change['success'], $change['message'] ?? '');

        // Old password must no longer work.
        $oldLogin = $this->user->login($username, $oldPass, false);
        $this->assertFalse($oldLogin['success']);

        // New password must work.
        $newLogin = $this->user->login($username, $newPass, false);
        $this->assertTrue($newLogin['success']);
    }

    /** @test */
    public function change_password_fails_with_incorrect_old_password(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username, 'Correct1!');

        $result = $this->user->change_password($reg['userid'], 'WrongOld1!', 'NewPass1!');
        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // get_apikeys_from_login()
    // -------------------------------------------------------------------------

    /** @test */
    public function get_apikeys_from_login_returns_correct_keys(): void
    {
        $username = $this->uniqueUsername();
        $password = 'ApiTest1!';
        $reg = $this->registerTestUser($username, $password);

        $auth = $this->user->get_apikeys_from_login($username, $password);
        $this->assertTrue($auth['success']);
        $this->assertSame($reg['apikey_read'],  $auth['apikey_read']);
        $this->assertSame($reg['apikey_write'], $auth['apikey_write']);
    }

    /** @test */
    public function get_apikeys_from_login_fails_with_wrong_password(): void
    {
        $username = $this->uniqueUsername();
        $this->registerTestUser($username, 'Correct1!');

        $auth = $this->user->get_apikeys_from_login($username, 'Wrong1!');
        $this->assertFalse($auth['success']);
    }

    // -------------------------------------------------------------------------
    // apikey_session()
    // -------------------------------------------------------------------------

    /** @test */
    public function apikey_session_returns_write_session_for_write_key(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $session = $this->user->apikey_session($reg['apikey_write']);
        $this->assertSame((int) $reg['userid'], (int) $session['userid']);
        $this->assertSame(1, $session['write']);
        $this->assertSame(1, $session['read']);
    }

    /** @test */
    public function apikey_session_returns_read_only_session_for_read_key(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $session = $this->user->apikey_session($reg['apikey_read']);
        $this->assertSame((int) $reg['userid'], (int) $session['userid']);
        $this->assertSame(1, $session['read']);
        $this->assertSame(0, $session['write']);
    }

    /** @test */
    public function apikey_session_returns_empty_for_invalid_key(): void
    {
        // A well-formed but non-existent key.
        $fakeKey = str_repeat('a', 32);
        $session = $this->user->apikey_session($fakeKey);
        $this->assertEmpty($session);
    }

    // -------------------------------------------------------------------------
    // get_username() / get_email()
    // -------------------------------------------------------------------------

    /** @test */
    public function get_username_returns_registered_username(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $this->assertSame($username, $this->user->get_username($reg['userid']));
    }

    /** @test */
    public function get_email_returns_registered_email(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $this->assertSame($username . '@example.test', $this->user->get_email($reg['userid']));
    }

    // -------------------------------------------------------------------------
    // get_apikey_read() / get_apikey_write()
    // -------------------------------------------------------------------------

    /** @test */
    public function get_apikey_read_matches_registration_key(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $this->assertSame($reg['apikey_read'], $this->user->get_apikey_read($reg['userid']));
    }

    /** @test */
    public function get_apikey_write_matches_registration_key(): void
    {
        $username = $this->uniqueUsername();
        $reg = $this->registerTestUser($username);

        $this->assertSame($reg['apikey_write'], $this->user->get_apikey_write($reg['userid']));
    }
}
