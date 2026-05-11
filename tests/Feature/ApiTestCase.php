<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for feature (HTTP API) tests.
 *
 * Provides helpers for making GET/POST requests to the emoncms JSON API,
 * handling cookies for session-based auth, and managing test users.
 */
abstract class ApiTestCase extends TestCase
{
    protected static string $baseUrl;
    protected static mysqli $mysqli;

    /** CookieJar file path — shared within a test class so session persists. */
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        static::$baseUrl  = $GLOBALS['feature_base_url'];
        static::$mysqli   = $GLOBALS['test_mysqli'];
    }

    protected function setUp(): void
    {
        // Each test class gets its own cookie jar so sessions are isolated.
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'emoncms_feature_cookies_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }

    // -------------------------------------------------------------------------
    // HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * Send a GET request and return the decoded JSON body.
     * Throws if the response is not valid JSON.
     */
    protected function get(string $path, array $params = []): mixed
    {
        $url = static::$baseUrl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $body = curl_exec($ch);
        $this->assertNotFalse($body, "GET $path failed: " . curl_error($ch));
        curl_close($ch);

        return $this->decodeJson($body, "GET $path");
    }

    /**
     * Send a POST request with form-encoded body and return decoded JSON.
     */
    protected function post(string $path, array $fields = [], array $params = []): mixed
    {
        $url = static::$baseUrl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $body = curl_exec($ch);
        $this->assertNotFalse($body, "POST $path failed: " . curl_error($ch));
        curl_close($ch);

        return $this->decodeJson($body, "POST $path");
    }

    /**
     * Send a POST request and return both the decoded JSON body and the raw
     * response headers, keyed by lower-case header name.
     * Multi-value headers (e.g. Set-Cookie) are returned as arrays.
     *
     * @return array{body: mixed, headers: array<string, string|string[]>}
     */
    protected function postRaw(string $path, array $fields = [], array $params = []): array
    {
        $url = static::$baseUrl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // don't follow so we see Set-Cookie on the login response
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_HEADERFUNCTION => function ($ch, string $headerLine) use (&$responseHeaders): int {
                $trimmed = trim($headerLine);
                if (strpos($trimmed, ':') !== false) {
                    [$name, $value] = explode(':', $trimmed, 2);
                    $name  = strtolower(trim($name));
                    $value = trim($value);
                    if (isset($responseHeaders[$name])) {
                        $responseHeaders[$name] = (array) $responseHeaders[$name];
                        $responseHeaders[$name][] = $value;
                    } else {
                        $responseHeaders[$name] = $value;
                    }
                }
                return strlen($headerLine);
            },
        ]);

        $body = curl_exec($ch);
        $this->assertNotFalse($body, "POST $path failed: " . curl_error($ch));
        curl_close($ch);

        $decoded = json_decode($body, true); // null for non-JSON (redirects etc.)

        return ['body' => $decoded, 'headers' => $responseHeaders];
    }

    /**
     * Return all Set-Cookie header values from a postRaw() response as an
     * associative array of cookie-name => cookie-value (the part before ';').
     *
     * @param array<string, string|string[]> $headers
     * @return array<string, string>
     */
    protected function parseCookies(array $headers): array
    {
        $setCookie = $headers['set-cookie'] ?? [];
        $setCookie = (array) $setCookie;

        $cookies = [];
        foreach ($setCookie as $line) {
            $parts = explode(';', $line);
            $pair  = explode('=', trim($parts[0]), 2);
            if (count($pair) === 2) {
                $cookies[trim($pair[0])] = trim($pair[1]);
            }
        }
        return $cookies;
    }

    private function decodeJson(string $body, string $context): mixed
    {
        $decoded = json_decode($body, true);
        $this->assertNotNull(
            $decoded,
            "$context returned non-JSON body: " . substr($body, 0, 300)
        );
        return $decoded;
    }

    // -------------------------------------------------------------------------
    // Test-user helpers
    // -------------------------------------------------------------------------

    private const USER_PREFIX = 'feat';

    /**
     * Generates a unique username safe for emoncms (alphanumeric only, ≤30 chars).
     */
    protected function uniqueUsername(): string
    {
        return self::USER_PREFIX . bin2hex(random_bytes(4));
    }

    /**
     * Removes all test users created by this suite directly from the DB.
     */
    protected static function deleteTestUsers(): void
    {
        $prefix = self::USER_PREFIX . '%';
        $stmt = static::$mysqli->prepare("DELETE FROM users WHERE username LIKE ?");
        $stmt->bind_param("s", $prefix);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Calls POST /user/auth.json and returns the auth payload.
     * Also asserts that authentication succeeded.
     */
    protected function auth(string $username, string $password): array
    {
        $result = $this->post('/user/auth.json', [
            'username' => $username,
            'password' => $password,
        ]);
        $this->assertTrue(
            $result['success'] ?? false,
            "auth() failed for $username: " . ($result['message'] ?? json_encode($result))
        );
        return $result;
    }
}
