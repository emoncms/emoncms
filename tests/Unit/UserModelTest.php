<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the User model.
 *
 * These tests cover the pure-logic methods that don't touch the database,
 * which makes them fast and runnable without any infrastructure.
 *
 * Run with:  composer phpunit
 *   or:      ./vendor/bin/phpunit
 */
class UserModelTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        // Mock mysqli — the constructor stores it but these tests never hit the DB.
        $mysqli = $this->createMock(mysqli::class);

        // Pass null for redis; rate-limiting is skipped when redis is absent.
        $this->user = new User($mysqli, null);
    }

    // -------------------------------------------------------------------------
    // timezone_valid()
    // -------------------------------------------------------------------------

    /** @test */
    public function timezone_valid_accepts_common_timezone(): void
    {
        $this->assertTrue($this->user->timezone_valid('Europe/London'));
    }

    /** @test */
    public function timezone_valid_accepts_timezone_with_underscore(): void
    {
        // e.g. "America/New_York" — the underscore is in \w so must be allowed.
        $this->assertTrue($this->user->timezone_valid('America/New_York'));
    }

    /** @test */
    public function timezone_valid_accepts_utc(): void
    {
        $this->assertTrue($this->user->timezone_valid('UTC'));
    }

    /** @test */
    public function timezone_valid_rejects_empty_string(): void
    {
        $this->assertFalse($this->user->timezone_valid(''));
    }

    /** @test */
    public function timezone_valid_rejects_too_short(): void
    {
        $this->assertFalse($this->user->timezone_valid('AB'));
    }

    /** @test */
    public function timezone_valid_rejects_too_long(): void
    {
        $this->assertFalse($this->user->timezone_valid(str_repeat('A', 51)));
    }

    /** @test */
    public function timezone_valid_rejects_string_with_injection_characters(): void
    {
        // Semicolons, angle brackets, quotes etc. must be rejected by the regex.
        $this->assertFalse($this->user->timezone_valid("Europe/London';DROP TABLE users--"));
        $this->assertFalse($this->user->timezone_valid('<script>'));
        $this->assertFalse($this->user->timezone_valid('../../etc/passwd'));
    }

    /** @test */
    public function timezone_valid_rejects_plausible_but_nonexistent_timezone(): void
    {
        // Passes character/length checks but is not in PHP's timezone list.
        $this->assertFalse($this->user->timezone_valid('Europe/Faketown'));
    }

    // -------------------------------------------------------------------------
    // validate_referrer()
    // -------------------------------------------------------------------------

    /** @test */
    public function validate_referrer_returns_empty_for_empty_input(): void
    {
        $this->assertSame('', $this->user->validate_referrer(''));
    }

    /** @test */
    public function validate_referrer_accepts_simple_relative_path(): void
    {
        $this->assertSame('/dashboard', $this->user->validate_referrer('/dashboard'));
    }

    /** @test */
    public function validate_referrer_accepts_relative_path_with_query(): void
    {
        $this->assertSame(
            '/feed/list?userid=1',
            $this->user->validate_referrer('/feed/list?userid=1')
        );
    }

    /** @test */
    public function validate_referrer_rejects_absolute_url_with_scheme(): void
    {
        // Open-redirect: attacker supplies an external URL.
        $this->assertSame('', $this->user->validate_referrer('https://evil.example.com'));
        $this->assertSame('', $this->user->validate_referrer('http://evil.example.com/steal'));
    }

    /** @test */
    public function validate_referrer_rejects_protocol_relative_url(): void
    {
        // //evil.example.com is treated as a host by browsers.
        $this->assertSame('', $this->user->validate_referrer('//evil.example.com'));
    }

    /** @test */
    public function validate_referrer_rejects_backslash_bypass_attempt(): void
    {
        // Some browsers normalise /\evil.com into //evil.com.
        $this->assertSame('', $this->user->validate_referrer('/\\evil.com'));
    }

    /** @test */
    public function validate_referrer_rejects_oversized_input(): void
    {
        $this->assertSame('', $this->user->validate_referrer(str_repeat('a', 2001)));
    }

    // -------------------------------------------------------------------------
    // Private validation helpers (tested via Reflection)
    // -------------------------------------------------------------------------

    private function callPrivate(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(User::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->user, ...$args);
    }

    // --- is_valid_email ---

    /** @test */
    public function is_valid_email_accepts_valid_address(): void
    {
        $result = $this->callPrivate('is_valid_email', 'user@example.com');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function is_valid_email_rejects_missing_at_sign(): void
    {
        $result = $this->callPrivate('is_valid_email', 'notanemail');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function is_valid_email_rejects_empty_string(): void
    {
        $result = $this->callPrivate('is_valid_email', '');
        $this->assertFalse($result['success']);
    }

    // --- is_valid_username ---

    /** @test */
    public function is_valid_username_accepts_alphanumeric(): void
    {
        $result = $this->callPrivate('is_valid_username', 'alice123');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function is_valid_username_rejects_special_characters(): void
    {
        $result = $this->callPrivate('is_valid_username', 'alice!');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function is_valid_username_rejects_too_short(): void
    {
        $result = $this->callPrivate('is_valid_username', 'ab');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function is_valid_username_rejects_too_long(): void
    {
        $result = $this->callPrivate('is_valid_username', str_repeat('a', 31));
        $this->assertFalse($result['success']);
    }

    // --- is_valid_password ---

    /** @test */
    public function is_valid_password_accepts_normal_password(): void
    {
        $result = $this->callPrivate('is_valid_password', 'correcthorsebatterystaple');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function is_valid_password_rejects_too_short(): void
    {
        $result = $this->callPrivate('is_valid_password', 'abc');
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function is_valid_password_rejects_too_long(): void
    {
        $result = $this->callPrivate('is_valid_password', str_repeat('a', 251));
        $this->assertFalse($result['success']);
    }
}
