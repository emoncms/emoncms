<?php

/**
 * Feature tests for the User JSON API.
 *
 * Each test makes real HTTP requests to the running emoncms instance and
 * asserts on the JSON response.  A fresh test user is registered (via the
 * API itself) at the start of each test and removed from the database
 * afterwards, so the suite is fully self-contained.
 *
 * Run with:  php vendor/bin/phpunit --configuration phpunit.feature.xml
 * Override the server URL:  EMONCMS_BASE_URL=http://myserver php vendor/bin/phpunit --configuration phpunit.feature.xml
 */
class UserApiTest extends ApiTestCase
{
    public static function tearDownAfterClass(): void
    {
        static::deleteTestUsers();
    }

    // -------------------------------------------------------------------------
    // POST /user/register.json
    // -------------------------------------------------------------------------

    /** @test */
    public function register_creates_a_new_user(): void
    {
        $username = $this->uniqueUsername();
        $result = $this->post('/user/register.json', [
            'username' => $username,
            'password' => 'TestPass1!',
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? json_encode($result));
        $this->assertGreaterThan(0, $result['userid'] ?? 0);
    }

    /** @test */
    public function register_rejects_duplicate_username(): void
    {
        $username = $this->uniqueUsername();
        $fields = [
            'username' => $username,
            'password' => 'TestPass1!',
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ];

        $first = $this->post('/user/register.json', $fields);
        $this->assertTrue($first['success'], 'First registration should succeed');

        $second = $this->post('/user/register.json', $fields);
        $this->assertFalse($second['success']);
        $this->assertStringContainsStringIgnoringCase('exists', $second['message']);
    }

    /** @test */
    public function register_rejects_invalid_email(): void
    {
        $username = $this->uniqueUsername();
        $result = $this->post('/user/register.json', [
            'username' => $username,
            'password' => 'TestPass1!',
            'email'    => 'notanemail',
            'timezone' => 'UTC',
        ]);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function register_rejects_short_password(): void
    {
        $username = $this->uniqueUsername();
        $result = $this->post('/user/register.json', [
            'username' => $username,
            'password' => 'abc',
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // POST /user/auth.json  (credential → API keys)
    // -------------------------------------------------------------------------

    /** @test */
    public function auth_returns_api_keys_for_valid_credentials(): void
    {
        $username = $this->uniqueUsername();
        $password = 'TestPass1!';
        $this->post('/user/register.json', [
            'username' => $username,
            'password' => $password,
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $result = $this->auth($username, $password);

        $this->assertArrayHasKey('apikey_write', $result);
        $this->assertArrayHasKey('apikey_read', $result);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['apikey_write']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['apikey_read']);
    }

    /** @test */
    public function auth_fails_with_wrong_password(): void
    {
        $username = $this->uniqueUsername();
        $this->post('/user/register.json', [
            'username' => $username,
            'password' => 'TestPass1!',
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $result = $this->post('/user/auth.json', [
            'username' => $username,
            'password' => 'WrongPass1!',
        ]);

        $this->assertFalse($result['success'] ?? true);
    }

    /** @test */
    public function auth_fails_for_nonexistent_user(): void
    {
        $result = $this->post('/user/auth.json', [
            'username' => $this->uniqueUsername(),
            'password' => 'SomePass1!',
        ]);

        $this->assertFalse($result['success'] ?? true);
    }

    // -------------------------------------------------------------------------
    // POST /user/login.json  (session-based auth)
    // -------------------------------------------------------------------------

    /** @test */
    public function login_succeeds_with_correct_credentials(): void
    {
        $username = $this->uniqueUsername();
        $password = 'TestPass1!';
        $this->post('/user/register.json', [
            'username' => $username,
            'password' => $password,
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $result = $this->post('/user/login.json', [
            'username'   => $username,
            'password'   => $password,
            'rememberme' => 0,
        ]);

        $this->assertTrue($result['success'] ?? false, $result['message'] ?? json_encode($result));
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        $username = $this->uniqueUsername();
        $this->post('/user/register.json', [
            'username' => $username,
            'password' => 'TestPass1!',
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);

        $result = $this->post('/user/login.json', [
            'username'   => $username,
            'password'   => 'WrongPass1!',
            'rememberme' => 0,
        ]);

        $this->assertFalse($result['success'] ?? true);
    }

    // -------------------------------------------------------------------------
    // Authenticated endpoints — tested via API key in query string
    // -------------------------------------------------------------------------

    /**
     * Registers a user, authenticates, and returns ['apikey_write', 'apikey_read'].
     */
    private function registerAndAuth(): array
    {
        $username = $this->uniqueUsername();
        $password = 'TestPass1!';
        $this->post('/user/register.json', [
            'username' => $username,
            'password' => $password,
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);
        return $this->auth($username, $password);
    }

    /** @test */
    public function gettimezone_returns_utc_for_new_user(): void
    {
        $keys = $this->registerAndAuth();

        $result = $this->get('/user/gettimezone.json', ['apikey' => $keys['apikey_read']]);

        $this->assertSame('UTC', $result);
    }

    /** @test */
    public function gettimezones_returns_array_containing_utc(): void
    {
        // gettimezones is public — no auth needed.
        $result = $this->get('/user/gettimezones.json');

        $this->assertIsArray($result);
        $ids = array_column($result, 'id');
        $this->assertContains('UTC', $ids);
        $this->assertContains('Europe/London', $ids);
    }

    /** @test */
    public function changepassword_allows_login_with_new_password(): void
    {
        $username = $this->uniqueUsername();
        $oldPass  = 'OldPass1!';
        $newPass  = 'NewPass1!';

        $this->post('/user/register.json', [
            'username' => $username,
            'password' => $oldPass,
            'email'    => $username . '@example.test',
            'timezone' => 'UTC',
        ]);
        $keys = $this->auth($username, $oldPass);

        $change = $this->post('/user/changepassword.json', [
            'old' => $oldPass,
            'new' => $newPass,
        ], ['apikey' => $keys['apikey_write']]);

        $this->assertTrue($change['success'] ?? false, $change['message'] ?? json_encode($change));

        // Old password must no longer work.
        $oldAuth = $this->post('/user/auth.json', [
            'username' => $username,
            'password' => $oldPass,
        ]);
        $this->assertFalse($oldAuth['success'] ?? true);

        // New password must work.
        $newAuth = $this->post('/user/auth.json', [
            'username' => $username,
            'password' => $newPass,
        ]);
        $this->assertTrue($newAuth['success'] ?? false);
    }

    /** @test */
    public function changepassword_requires_write_apikey(): void
    {
        $keys = $this->registerAndAuth();

        // Using the read key must not succeed.
        $result = $this->post('/user/changepassword.json', [
            'old' => 'TestPass1!',
            'new' => 'NewPass1!',
        ], ['apikey' => $keys['apikey_read']]);

        // The endpoint returns false/null or an error when auth is insufficient.
        $this->assertFalse($result['success'] ?? $result ?? true);
    }

    /** @test */
    public function getuuid_returns_uuid_string(): void
    {
        $keys = $this->registerAndAuth();

        $result = $this->get('/user/getuuid.json', ['apikey' => $keys['apikey_read']]);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $result['message']
        );
    }

    /** @test */
    public function unauthenticated_request_to_protected_endpoint_returns_no_data(): void
    {
        // Without a valid apikey the endpoint returns false/null or a plain-text
        // error — either way it must not return the actual timezone string.
        $url = static::$baseUrl . '/user/gettimezone.json?' . http_build_query(['apikey' => str_repeat('0', 32)]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($ch);
        curl_close($ch);

        // Must not contain a real timezone value.
        $this->assertStringNotContainsString('UTC', $body);
        $this->assertStringNotContainsString('Europe/', $body);
    }
}
