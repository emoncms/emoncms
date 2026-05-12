<?php

use PHPUnit\Framework\TestCase;

/**
 * Feature tests for the remember-me login flow.
 *
 * These tests exercise the full HTTP stack: register a user, log in with the
 * rememberme flag, assert the Set-Cookie response header, verify the database
 * row, then log out and confirm the row is removed.
 */
class RemembermeApiTest extends ApiTestCase
{
    private static string $username;
    private static string $password = 'TestPass1!';
    private static string $email;
    private static int    $userId   = 0;

    // -------------------------------------------------------------------------
    // Suite lifecycle
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$username = 'feat' . bin2hex(random_bytes(4));
        static::$email    = static::$username . '@rememberme.test';

        // Register via HTTP so the user exists server-side.
        $ch = curl_init(static::$baseUrl . '/user/register.json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'username' => static::$username,
                'password' => static::$password,
                'email'    => static::$email,
                'timezone' => 'Europe/London',
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT    => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);
        if (empty($result['success'])) {
            throw new \RuntimeException('setUpBeforeClass: registration failed — ' . ($result['message'] ?? $body));
        }

        // Fetch the userid so we can query the rememberme table later.
        $row = static::$mysqli->query(
            "SELECT id FROM users WHERE username = '" . static::$mysqli->real_escape_string(static::$username) . "'"
        )->fetch_object();
        static::$userId = (int) $row->id;
    }

    public static function tearDownAfterClass(): void
    {
        if (static::$userId) {
            static::$mysqli->query("DELETE FROM rememberme WHERE userid = '" . static::$userId . "'");
            static::$mysqli->query("DELETE FROM users WHERE id = '" . static::$userId . "'");
        }
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        // Clear any rememberme rows so tests don't bleed into each other.
        if (static::$userId) {
            static::$mysqli->query("DELETE FROM rememberme WHERE userid = '" . static::$userId . "'");
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function countDbTriplets(): int
    {
        $result = static::$mysqli->query(
            "SELECT COUNT(*) AS c FROM rememberme WHERE userid = '" . static::$userId . "'"
        );
        return (int) $result->fetch_object()->c;
    }

    private function loginViaHttp(int $rememberme): array
    {
        return $this->postRaw('/user/login.json', [
            'username'   => static::$username,
            'password'   => static::$password,
            'rememberme' => $rememberme,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
 * @test 
*/
    public function login_with_rememberme_returns_success(): void
    {
        $response = $this->loginViaHttp(1);

        $this->assertTrue($response['body']['success'] ?? false, 'Login should succeed');
    }

    /**
 * @test 
*/
    public function login_with_rememberme_sets_cookie_in_response_headers(): void
    {
        $response = $this->loginViaHttp(1);
        $cookies  = $this->parseCookies($response['headers']);

        $this->assertArrayHasKey(
            'EMONCMS_REMEMBERME',
            $cookies,
            'Set-Cookie header for EMONCMS_REMEMBERME should be present'
        );
        $this->assertNotEmpty($cookies['EMONCMS_REMEMBERME'], 'Cookie value should not be empty');
    }

    /**
 * @test 
*/
    public function login_without_rememberme_does_not_set_cookie(): void
    {
        $response = $this->loginViaHttp(0);
        $cookies  = $this->parseCookies($response['headers']);

        $this->assertArrayNotHasKey(
            'EMONCMS_REMEMBERME',
            $cookies,
            'Set-Cookie for EMONCMS_REMEMBERME should NOT be present when rememberme=0'
        );
    }

    /**
 * @test 
*/
    public function login_with_rememberme_creates_database_entry(): void
    {
        $this->loginViaHttp(1);

        $this->assertSame(1, $this->countDbTriplets(), 'Exactly one rememberme row should exist after login');
    }

    /**
 * @test 
*/
    public function logout_removes_rememberme_database_entry(): void
    {
        // Login with rememberme so a DB row is created.
        $response = $this->loginViaHttp(1);
        $this->assertSame(1, $this->countDbTriplets(), 'Precondition: DB row must exist before logout');

        // curl's file-based Netscape cookie jar doesn't persist cookies for
        // "localhost" (no TLD), so we capture the Set-Cookie values from the
        // login response and replay them manually in the logout request.
        $setCookies = (array) ($response['headers']['set-cookie'] ?? []);
        $cookiePairs = array_map(
            fn(string $line) => trim(explode(';', $line)[0]),
            $setCookies
        );
        $cookieHeader = implode('; ', $cookiePairs);

        $ch = curl_init(static::$baseUrl . '/user/logout.json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Cookie: $cookieHeader"],
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);

        $this->assertSame(0, $this->countDbTriplets(), 'Rememberme DB row should be removed after logout');
    }
}