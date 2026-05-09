<?php
/**
 * sysinfo.php
 * Compiles Emoncms-style system information JSON.
 * Usage: include this file and call get_system_info(), or hit it directly
 *        for a JSON response.
 */

// ── Services ─────────────────────────────────────────────────────────────────

function get_service_status(string $name): array
{
    $props = ['LoadState', 'ActiveState', 'SubState', 'UnitFileState'];
    $cmd   = 'systemctl show ' . escapeshellarg($name . '.service')
           . ' -p ' . implode(' -p ', $props) . ' 2>/dev/null';

    $raw = shell_exec($cmd) ?? '';
    $data = [];
    foreach (explode("\n", trim($raw)) as $line) {
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $data[$k] = $v;
    }

    $load    = $data['LoadState']    ?? 'not-found';
    $state   = $data['ActiveState']  ?? 'inactive';
    $sub     = $data['SubState']     ?? '';
    $unit    = $data['UnitFileState'] ?? '';

    $running = ($state === 'active' && $sub === 'running');

    if ($load === 'not-found' || $load === 'masked') {
        return [
            'loadstate'     => 'Not-found',
            'state'         => 'Inactive',
            'text'          => 'Not found or not installed',
            'running'       => false,
            'unitfilestate' => false,
            'cssClass'      => 'masked',
        ];
    }

    return [
        'loadstate'     => ucfirst($load),
        'state'         => ucfirst($state),
        'text'          => $running ? 'Running' : ucfirst($state),
        'running'       => $running,
        'unitfilestate' => $unit ?: false,
        'cssClass'      => $running ? 'success' : 'warning',
    ];
}

function get_services(): array
{
    $services = [
        'redis-server', 'emonhub', 'emoncms_mqtt', 'feedwriter',
        'service-runner', 'emonPiLCD', 'mosquitto', 'demandshaper', 'emoncms_sync',
    ];

    $out = [];
    foreach ($services as $svc) {
        $out[$svc] = get_service_status($svc);
    }
    return $out;
}

// ── Emoncms ──────────────────────────────────────────────────────────────────

function get_emoncms_info(): array
{
    $dir = defined('EMONCMS_ROOT') ? EMONCMS_ROOT : '/var/www/emoncms';

    // Version
    $version = 'unknown';
    if (file_exists("$dir/version.php")) {
        $content = file_get_contents("$dir/version.php");
        if (preg_match("/['\"](\d+\.\d+\.\d+)['\"]/", $content, $m)) {
            $version = $m[1];
        }
    }

    // Git info
    $git_url = $git_branch = $git_describe = '';
    if (is_dir("$dir/.git")) {
        $git_url     = trim(shell_exec("git -C " . escapeshellarg($dir) . " remote get-url origin 2>/dev/null") ?? '');
        $git_branch  = trim(shell_exec("git -C " . escapeshellarg($dir) . " rev-parse --abbrev-ref HEAD 2>/dev/null") ?? '');
        $git_describe= trim(shell_exec("git -C " . escapeshellarg($dir) . " describe --tags 2>/dev/null") ?? '');
    }

    return [
        'Version'      => $version,
        'Git URL'      => $git_url,
        'Git Branch'   => $git_branch,
        'Git Describe' => $git_describe,
    ];
}

// ── Server ───────────────────────────────────────────────────────────────────

function read_dmi(string $field): string
{
    $path = "/sys/class/dmi/id/$field";
    return file_exists($path) ? trim(file_get_contents($path)) : '';
}

function get_server_info(): array
{
    $product  = read_dmi('product_name');
    $board    = read_dmi('board_name');
    $bios_ver = read_dmi('bios_version');
    $bios_date= read_dmi('bios_date');
    $machine  = trim("$product/$board, BIOS $bios_ver $bios_date");

    // CPU
    $cpuinfo = file_get_contents('/proc/cpuinfo') ?: '';
    preg_match('/^model name\s*:\s*(.+)$/m', $cpuinfo, $m);
    $cpu_model   = trim($m[1] ?? 'Unknown');
    $cpu_threads = substr_count($cpuinfo, 'processor	:');
    preg_match('/^cpu cores\s*:\s*(\d+)/m', $cpuinfo, $m);
    $cpu_cores   = (int)($m[1] ?? 0);
    preg_match_all('/^physical id\s*:\s*\d+/m', $cpuinfo, $m);
    $cpu_sockets = count(array_unique($m[0])) ?: 1;
    preg_match_all('/^bogomips\s*:\s*([\d.]+)/m', $cpuinfo, $m);
    $cpu_mips = $m[1] ? number_format(array_sum($m[1]) / count($m[1]), 2) . 'MIPS' : '';

    $cpu_str = "$cpu_model | {$cpu_threads} Threads(s) | {$cpu_cores} Core(s) | {$cpu_sockets} Sockets(s) | $cpu_mips";

    $uname   = php_uname('s') . ' ' . php_uname('r');
    $host    = gethostname();
    $ip      = gethostbyname($host) ?: '127.0.0.1';
    $date    = gmdate('Y-m-d H:i:s') . ' UTC';
    $uptime  = trim(shell_exec('uptime') ?? '');

    return [
        'Machine' => $machine,
        'CPU'     => $cpu_str,
        'OS'      => $uname,
        'Host'    => "$host | $host | ($ip)",
        'Date'    => $date,
        'Uptime'  => $uptime,
    ];
}

// ── Memory ───────────────────────────────────────────────────────────────────

function parse_meminfo(): array
{
    $raw = file_get_contents('/proc/meminfo') ?: '';
    $mem = [];
    foreach (explode("\n", $raw) as $line) {
        if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
            $mem[$m[1]] = (int)$m[2] * 1024; // kB → bytes
        }
    }
    return $mem;
}

function format_bytes(int $bytes, int $decimals = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, $decimals) . ' ' . $units[$i];
}

function get_memory_info(): array
{
    $m = parse_meminfo();

    $ram_total = $m['MemTotal']     ?? 0;
    $ram_avail = $m['MemAvailable'] ?? ($m['MemFree'] ?? 0);
    $ram_used  = $ram_total - $ram_avail;
    $ram_free  = $ram_avail;

    $swap_total = $m['SwapTotal'] ?? 0;
    $swap_free  = $m['SwapFree']  ?? 0;
    $swap_used  = $swap_total - $swap_free;

    return [
        'RAM' => [
            'Used'       => number_format(($ram_used / max($ram_total, 1)) * 100, 2) . '%',
            'Total'      => format_bytes($ram_total),
            'Used Value' => format_bytes($ram_used),
            'Free'       => format_bytes($ram_free),
        ],
        'Swap' => [
            'Used'       => number_format(($swap_used / max($swap_total, 1)) * 100, 2) . '%',
            'Total'      => format_bytes($swap_total),
            'Used Value' => $swap_used > 0 ? format_bytes($swap_used) : '0 B',
            'Free'       => format_bytes($swap_free),
        ],
    ];
}

// ── Disk ─────────────────────────────────────────────────────────────────────

function get_disk_info(): array
{
    $mounts = ['/', '/boot/efi', '/sys/firmware/efi/efivars'];
    $out    = [];

    foreach ($mounts as $mount) {
        if (!is_dir($mount)) continue;

        $total = disk_total_space($mount);
        $free  = disk_free_space($mount);
        if ($total === false || $free === false) continue;

        $used     = $total - $free;
        $used_pct = number_format(($used / max($total, 1)) * 100, 2) . '%';

        $out[$mount] = [
            'Used'        => $used_pct,
            'Total'       => format_bytes((int)$total),
            'Used Value'  => format_bytes((int)$used),
            'Free'        => format_bytes((int)$free),
            'Read Load'   => 'n/a',
            'Write Load'  => 'n/a',
            'Load Time'   => 'n/a',
        ];
    }

    return $out;
}

// ── HTTP ─────────────────────────────────────────────────────────────────────

function get_http_info(): array
{
    $server = $_SERVER['SERVER_SOFTWARE'] ?? (trim(shell_exec('apache2 -v 2>/dev/null | head -1') ?? ''));
    return ['Server' => $server];
}

// ── MySQL / MariaDB ───────────────────────────────────────────────────────────

function get_mysql_info(): array
{
    // Requires a mysqli connection; gracefully degrades if unavailable.
    $host = DB_SERVER ?? 'localhost';
    $user = DB_USER   ?? 'root';
    $pass = DB_PASS   ?? '';
    $db   = DB_NAME   ?? '';

    $info = [
        'Version' => 'unknown',
        'Host'    => "$host (127.0.0.1)",
        'Date'    => gmdate('Y-m-d H:i:s') . ' (UTC ' . date('P') . ')',
        'Stats'   => 'unavailable',
    ];

    if (!function_exists('mysqli_connect')) return $info;

    $conn = @mysqli_connect($host, $user, $pass, $db);
    if (!$conn) return $info;

    $info['Version'] = mysqli_get_server_info($conn);

    // SHOW STATUS
    $status = [];
    $result = mysqli_query($conn, 'SHOW STATUS');
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            $status[$row[0]] = $row[1];
        }
        mysqli_free_result($result);
    }

    $uptime  = (int)($status['Uptime']               ?? 0);
    $threads = (int)($status['Threads_connected']     ?? 0);
    $questions=(int)($status['Questions']             ?? 0);
    $slow    = (int)($status['Slow_queries']          ?? 0);
    $opens   = (int)($status['Opened_tables']         ?? 0);
    $open    = (int)($status['Open_tables']           ?? 0);
    $qps     = $uptime > 0 ? number_format($questions / $uptime, 3) : '0.000';

    $info['Stats'] = "Uptime: $uptime  Threads: $threads  Questions: $questions  "
                   . "Slow queries: $slow  Opens: $opens  Open tables: $open  "
                   . "Queries per second avg: $qps";

    mysqli_close($conn);
    return $info;
}

// ── PHP ───────────────────────────────────────────────────────────────────────

function get_php_info(): array
{
    $version = 'PHP ' . PHP_VERSION . ' (Zend Version ' . zend_version() . ')';
    $user    = 'User: ' . get_current_user() . ' Group: ' . (function_exists('posix_getgrgid')
        ? (posix_getgrgid(posix_getegid())['name'] ?? '')
        : '') . ' Script Owner: ' . (posix_getpwuid(fileowner(__FILE__))['name'] ?? '');

    $modules = get_loaded_extensions();
    sort($modules);
    $modules = array_map(fn($m) => $m . ' ', $modules); // trailing space matches Emoncms style

    return [
        'Version'    => $version,
        'Run user'   => $user,
        'Modules'    => $modules,
    ];
}

// ── Redis ─────────────────────────────────────────────────────────────────────

function get_redis_info(): array
{
    $info = [
        'Redis Server' => 'unknown',
        'PHP Redis'    => defined('Redis::VERSION') ? Redis::VERSION : (phpversion('redis') ?: ''),
        'Python Redis' => '',
        'Host'         => 'localhost:6379',
        'Size'         => '0 keys',
        'Uptime'       => '0 days',
    ];

    if (!class_exists('Redis')) return $info;

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 1.0);

        $server = $redis->info('server');
        $info['Redis Server'] = $server['redis_version'] ?? 'unknown';

        $uptime_secs = (int)($server['uptime_in_seconds'] ?? 0);
        $info['Uptime'] = intdiv($uptime_secs, 86400) . ' days';

        $mem  = $redis->info('memory');
        $used = $mem['used_memory_human'] ?? '0B';

        $keys = $redis->dbSize();
        $info['Size'] = "$keys keys ($used)";

    } catch (Exception $e) {
        // Redis unavailable — defaults already set
    }

    return $info;
}

// ── Client ───────────────────────────────────────────────────────────────────

function get_client_info(): array
{
    $ip      = $_SERVER['REMOTE_ADDR']          ?? '127.0.0.1';
    $fwd     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    $agent   = $_SERVER['HTTP_USER_AGENT']      ?? '';
    $host    = $_SERVER['REMOTE_HOST']          ?? gethostbyaddr($ip) ?? 'localhost';

    return [
        'Browser'      => $agent,
        'IP'           => $ip,
        'Forwarded IP' => $fwd,
        'Client Hostname' => $host,
    ];
}

// ── Assemble ─────────────────────────────────────────────────────────────────

function get_system_info(): array
{
    return [
        'Services'           => get_services(),
        'System Information' => [
            'Emoncms' => get_emoncms_info(),
            'Server'  => get_server_info(),
            'Memory'  => get_memory_info(),
            'Disk'    => get_disk_info(),
            'HTTP'    => get_http_info(),
            'MySQL'   => get_mysql_info(),
            'PHP'     => get_php_info(),
            'Redis'   => get_redis_info(),
        ],
        'Client Information' => get_client_info(),
    ];
}

// ── Direct execution — output JSON ───────────────────────────────────────────

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode(get_system_info(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
