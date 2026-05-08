<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Rememberme model.
 *
 * Tests the database-facing triplet operations (store, find, clean) by calling
 * private methods via Reflection against the real MySQL database.
 * The public cookieIsValid() method is tested by manipulating $_COOKIE directly.
 *
 * A temporary test user row is inserted before the suite and removed after.
 */
class RemembermeModelIntegrationTest extends TestCase
{
    private static mysqli $mysqli;
    private static int $testUserId;

    private Rememberme $rememberme;
    private const COOKIE_NAME = 'EMONCMS_REMEMBERME';

    // -------------------------------------------------------------------------
    // Suite setup / teardown
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        static::$mysqli = $GLOBALS['test_mysqli'];

        // Insert a minimal user row so foreign-key-style userid references work.
        $stmt = static::$mysqli->prepare(
            "INSERT INTO users (username, password, salt, email, admin, apikey_read, apikey_write)
              VALUES ('rmtest_integration', '', '', 'rmtest@example.test', 0, '', '')"
        );
        $stmt->execute();
        static::$testUserId = (int) static::$mysqli->insert_id;
        $stmt->close();
    }

    public static function tearDownAfterClass(): void
    {
        // Remove the test user and any leftover rememberme rows.
        static::$mysqli->query("DELETE FROM rememberme WHERE userid = '" . static::$testUserId . "'");
        static::$mysqli->query("DELETE FROM users WHERE id = '" . static::$testUserId . "'");
    }

    protected function setUp(): void
    {
        $this->rememberme = new Rememberme(static::$mysqli);
        // Clean any leftover triplets from a previous test.
        static::$mysqli->query("DELETE FROM rememberme WHERE userid = '" . static::$testUserId . "'");
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function callPrivate(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(Rememberme::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->rememberme, ...$args);
    }

    private function makeCookieValues(int $userid = 0): object
    {
        $cv = new stdClass();
        $cv->userid          = $userid ?: static::$testUserId;
        $cv->token           = bin2hex(random_bytes(16));
        $cv->persistentToken = bin2hex(random_bytes(16));
        return $cv;
    }

    private function countTriplets(): int
    {
        $result = static::$mysqli->query(
            "SELECT COUNT(*) AS c FROM rememberme WHERE userid = '" . static::$testUserId . "'"
        );
        return (int) $result->fetch_object()->c;
    }

    // -------------------------------------------------------------------------
    // storeTriplet() — stores hashed values in the database
    // -------------------------------------------------------------------------

    /** @test */
    public function storeTriplet_inserts_row_into_database(): void
    {
        $cv = $this->makeCookieValues();
        $result = $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        $this->assertTrue($result);
        $this->assertSame(1, $this->countTriplets());
    }

    /** @test */
    public function storeTriplet_hashes_token_before_storing(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        $row = static::$mysqli->query(
            "SELECT token FROM rememberme WHERE userid = '" . static::$testUserId . "'"
        )->fetch_object();

        // The raw token must NOT be stored — only its sha256 hash.
        $this->assertNotSame($cv->token, $row->token);
        $this->assertSame(hash('sha256', $cv->token), $row->token);
    }

    // -------------------------------------------------------------------------
    // findTriplet()
    // -------------------------------------------------------------------------

    /** @test */
    public function findTriplet_returns_found_for_valid_triplet(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        $result = $this->callPrivate('findTriplet', $cv);

        $this->assertSame(Rememberme::TRIPLET_FOUND, $result);
    }

    /** @test */
    public function findTriplet_returns_invalid_when_token_does_not_match(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        // Tamper with the token — same persistentToken, wrong token.
        $tampered = clone $cv;
        $tampered->token = bin2hex(random_bytes(16));

        $result = $this->callPrivate('findTriplet', $tampered);

        $this->assertSame(Rememberme::TRIPLET_INVALID, $result);
    }

    /** @test */
    public function findTriplet_returns_not_found_for_missing_entry(): void
    {
        $cv = $this->makeCookieValues();
        // Do NOT call storeTriplet — nothing in the DB.

        $result = $this->callPrivate('findTriplet', $cv);

        $this->assertSame(Rememberme::TRIPLET_NOT_FOUND, $result);
    }

    // -------------------------------------------------------------------------
    // cleanTriplet() via cookieIsValid() / findTriplet() after clean
    // -------------------------------------------------------------------------

    /** @test */
    public function cleanTriplet_removes_entry_from_database(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);
        $this->assertSame(1, $this->countTriplets());

        $this->callPrivate('cleanTriplet', $cv);

        $this->assertSame(0, $this->countTriplets());
    }

    // -------------------------------------------------------------------------
    // cleanAllTriplets()
    // -------------------------------------------------------------------------

    /** @test */
    public function cleanAllTriplets_removes_all_triplets_for_user(): void
    {
        // Store two separate triplets.
        $this->callPrivate('storeTriplet', $this->makeCookieValues(), time() + 7776000);
        $this->callPrivate('storeTriplet', $this->makeCookieValues(), time() + 7776000);
        $this->assertSame(2, $this->countTriplets());

        $this->callPrivate('cleanAllTriplets', static::$testUserId);

        $this->assertSame(0, $this->countTriplets());
    }

    // -------------------------------------------------------------------------
    // cookieIsValid() — public method, uses $_COOKIE superglobal
    // -------------------------------------------------------------------------

    /** @test */
    public function cookieIsValid_returns_true_when_cookie_matches_db(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        $_COOKIE[self::COOKIE_NAME] = implode('|', [$cv->userid, $cv->token, $cv->persistentToken]);

        $this->assertTrue($this->rememberme->cookieIsValid($cv->userid));
    }

    /** @test */
    public function cookieIsValid_returns_false_when_token_is_tampered(): void
    {
        $cv = $this->makeCookieValues();
        $this->callPrivate('storeTriplet', $cv, time() + 7776000);

        // Replace token with a different value.
        $_COOKIE[self::COOKIE_NAME] = implode('|', [
            $cv->userid,
            bin2hex(random_bytes(16)), // wrong token
            $cv->persistentToken,
        ]);

        $this->assertFalse($this->rememberme->cookieIsValid($cv->userid));
    }

    /** @test */
    public function cookieIsValid_returns_false_when_no_db_entry_exists(): void
    {
        $cv = $this->makeCookieValues();
        // No storeTriplet call — nothing in the DB.
        $_COOKIE[self::COOKIE_NAME] = implode('|', [$cv->userid, $cv->token, $cv->persistentToken]);

        $this->assertFalse($this->rememberme->cookieIsValid($cv->userid));
    }
}