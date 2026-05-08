<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Rememberme model.
 *
 * These tests focus on the pure parsing and validation logic inside
 * getCookieValues() and the public helper methods.  No database is touched —
 * mysqli is mocked and $_COOKIE is manipulated directly.
 */
class RemembermeModelTest extends TestCase
{
    private Rememberme $rememberme;
    private const COOKIE_NAME = 'EMONCMS_REMEMBERME';

    protected function setUp(): void
    {
        $mysqli = $this->createMock(mysqli::class);
        $this->rememberme = new Rememberme($mysqli);
    }

    protected function tearDown(): void
    {
        // Always restore the superglobal so tests don't bleed into each other.
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function callGetCookieValues(): mixed
    {
        $ref = new ReflectionMethod(Rememberme::class, 'getCookieValues');
        $ref->setAccessible(true);
        return $ref->invoke($this->rememberme);
    }

    private function validToken(): string
    {
        return str_repeat('a', 32); // 32-char hex string
    }

    // -------------------------------------------------------------------------
    // getCookieName()
    // -------------------------------------------------------------------------

    /** @test */
    public function getCookieName_returns_expected_value(): void
    {
        $this->assertSame(self::COOKIE_NAME, $this->rememberme->getCookieName());
    }

    // -------------------------------------------------------------------------
    // loginTokenWasInvalid()
    // -------------------------------------------------------------------------

    /** @test */
    public function loginTokenWasInvalid_is_false_on_fresh_instance(): void
    {
        $this->assertFalse($this->rememberme->loginTokenWasInvalid());
    }

    // -------------------------------------------------------------------------
    // getCookieValues() — called via Reflection
    // -------------------------------------------------------------------------

    /** @test */
    public function getCookieValues_returns_false_when_cookie_absent(): void
    {
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_for_empty_cookie(): void
    {
        $_COOKIE[self::COOKIE_NAME] = '';
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_when_only_two_parts(): void
    {
        $_COOKIE[self::COOKIE_NAME] = '123|' . $this->validToken();
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_for_non_integer_userid(): void
    {
        $_COOKIE[self::COOKIE_NAME] = 'notanint|' . $this->validToken() . '|' . $this->validToken();
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_for_invalid_token_format(): void
    {
        // Token contains a non-hex character (Z).
        $badToken = str_repeat('Z', 32);
        $_COOKIE[self::COOKIE_NAME] = '1|' . $badToken . '|' . $this->validToken();
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_for_invalid_persistent_token_format(): void
    {
        $badToken = str_repeat('Z', 32);
        $_COOKIE[self::COOKIE_NAME] = '1|' . $this->validToken() . '|' . $badToken;
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_false_for_short_token(): void
    {
        // 31 chars — one short of the required 32.
        $shortToken = str_repeat('a', 31);
        $_COOKIE[self::COOKIE_NAME] = '1|' . $shortToken . '|' . $this->validToken();
        $this->assertFalse($this->callGetCookieValues());
    }

    /** @test */
    public function getCookieValues_returns_object_for_valid_cookie(): void
    {
        $token      = $this->validToken();
        $persistent = str_repeat('b', 32);
        $_COOKIE[self::COOKIE_NAME] = "42|{$token}|{$persistent}";

        $result = $this->callGetCookieValues();

        $this->assertIsObject($result);
        $this->assertSame(42, $result->userid);
        $this->assertSame($token, $result->token);
        $this->assertSame($persistent, $result->persistentToken);
    }

    /** @test */
    public function getCookieValues_accepts_cookie_with_extra_pipe_in_value(): void
    {
        // The implementation uses explode(..., 3) so a | inside the persistent
        // token area is absorbed into the third segment without breaking parsing.
        $token      = $this->validToken();
        $persistent = str_repeat('c', 32);
        $_COOKIE[self::COOKIE_NAME] = "1|{$token}|{$persistent}|extra";

        // explode with limit 3 makes the third part "cccc...|extra" — which
        // fails the 32-char hex regex, so getCookieValues() should return false.
        $this->assertFalse($this->callGetCookieValues());
    }
}
