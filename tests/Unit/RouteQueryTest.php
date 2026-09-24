<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The rewrite rule writes the request path into q. Where the path is written
 * decoded, an encoded & in it starts a second q and PHP routes on the last
 * one, so index.php refuses a query string holding two.
 *
 * core.php is loaded in its own php process, as the unit bootstrap declares
 * some of its functions.
 */
final class RouteQueryTest extends TestCase
{
    private function ambiguous(array $queries): array
    {
        $root = dirname(__DIR__, 2);
        $code = sprintf(
            'define("EMONCMS_EXEC", 1); chdir(%s); require "core.php"; '
            . '$out = []; foreach (json_decode(%s, true) as $q) $out[] = route_query_is_ambiguous($q); '
            . 'echo json_encode($out);',
            var_export($root, true),
            var_export(json_encode($queries), true)
        );
        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code));
        return json_decode((string) $output, true);
    }

    #[Test]
    public function one_q_is_accepted(): void
    {
        $this->assertSame(
            [false, false, false, false, false],
            $this->ambiguous([
                '',
                'q=feed/list.json',
                'q=feed/data.json&id=1&start=0',
                'q=dashboard/view&id=3&apikey=abc',
                'q=x&qq=1&aq=2&Q=3',
            ])
        );
    }

    #[Test]
    public function a_second_q_is_refused(): void
    {
        $this->assertSame(
            [true, true, true, true, true],
            $this->ambiguous([
                'q=EnergyPi&q=feed/delete.json&id=5',
                'q=version&%71=feed/delete.json',
                'q=a& q=b',
                'q=a&q[]=b',
                'q[]=a&q[]=b',
            ])
        );
    }
}
