<?php

/**
 * Feature tests for dashboard access control via the public-profile route.
 *
 * Browsing /<username>/dashboard/view?id=N must expose only that user's
 * public dashboards, never their private ones, even though on that route the
 * resolved $userid is the profile owner.
 *
 * Users and dashboards are seeded directly in the database so the test is
 * self-contained and independent of the register/apikey/email configuration.
 * Access is then exercised with anonymous HTML requests - no session cookie,
 * no apikey - which is exactly the public-profile path.
 *
 * Run with:  php vendor/bin/phpunit --configuration tests/phpunit.feature.xml
 */
class DashboardAccessTest extends ApiTestCase
{
    public static function tearDownAfterClass(): void
    {
        // Remove dashboards owned by this suite's users, then the users.
        static::$mysqli->query(
            "DELETE d FROM dashboard d JOIN users u ON d.userid = u.id WHERE u.username LIKE 'feat%'"
        );
        static::deleteTestUsers();
    }

    /**
     * Insert a user row directly and return [userid, username]. Only the
     * columns needed for public-profile resolution are set; NOT NULL columns
     * without a default (admin, account_admin) are set explicitly.
     *
     * @return array{0:int,1:string}
     */
    private function seedUser(): array
    {
        $username = $this->uniqueUsername();
        $stmt = static::$mysqli->prepare(
            "INSERT INTO users (username, email, password, salt, apikey_read, apikey_write, admin, account_admin)
             VALUES (?, ?, '', '', ?, ?, 0, 0)"
        );
        $email = $username . '@example.test';
        $read  = bin2hex(random_bytes(16));
        $write = bin2hex(random_bytes(16));
        $stmt->bind_param("ssss", $username, $email, $read, $write);
        $this->assertTrue($stmt->execute(), 'seedUser insert failed: ' . static::$mysqli->error);
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return [$id, $username];
    }

    /**
     * Insert a dashboard for $userid with $name (used as a canary marker) and
     * the given public flag. Returns the new dashboard id.
     */
    private function seedDashboard(int $userid, string $name, bool $public): int
    {
        $stmt = static::$mysqli->prepare(
            "INSERT INTO dashboard (userid, name, alias, description, content, public)
             VALUES (?, ?, '', '', '[]', ?)"
        );
        $pub = $public ? 1 : 0;
        $stmt->bind_param("isi", $userid, $name, $pub);
        $this->assertTrue($stmt->execute(), 'seedDashboard insert failed: ' . static::$mysqli->error);
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Anonymous HTML GET - deliberately sends no cookie jar and no apikey, so
     * it exercises the pure public-profile access path.
     */
    private function anonymousHtml(string $path): string
    {
        $ch = curl_init(static::$baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $this->assertNotFalse($body, "GET $path failed: " . curl_error($ch));
        // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
        return (string) $body;
    }

    /**
     * @test
     */
    public function public_profile_does_not_expose_a_private_dashboard_by_id(): void
    {
        [$userid, $username] = $this->seedUser();
        $canary = 'PRIVATECANARY' . strtoupper(bin2hex(random_bytes(3)));
        $id = $this->seedDashboard($userid, $canary, false);

        $body = $this->anonymousHtml("/$username/dashboard/view?id=$id");

        $this->assertStringNotContainsString(
            $canary,
            $body,
            "Private dashboard (id=$id, public=0) was disclosed via the public-profile route /$username/dashboard/view?id=$id"
        );
    }

    /**
     * Over-blocking guard: the fix must not break legitimate public sharing.
     *
     * @test
     */
    public function public_profile_still_serves_a_public_dashboard_by_id(): void
    {
        [$userid, $username] = $this->seedUser();
        $canary = 'PUBLICCANARY' . strtoupper(bin2hex(random_bytes(3)));
        $id = $this->seedDashboard($userid, $canary, true);

        $body = $this->anonymousHtml("/$username/dashboard/view?id=$id");

        $this->assertStringContainsString(
            $canary,
            $body,
            "Public dashboard (id=$id, public=1) should be viewable via the public-profile route"
        );
    }

    /**
     * A user must not be able to read another user's private dashboard by
     * naming their own profile but pointing id at the victim's dashboard.
     *
     * @test
     */
    public function public_profile_does_not_leak_a_foreign_private_dashboard(): void
    {
        [$victimId]              = $this->seedUser();
        [, $attackerName]        = $this->seedUser();

        $canary = 'FOREIGNCANARY' . strtoupper(bin2hex(random_bytes(3)));
        $victimDashId = $this->seedDashboard($victimId, $canary, false);

        // Attacker browses their OWN public profile but targets the victim's id.
        $body = $this->anonymousHtml("/$attackerName/dashboard/view?id=$victimDashId");

        $this->assertStringNotContainsString(
            $canary,
            $body,
            "Victim's private dashboard (id=$victimDashId) leaked via /$attackerName/dashboard/view?id=$victimDashId"
        );
    }
}
