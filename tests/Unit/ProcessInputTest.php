<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Lib/enum.php';
require_once __DIR__ . '/../../Modules/process/process_model.php';

/**
 * Unit tests for input processing.
 *
 * Runs the real Process class and core process functions against in-memory
 * feed, input and redis stores. Expected values record current behaviour,
 * so a refactor that changes any output fails here.
 *
 * Processes that need MySQL (update_feed_data) and the schedule and eventp
 * modules are not covered.
 */

// In-memory feed store. Records every post and serves last values.
class FakeProcessFeed
{
    public $posts = [];
    public $last = [];
    public $data = [];

    // Create a feed, optionally with a last datapoint
    public function seed($id, $time = null, $value = null)
    {
        $this->last[$id] = ['time' => $time, 'value' => $value];
    }

    public function post($id, $updatetime, $time, $value, $padding_mode = null)
    {
        $this->posts[] = [(int) $id, (int) $time, $value];
        // Feed::post stores updatetime as the last time
        $this->last[(int) $id] = ['time' => $updatetime, 'value' => $value];
        return true;
    }

    // Null when the feed does not exist
    public function get_timevalue($id)
    {
        return $this->last[(int) $id] ?? null;
    }

    public function get_data($id, $start, $end, $interval, $average, $timezone, $timeformat, $csv, $skipmissing, $limitinterval)
    {
        return $this->data[(int) $id] ?? [];
    }

    public function set_processlist_error_found($id) {}
}

// In-memory input store
class FakeProcessInput
{
    public $last = [];
    public $error_found = [];

    public function get_last_value($id)
    {
        return $this->last[(int) $id] ?? null;
    }

    public function set_timevalue($id, $time, $value)
    {
        $this->last[(int) $id] = $value;
    }

    public function set_processlist_error_found($id)
    {
        $this->error_found[] = $id;
    }
}

// Hash subset of the redis API used by process functions
class FakeProcessRedis
{
    public $hashes = [];

    public function exists($key)
    {
        return isset($this->hashes[$key]);
    }

    public function hmget($key, $fields)
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $this->hashes[$key][$field] ?? false;
        }
        return $out;
    }

    public function hMset($key, $values)
    {
        $this->hashes[$key] = array_merge($this->hashes[$key] ?? [], $values);
        return true;
    }

    public function hset($key, $field, $value)
    {
        $this->hashes[$key][$field] = $value;
        return 1;
    }
}

class ProcessInputTest extends TestCase
{
    // 2026-01-01 00:00 UTC
    const D = 1767225600;
    // 01:00 on day D
    const T = self::D + 3600;
    // Feed that collects the output of a process list
    const OUT = 99;

    private Process $process;
    private FakeProcessFeed $feed;
    private FakeProcessInput $input;
    private FakeProcessRedis $redis;

    protected function setUp(): void
    {
        $GLOBALS['settings']['mqtt'] = ['enabled' => false];

        $this->feed = new FakeProcessFeed();
        $this->input = new FakeProcessInput();
        $this->redis = new FakeProcessRedis();
        $GLOBALS['redis'] = $this->redis;

        $this->process = new Process($this->stub_mysqli(true), $this->input, $this->feed, 'UTC');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['redis']);
    }

    // mysqli stub for arg_access: fetch() result decides access
    private function stub_mysqli(bool $access): mysqli
    {
        $stmt = $this->createStub(mysqli_stmt::class);
        $stmt->method('fetch')->willReturn($access);
        $mysqli = $this->createStub(mysqli::class);
        $mysqli->method('prepare')->willReturn($stmt);
        return $mysqli;
    }

    private function run_list(string $processlist, array $samples, array $options = []): array
    {
        $options += ['sourcetype' => ProcessOriginType::INPUT, 'sourceid' => 1];
        $out = [];
        foreach ($samples as [$time, $value]) {
            $out[] = $this->process->input($time, $value, $processlist, $options);
        }
        return $out;
    }

    // Values posted to the OUT feed
    private function out_values(): array
    {
        $values = [];
        foreach ($this->feed->posts as [$id, $time, $value]) {
            if ($id === self::OUT) {
                $values[] = $value;
            }
        }
        return $values;
    }

    // -------------------------------------------------------------------------
    // Process ids
    // -------------------------------------------------------------------------

    /**
     * Process ids are stored in user process lists. Changing one breaks
     * existing configurations.
     */
    public function test_process_id_map_is_stable(): void
    {
        $expected = [
            1 => 'log_to_feed', 2 => 'scale', 3 => 'offset', 4 => 'power_to_kwh',
            5 => 'power_to_kwhd', 6 => 'times_input', 7 => 'input_ontime',
            8 => 'whinc_to_kwhd', 9 => 'kwh_to_kwhd_old', 10 => 'update_feed_data',
            11 => 'add_input', 12 => 'divide_input', 13 => 'phaseshift',
            14 => 'accumulator', 15 => 'ratechange', 16 => 'histogram', 17 => 'average',
            18 => 'heat_flux', 19 => 'power_acc_to_kwhd', 20 => 'pulse_diff',
            21 => 'kwh_to_power', 22 => 'subtract_input', 23 => 'kwh_to_kwhd',
            24 => 'allowpositive', 25 => 'allownegative', 26 => 'signed2unsigned',
            27 => 'max_value', 28 => 'min_value', 29 => 'add_feed', 30 => 'sub_feed',
            31 => 'multiply_by_feed', 32 => 'divide_by_feed', 33 => 'reset2zero',
            34 => 'wh_accumulator', 35 => 'publish_to_mqtt', 36 => 'reset2null',
            37 => 'reset2original', 42 => 'if_zero_skip', 43 => 'if_not_zero_skip',
            44 => 'if_null_skip', 45 => 'if_not_null_skip', 46 => 'if_gt_skip',
            47 => 'if_gt_equal_skip', 48 => 'if_lt_skip', 49 => 'if_lt_equal_skip',
            50 => 'if_equal_skip', 51 => 'if_not_equal_skip', 52 => 'goto_process',
            53 => 'source_feed_data_time', 55 => 'add_source_feed', 56 => 'sub_source_feed',
            57 => 'multiply_by_source_feed', 58 => 'divide_by_source_feed',
            59 => 'reciprocal_by_source_feed', 61 => 'max_value_allowed',
            62 => 'min_value_allowed', 63 => 'abs_value', 64 => 'kwh_accumulator',
            65 => 'log_to_feed_join', 66 => 'max_input', 67 => 'min_input',
            68 => 'max_feed', 69 => 'min_feed', 71 => 'power_to_kwh_custom',
        ];
        $expected = array_map(fn($fn) => "process__$fn", $expected);

        $this->assertSame($expected, $this->process->process_map);
        $this->assertSame(array_flip($expected), $this->process->process_map_reverse);
    }

    // -------------------------------------------------------------------------
    // Value processes
    // -------------------------------------------------------------------------

    /**
     * [process list, input values, expected values on OUT feed, setup]
     * setup: 'feeds' => [id => [time, value]], 'inputs' => [id => value]
     */
    public static function value_cases(): array
    {
        return [
            'scale' => ['process__scale:2', [10, null], [20, null]],
            'offset' => ['process__offset:1.5', [10, null], [11.5, null]],
            'allowpositive' => ['process__allowpositive:0', [-4, 4], [0, 4]],
            'allownegative' => ['process__allownegative:0', [-4, 4], [-4, 0]],
            'signed2unsigned' => ['process__signed2unsigned:0', [-1, 5], [65535, 5]],
            'max_value_allowed' => ['process__max_value_allowed:100', [150, 50], [100, 50]],
            'min_value_allowed' => ['process__min_value_allowed:0', [-5, 5], [0, 5]],
            'abs_value' => ['process__abs_value:0', [-3, 3, null], [3, 3, null]],
            'reset2zero' => ['process__reset2zero:0', [7], [0]],
            'reset2null' => ['process__reset2null:0', [7], [null]],
            'reset2original' => ['process__scale:10,process__reset2original:0', [7], [7]],

            'times_input' => ['process__times_input:3', [5], [10], ['inputs' => [3 => 2]]],
            'times_input missing' => ['process__times_input:3', [5], [null]],
            'divide_input' => ['process__divide_input:3', [10], [2.5], ['inputs' => [3 => 4]]],
            'divide_input zero' => ['process__divide_input:3', [10], [null], ['inputs' => [3 => 0]]],
            'add_input' => ['process__add_input:3', [10], [14], ['inputs' => [3 => 4]]],
            'subtract_input' => ['process__subtract_input:3', [10], [6], ['inputs' => [3 => 4]]],
            'max_input' => ['process__max_input:3', [80, 20], [50, 20], ['inputs' => [3 => 50]]],
            'min_input' => ['process__min_input:3', [80, 20], [80, 50], ['inputs' => [3 => 50]]],

            'add_feed' => ['process__add_feed:7', [10], [12], ['feeds' => [7 => [self::T, 2]]]],
            'add_feed missing feed' => ['process__add_feed:7', [10], [10]],
            'add_feed null' => ['process__add_feed:7', [10], [null], ['feeds' => [7 => [null, null]]]],
            'sub_feed' => ['process__sub_feed:7', [10], [8], ['feeds' => [7 => [self::T, 2]]]],
            'multiply_by_feed' => ['process__multiply_by_feed:7', [10], [20], ['feeds' => [7 => [self::T, 2]]]],
            'divide_by_feed' => ['process__divide_by_feed:7', [10], [5], ['feeds' => [7 => [self::T, 2]]]],
            'divide_by_feed zero' => ['process__divide_by_feed:7', [10], [null], ['feeds' => [7 => [self::T, 0]]]],
            'max_feed' => ['process__max_feed:7', [80, 20], [50, 20], ['feeds' => [7 => [self::T, 50]]]],
            'min_feed' => ['process__min_feed:7', [80, 20], [80, 50], ['feeds' => [7 => [self::T, 50]]]],

            'source_feed_data_time' => ['process__source_feed_data_time:7', [10], [2], ['feeds' => [7 => [self::T, 2]]]],
            'add_source_feed' => ['process__add_source_feed:7', [10], [12], ['feeds' => [7 => [self::T, 2]]]],
            'sub_source_feed' => ['process__sub_source_feed:7', [10], [8], ['feeds' => [7 => [self::T, 2]]]],
            'multiply_by_source_feed' => ['process__multiply_by_source_feed:7', [10], [20], ['feeds' => [7 => [self::T, 2]]]],
            'divide_by_source_feed' => ['process__divide_by_source_feed:7', [10], [5], ['feeds' => [7 => [self::T, 2]]]],
            'divide_by_source_feed zero' => ['process__divide_by_source_feed:7', [10], [null], ['feeds' => [7 => [self::T, 0]]]],
            'reciprocal_by_source_feed' => ['process__reciprocal_by_source_feed:7', [10], [0.5], ['feeds' => [7 => [self::T, 2]]]],
            'source feed missing' => ['process__add_source_feed:7', [10], [null]],

            // Retired processes pass the value through
            'histogram' => ['process__histogram:7', [10], [10]],
            'average' => ['process__average:7', [10], [10]],
            'phaseshift' => ['process__phaseshift:0', [10], [10]],
            'kwh_to_kwhd_old' => ['process__kwh_to_kwhd_old:7', [10], [10]],
            'power_acc_to_kwhd' => ['process__power_acc_to_kwhd:7', [10], [10]],
            'heat_flux' => ['process__heat_flux:7', [10], [10]],
            'publish_to_mqtt disabled' => ['process__publish_to_mqtt:emon/test', [10], [10]],

            // Numeric ids map to the same functions
            'numeric ids' => ['2:2,3:1', [10], [21]],
        ];
    }

    /**
     * @dataProvider value_cases
     */
    public function test_value_process(string $processlist, array $values, array $expected, array $setup = []): void
    {
        foreach ($setup['feeds'] ?? [] as $id => [$time, $value]) {
            $this->feed->seed($id, $time, $value);
        }
        $this->input->last = $setup['inputs'] ?? [];

        $samples = [];
        foreach ($values as $i => $value) {
            $samples[] = [self::T + 10 * $i, $value];
        }
        $this->run_list($processlist . ',process__log_to_feed:' . self::OUT, $samples);

        $this->assertEqualsWithDelta($expected, $this->out_values(), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Flow control
    // -------------------------------------------------------------------------

    /**
     * [skip process, input values, expected posts]
     * Each list is "<skip>,log_to_feed:5,log_to_feed:6".
     */
    public static function skip_cases(): array
    {
        return [
            'if_zero_skip' => ['process__if_zero_skip:0', [0, 3], [[6, 0], [5, 3], [6, 3]]],
            'if_not_zero_skip' => ['process__if_not_zero_skip:0', [0, 3], [[5, 0], [6, 0], [6, 3]]],
            'if_null_skip' => ['process__if_null_skip:0', [null, 3], [[6, null], [5, 3], [6, 3]]],
            'if_not_null_skip' => ['process__if_not_null_skip:0', [null, 3], [[5, null], [6, null], [6, 3]]],
            'if_gt_skip' => ['process__if_gt_skip:5', [6, 5], [[6, 6], [5, 5], [6, 5]]],
            'if_gt_equal_skip' => ['process__if_gt_equal_skip:5', [5, 4], [[6, 5], [5, 4], [6, 4]]],
            'if_lt_skip' => ['process__if_lt_skip:5', [4, 5], [[6, 4], [5, 5], [6, 5]]],
            'if_lt_equal_skip' => ['process__if_lt_equal_skip:5', [5, 6], [[6, 5], [5, 6], [6, 6]]],
            'if_equal_skip' => ['process__if_equal_skip:5', [5, 6], [[6, 5], [5, 6], [6, 6]]],
            'if_not_equal_skip' => ['process__if_not_equal_skip:5', [6, 5], [[6, 6], [5, 5], [6, 5]]],
            'goto_process' => ['process__goto_process:3', [1], [[6, 1]]],
            'error_found' => ['process__error_found:0', [1], []],
        ];
    }

    /**
     * @dataProvider skip_cases
     */
    public function test_flow_control(string $process, array $values, array $expected): void
    {
        $samples = [];
        foreach ($values as $i => $value) {
            $samples[] = [self::T + 10 * $i, $value];
        }
        $this->run_list("$process,process__log_to_feed:5,process__log_to_feed:6", $samples);

        $posts = array_map(fn($p) => [$p[0], $p[2]], $this->feed->posts);
        $this->assertSame($expected, $posts);
    }

    public function test_goto_loop_is_stopped(): void
    {
        $result = $this->run_list('process__scale:1,process__goto_process:2', [[self::T, 1]]);

        $this->assertSame([false], $result);
        $this->assertSame(ProcessError::TOO_MANY_ITERATIONS, $this->process->runtime_error);
        $this->assertSame([1], $this->input->error_found);
    }

    public function test_unknown_process_returns_false(): void
    {
        $this->assertSame([false], $this->run_list('process__missing:1', [[self::T, 1]]));
    }

    public function test_input_returns_final_value(): void
    {
        $this->assertSame([21], $this->run_list('process__scale:2,process__offset:1', [[self::T, 10]]));
    }

    public function test_virtual_feed_rejects_input_only_process(): void
    {
        $options = ['sourcetype' => ProcessOriginType::VIRTUALFEED];
        $this->assertSame([false], $this->run_list('process__log_to_feed:5', [[self::T, 1]], $options));
        $this->assertSame([], $this->feed->posts);
    }

    public function test_source_feed_data_time_uses_data_cache(): void
    {
        $this->feed->data[7] = [[self::T, 1], [self::T + 10, 2]];
        $options = [
            'sourcetype' => ProcessOriginType::VIRTUALFEED,
            'start' => self::T, 'end' => self::T + 20, 'interval' => 10,
            'average' => 0, 'timezone' => 'UTC', 'index' => 1,
        ];

        $this->assertSame([2], $this->run_list('process__source_feed_data_time:7', [[self::T, null]], $options));
    }

    // -------------------------------------------------------------------------
    // Feed state processes
    // -------------------------------------------------------------------------

    /**
     * [process list, [time, value] samples, expected [feedid, time, value] posts, seeded feed 7]
     */
    public static function feed_state_cases(): array
    {
        $D = self::D;
        $T = self::T;
        $next_day = $D + 86400;

        return [
            'log_to_feed' => ['process__log_to_feed:7', [[$T, 5]], [[7, $T, 5]], null],
            'log_to_feed_join' => ['process__log_to_feed_join:7', [[$T, 5]], [[7, $T, 5]], null],

            'power_to_kwh' => [
                'process__power_to_kwh:7',
                [[$T, 3600], [$T + 10, 3600], [$T + 7210, 3600]],
                [[7, $T, 0], [7, $T + 10, 0.01], [7, $T + 7210, 0.01]],
                [null, null],
            ],
            'power_to_kwh missing feed' => ['process__power_to_kwh:7', [[$T, 3600]], [], null],
            'power_to_kwhd' => [
                'process__power_to_kwhd:7',
                [[$T, 3600], [$T + 10, 3600]],
                [[7, $D, 0], [7, $D, 0.01]],
                [null, null],
            ],
            'power_to_kwhd new day' => [
                'process__power_to_kwhd:7',
                [[$next_day + 5, 3600]],
                [[7, $next_day, 0.01]],
                [$next_day - 5, 5],
            ],
            'power_to_kwh_custom' => [
                'process__power_to_kwh_custom:15:7',
                [[$T + 10, 3600], [$T + 905, 3600]],
                [[7, $T + 900, 0.51], [7, $T + 1800, 0.895]],
                [$T, 0.5],
            ],
            'input_ontime' => [
                'process__input_ontime:7',
                [[$T + 10, 1], [$T + 20, 0]],
                [[7, $D, 110], [7, $D, 110]],
                [$T, 100],
            ],
            'whinc_to_kwhd' => [
                'process__whinc_to_kwhd:7',
                [[$T, 500], [$next_day, 200]],
                [[7, $D, 1.5], [7, $next_day, 0.2]],
                [$T, 1.0],
            ],
            'accumulator' => [
                'process__accumulator:7',
                [[$T + 10, 5], [$T + 20, 2]],
                [[7, $T + 10, 15], [7, $T + 20, 17]],
                [$T, 10],
            ],
            'max_value' => [
                'process__max_value:7',
                [[$T + 10, 60], [$T + 20, 40], [$next_day, 10]],
                [[7, $D, 60], [7, $next_day, 10]],
                [$T, 50],
            ],
            'min_value' => [
                'process__min_value:7',
                [[$T + 10, 40], [$T + 20, 60], [$next_day, 90]],
                [[7, $D, 40], [7, $next_day, 90]],
                [$T, 50],
            ],
            'pulse_diff' => [
                'process__pulse_diff:7,process__log_to_feed:' . self::OUT,
                [[$T + 10, 110], [$T + 20, 50], [$T + 30, 0]],
                [[7, $T + 10, 110], [self::OUT, $T + 10, 10], [7, $T + 20, 50], [self::OUT, $T + 20, 50], [self::OUT, $T + 30, null]],
                [$T, 100],
            ],
        ];
    }

    /**
     * @dataProvider feed_state_cases
     */
    public function test_feed_state_process(string $processlist, array $samples, array $expected, ?array $seed): void
    {
        if ($seed !== null) {
            $this->feed->seed(7, $seed[0], $seed[1]);
        }
        $this->run_list($processlist, $samples);

        $this->assertEqualsWithDelta($expected, $this->feed->posts, 1e-9);
    }

    // -------------------------------------------------------------------------
    // Redis state processes
    // -------------------------------------------------------------------------

    /**
     * [process list, [time, value] samples, expected [feedid, time, value] posts, expected return values, seeded feed 7]
     */
    public static function redis_state_cases(): array
    {
        $D = self::D;
        $T = self::T;

        return [
            'kwh_to_power' => [
                'process__kwh_to_power:7',
                [[$T, 1.0], [$T + 10, 1.001]],
                [[7, $T + 10, 360]],
                [0, 360],
                null,
            ],
            'wh_accumulator' => [
                'process__wh_accumulator:7',
                [[$T, 500], [$T + 10, 510], [$T + 20, 5], [$T + 30, 10000]],
                [[7, $T + 10, 1010], [7, $T + 20, 1010], [7, $T + 30, 1010]],
                [500, 1010, 1010, 1010],
                [$T, 1000],
            ],
            'kwh_accumulator' => [
                'process__kwh_accumulator:7',
                [[$T, 50], [$T + 10, 50.01], [$T + 20, 1]],
                [[7, $T + 10, 10.01], [7, $T + 20, 10.01]],
                [50, 10.01, 10.01],
                [$T, 10],
            ],
            'kwh_to_kwhd' => [
                'process__kwh_to_kwhd:7',
                [[$T, 100], [$T + 10, 100.5], [$T + 20, 100.2]],
                [[7, $D, 2.5], [7, $D, 2.5]],
                [100, 100.5, 100.5],
                [$T, 2.0],
            ],
        ];
    }

    /**
     * @dataProvider redis_state_cases
     */
    public function test_redis_state_process(string $processlist, array $samples, array $expected_posts, array $expected_out, ?array $seed): void
    {
        if ($seed !== null) {
            $this->feed->seed(7, $seed[0], $seed[1]);
        }
        $out = $this->run_list($processlist, $samples);

        $this->assertEqualsWithDelta($expected_posts, $this->feed->posts, 1e-9);
        $this->assertEqualsWithDelta($expected_out, $out, 1e-9);
    }

    public function test_ratechange(): void
    {
        // First call without a stored value returns an undefined variable, so seed it
        $this->redis->hMset('process:ratechange:7', ['time' => self::T, 'value' => 100]);

        $out = $this->run_list('process__ratechange:7', [[self::T + 10, 130], [self::T + 20, 125]]);

        $this->assertEquals([[7, self::T + 10, 30], [7, self::T + 20, -5]], $this->feed->posts);
        $this->assertEquals([30, -5], $out);
    }

    public function test_redis_processes_pass_through_without_redis(): void
    {
        $GLOBALS['redis'] = null;
        $this->feed->seed(7, self::T, 1);

        foreach (['ratechange', 'kwh_to_power', 'wh_accumulator', 'kwh_accumulator', 'kwh_to_kwhd'] as $fn) {
            $this->assertSame([5], $this->run_list("process__$fn:7", [[self::T + 10, 5]]), $fn);
        }
        $this->assertSame([], $this->feed->posts);
    }

    public function test_publish_to_mqtt_enabled(): void
    {
        // Enabled without the Mosquitto extension, as in a web request
        $GLOBALS['settings']['mqtt'] = ['enabled' => true];
        $this->process = new Process($this->stub_mysqli(true), $this->input, $this->feed, 'UTC');

        $this->assertSame([10], $this->run_list('process__publish_to_mqtt:emon/test', [[self::T, 10]]));
        $this->assertSame(['emon/test' => 10], $this->redis->hashes['publish_to_mqtt']);

        $GLOBALS['redis'] = null;
        $this->assertSame([12], $this->run_list('process__publish_to_mqtt:emon/test', [[self::T + 10, 12]]));
    }

    // -------------------------------------------------------------------------
    // validate_processlist
    // -------------------------------------------------------------------------

    private function validate(array $list, int $context = 0, bool $access = true): array
    {
        $process = new Process($this->stub_mysqli($access), $this->input, $this->feed, 'UTC');
        return $process->validate_processlist(1, 1, json_encode($list), $context);
    }

    public function test_validate_encodes_valid_list(): void
    {
        $result = $this->validate([
            ['fn' => 'process__scale', 'args' => ['2']],
            ['fn' => 'process__log_to_feed', 'args' => ['5']],
            ['fn' => 'process__power_to_kwh_custom', 'args' => ['15', '7']],
        ]);

        $this->assertSame(['success' => true, 'processlist' => '2:2,1:5,71:15:7'], $result);
    }

    public static function invalid_list_cases(): array
    {
        return [
            'missing fn' => [[['args' => []]], 'Missing process key'],
            'missing args' => [[['fn' => 'process__scale']], 'Invalid or missing args'],
            'unknown fn' => [[['fn' => 'process__missing', 'args' => []]], 'Invalid process process key'],
            'wrong arg count' => [[['fn' => 'process__scale', 'args' => []]], 'Invalid number of arguments for process: process__scale'],
            'non numeric value' => [[['fn' => 'process__scale', 'args' => ['x']]], 'Value is not numeric'],
            'bad text' => [[['fn' => 'process__publish_to_mqtt', 'args' => ['a;b']]], 'Invalid characters'],
        ];
    }

    /**
     * @dataProvider invalid_list_cases
     */
    public function test_validate_rejects_invalid_list(array $list, string $message): void
    {
        $result = $this->validate($list);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString($message, $result['message']);
    }

    public function test_validate_rejects_feed_without_access(): void
    {
        $result = $this->validate([['fn' => 'process__log_to_feed', 'args' => ['5']]], 0, false);
        $this->assertSame(['success' => false, 'message' => 'Invalid feed'], $result);
    }

    public function test_validate_rejects_input_only_process_for_virtual_feed(): void
    {
        $result = $this->validate([['fn' => 'process__log_to_feed', 'args' => ['5']]], 1);
        $this->assertFalse($result['success']);
    }

    public function test_validate_rejects_non_array_json(): void
    {
        $this->assertFalse($this->process->validate_processlist(1, 1, '5', 0)['success']);
        $this->assertFalse($this->process->validate_processlist(1, 1, '{bad', 0)['success']);
    }

    // -------------------------------------------------------------------------
    // Encoding and references
    // -------------------------------------------------------------------------

    public function test_decode_and_encode_round_trip(): void
    {
        $decoded = $this->process->decode_processlist('process__scale:2,1:5,71:15:7,process__missing:1');

        $this->assertSame([
            ['fn' => 'process__scale', 'args' => ['2']],
            ['fn' => 'process__log_to_feed', 'args' => ['5']],
            ['fn' => 'process__power_to_kwh_custom', 'args' => ['15', '7']],
        ], $decoded);
        $this->assertSame('2:2,1:5,71:15:7', $this->process->encode_processlist($decoded));
    }

    public function test_referenced_entities(): void
    {
        $refs = $this->process->get_referenced_entities('2:2,1:5,11:3,1:5,process__power_to_kwh_custom:15:8');

        $this->assertSame(['inputs' => [3], 'feeds' => [5, 8]], $refs);
    }

    public function test_referenced_entities_unknown_process(): void
    {
        $this->assertFalse($this->process->get_referenced_entities('process__missing:1'));
    }
}
