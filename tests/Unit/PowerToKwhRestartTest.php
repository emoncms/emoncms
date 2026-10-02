<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Lib/enum.php';
require_once __DIR__ . '/../../Modules/feed/feed_model.php';
require_once __DIR__ . '/../../Modules/process/process_model.php';

/**
 * power_to_kwh after a restart.
 *
 * Runs the real Feed model and PHPFina engine against an in-memory redis.
 * Each test sets up a kWh feed holding 58.4 kWh on disk with an empty redis
 * cache, then processes 3600 W for 10 s.
 */

// Subset of the redis API used by Feed and power_to_kwh
class FakeRestartRedis
{
    public $hashes = [];

    public function exists($key) { return isset($this->hashes[$key]); }
    public function del($key) { unset($this->hashes[$key]); return 1; }
    public function hExists($key, $field) { return isset($this->hashes[$key][$field]); }
    public function hget($key, $field) { return $this->hashes[$key][$field] ?? false; }
    public function hGetAll($key) { return $this->hashes[$key] ?? []; }

    public function hmget($key, $fields)
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $this->hashes[$key][$field] ?? false;
        }
        return $out;
    }

    // phpredis stores null and false as an empty string
    public function hMset($key, $values)
    {
        foreach ($values as $field => $value) {
            $this->hashes[$key][$field] = ($value === null || $value === false) ? '' : (string) $value;
        }
        return true;
    }

    public function hset($key, $field, $value)
    {
        $this->hMset($key, [$field => $value]);
        return 1;
    }
}

class PowerToKwhRestartTest extends TestCase
{
    const FEED = 1;
    const INTERVAL = 10;
    // Last datapoint on disk
    const T = 1767225600;
    const KWH = 58.4;

    // EngineClass caches engines in a static, so one data dir for the class
    private static string $dir;

    private Feed $feed;
    private FakeRestartRedis $redis;
    private Process $process;

    public static function setUpBeforeClass(): void
    {
        self::$dir = sys_get_temp_dir() . '/emoncms_restart_test_' . getmypid() . '/';
        @mkdir(self::$dir);
    }

    public static function tearDownAfterClass(): void
    {
        array_map('unlink', glob(self::$dir . '*'));
        @rmdir(self::$dir);
    }

    protected function setUp(): void
    {
        $GLOBALS['settings']['mqtt'] = ['enabled' => false];
        array_map('unlink', glob(self::$dir . '*'));

        $this->redis = new FakeRestartRedis();
        $this->feed = new Feed(null, $this->redis, [
            'redisbuffer' => ['enabled' => false],
            'phpfina' => ['datadir' => self::$dir],
        ]);

        $engine = $this->feed->EngineClass(Engine::PHPFINA);
        $engine->create(self::FEED, ['interval' => self::INTERVAL]);
        $engine->post_multiple(self::FEED, [
            [self::T - 20, 58.0],
            [self::T - 10, 58.2],
            [self::T, self::KWH],
        ]);

        // Feed metadata as load_feed_to_redis writes it, without time and value
        $this->redis->hashes['feed:' . self::FEED] = [
            'id' => (string) self::FEED,
            'userid' => '1',
            'engine' => (string) Engine::PHPFINA,
        ];

        $stmt = $this->createStub(mysqli_stmt::class);
        $stmt->method('fetch')->willReturn(true);
        $mysqli = $this->createStub(mysqli::class);
        $mysqli->method('prepare')->willReturn($stmt);
        $GLOBALS['redis'] = $this->redis;
        $this->process = new Process($mysqli, null, $this->feed, 'UTC');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['redis']);
    }

    private function power_to_kwh(int $time, float $watts): void
    {
        $this->process->input($time, $watts, 'process__power_to_kwh:' . self::FEED, [
            'sourcetype' => ProcessOriginType::INPUT,
            'sourceid' => 1,
        ]);
    }

    private function disk_lastvalue(): ?float
    {
        return $this->feed->EngineClass(Engine::PHPFINA)->lastvalue(self::FEED)['value'];
    }

    /**
     * Redis restarted empty. Last value is loaded from disk.
     */
    public function test_continues_after_redis_flush(): void
    {
        $this->power_to_kwh(self::T + 10, 3600);

        $this->assertEqualsWithDelta(self::KWH + 0.01, $this->disk_lastvalue(), 1e-4);
    }

    /**
     * New feed with no datapoints starts from 0
     */
    public function test_new_feed_starts_from_zero(): void
    {
        $engine = $this->feed->EngineClass(Engine::PHPFINA);
        $engine->delete(self::FEED);
        $engine->create(self::FEED, ['interval' => self::INTERVAL]);

        $this->power_to_kwh(self::T + 10, 3600);
        $this->power_to_kwh(self::T + 20, 3600);

        $this->assertEqualsWithDelta(0.01, $this->disk_lastvalue(), 1e-4);
    }
}
