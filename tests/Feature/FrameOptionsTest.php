<?php

/**
 * Framing policy (clickjacking protection) and referer policy.
 *
 * Pages must only be frameable by the site itself. Content meant to be placed
 * in an iframe on another site takes the separate embed policy, which is a
 * public dashboard and graph/embed.
 *
 * The route on its own does not say whether a page may be embedded.
 * dashboard/view serves a public dashboard to anyone and a private one to its
 * owner, so the controller says which it served, see allow_public_embed in
 * core.php. Before that, a private dashboard took the embed policy too, which
 * on its default of "*" let any site frame it.
 *
 * Run with:  php vendor/bin/phpunit --configuration tests/phpunit.feature.xml
 */
class FrameOptionsTest extends ApiTestCase
{
    public static function tearDownAfterClass(): void
    {
        static::$mysqli->query(
            "DELETE d FROM dashboard d JOIN users u ON d.userid = u.id WHERE u.username LIKE 'feat%'"
        );
        static::deleteTestUsers();
    }

    /**
     * Fetch a path anonymously and return its response headers, lowercased
     * names to values. No cookie jar and no apikey, so this is the path a
     * framing site would take.
     */
    private function headers(string $path): array
    {
        $ch = curl_init(static::$baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $raw = curl_exec($ch);
        $this->assertNotFalse($raw, "HEAD $path failed: " . curl_error($ch));
        curl_close($ch);

        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            if (strpos($line, ':') === false) continue;
            list($name, $value) = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }
        return $headers;
    }

    /**
     * Insert a user row directly.
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

    private function seedDashboard(int $userid, bool $public): int
    {
        $stmt = static::$mysqli->prepare(
            "INSERT INTO dashboard (userid, name, alias, description, content, public)
             VALUES (?, 'frame policy', '', '', '[]', ?)"
        );
        $pub = $public ? 1 : 0;
        $stmt->bind_param("ii", $userid, $pub);
        $this->assertTrue($stmt->execute(), 'seedDashboard insert failed: ' . static::$mysqli->error);
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function testOrdinaryPagesAreSameOriginOnly(): void
    {
        foreach (['/user/login', '/feed/list', '/input/view'] as $path) {
            $headers = $this->headers($path);
            $this->assertSame("frame-ancestors 'self'", $headers['content-security-policy'] ?? '', $path);
            $this->assertSame('SAMEORIGIN', $headers['x-frame-options'] ?? '', $path);
        }
    }

    /**
     * embed=1 strips the page chrome and works on any route, so it must not
     * relax the policy. Otherwise it is a one parameter bypass.
     */
    public function testEmbedParameterDoesNotRelaxThePolicy(): void
    {
        $headers = $this->headers('/user/view?embed=1');
        $this->assertSame("frame-ancestors 'self'", $headers['content-security-policy'] ?? '');
        $this->assertSame('SAMEORIGIN', $headers['x-frame-options'] ?? '');
    }

    public function testGraphEmbedUsesTheEmbedPolicy(): void
    {
        $headers = $this->headers('/graph/embed');
        $this->assertSame('frame-ancestors *', $headers['content-security-policy'] ?? '');
        // X-Frame-Options cannot express "any origin", so it is not sent
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testPublicDashboardUsesTheEmbedPolicy(): void
    {
        [$userid] = $this->seedUser();
        $id = $this->seedDashboard($userid, true);

        $headers = $this->headers("/dashboard/view?id=$id");
        $this->assertSame('frame-ancestors *', $headers['content-security-policy'] ?? '');
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    /**
     * The regression this covers: a private dashboard took the embed policy
     * because the route matched, so any site could frame it.
     */
    public function testPrivateDashboardIsSameOriginOnly(): void
    {
        [$userid] = $this->seedUser();
        $id = $this->seedDashboard($userid, false);

        $headers = $this->headers("/dashboard/view?id=$id");
        $this->assertSame("frame-ancestors 'self'", $headers['content-security-policy'] ?? '');
        $this->assertSame('SAMEORIGIN', $headers['x-frame-options'] ?? '');
    }

    /**
     * A dashboard that does not resolve returns an empty route, which is not a
     * public dashboard and must not take the embed policy either.
     */
    public function testDashboardViewWithoutADashboardIsSameOriginOnly(): void
    {
        $headers = $this->headers('/dashboard/view');
        $this->assertSame("frame-ancestors 'self'", $headers['content-security-policy'] ?? '');
        $this->assertSame('SAMEORIGIN', $headers['x-frame-options'] ?? '');
    }

    /**
     * Emoncms accepts an apikey and a readkey as query parameters, and a
     * dashboard may carry an image the author pointed at another site. Without
     * this header the key travels there in the referer of that fetch.
     */
    public function testReferrerPolicyKeepsTheQueryStringOffSite(): void
    {
        foreach (['/user/login', '/dashboard/view'] as $path) {
            $headers = $this->headers($path);
            $this->assertSame(
                'strict-origin-when-cross-origin',
                $headers['referrer-policy'] ?? '',
                $path
            );
        }
    }
}
