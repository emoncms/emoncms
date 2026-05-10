<?php

class SystemInfo
{

    private $mysqli;
    private $redis;
    private $settings;
    private $components_model_instance = null;

    public function __construct($mysqli = null, $redis = null, array $settings = [])
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->settings = $settings;
    }

    private function rootPath(): string
    {
        if (defined('EMONCMS_ROOT') && EMONCMS_ROOT) {
            return EMONCMS_ROOT;
        }
        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            return dirname($_SERVER['SCRIPT_FILENAME']);
        }
        return dirname(__DIR__, 3);
    }

    private function exec($cmd)
    {
        $output = false;
        if (function_exists('exec')) {
            $output = @exec($cmd);
        }
        return $output;
    }

    private function exec_array($cmd)
    {
        $output = false;
        if (function_exists('exec')) {
            @exec($cmd, $output);
        }
        return $output;
    }

    private function lscpu()
    {
        return $this->exec_array('lscpu');
    }

    private function uptime()
    {
        return $this->exec('uptime');
    }

    private function whoami()
    {
        return $this->exec('whoami');
    }

    private function id_groups()
    {
        return $this->exec('id -Gn');
    }

    private function which($command)
    {
        return $this->exec('which ' . escapeshellarg($command) . ' 2>/dev/null');
    }

    // ── Emoncms ──────────────────────────────────────────────────────────────

    private function components_model()
    {
        if ($this->components_model_instance === null) {
            if (empty($this->settings)) {
                return null;
            }
            require_once "Modules/admin/components/ComponentsModel.php";
            $this->components_model_instance = new ComponentsModel($this->settings, $this->redis);
        }
        return $this->components_model_instance;
    }

    private function component_list($git_info = true)
    {
        $components_model = $this->components_model();
        if (!$components_model) {
            return array();
        }
        return $components_model->component_list($git_info);
    }

    private function getEmoncmsInfo(): array
    {
        $dir = $this->rootPath();

        $version = $GLOBALS['emoncms_version'] ?? 'unknown';
        if ($version === 'unknown' && file_exists($dir . '/version.json')) {
            $json = json_decode(file_get_contents($dir . '/version.json'), true);
            if (!empty($json['version'])) {
                $version = $json['version'];
            }
        }

        $git_url = $git_branch = $git_describe = '';
        if (is_dir("$dir/.git")) {
            $git_url = trim(shell_exec("git -C " . escapeshellarg($dir) . " ls-remote --get-url origin 2>/dev/null") ?? '');
            $git_branch = trim(shell_exec("git -C " . escapeshellarg($dir) . " branch --contains HEAD 2>/dev/null") ?? '');
            $git_describe = trim(shell_exec("git -C " . escapeshellarg($dir) . " describe 2>/dev/null") ?? '');
        }

        $component_summary = array();
        foreach ($this->component_list(false) as $component) {
            $component_summary[] = $component['name'] . ' v' . $component['version'];
        }

        return [
            'Version' => $version,
            'Git URL' => $git_url,
            'Git Branch' => $git_branch,
            'Git Describe' => $git_describe,
            'Components' => implode(' | ', $component_summary),
        ];
    }

    // ── Server ───────────────────────────────────────────────────────────────

    private function readDmi(string $field): string
    {
        foreach (array("/sys/devices/virtual/dmi/id/$field", "/sys/class/dmi/id/$field") as $path) {
            if (file_exists($path)) {
                return trim((string)file_get_contents($path));
            }
        }
        return '';
    }

    private function getServerInfo(): array
    {
        @list($os_name, $host, $kernel) = preg_split('/[\s,]+/', php_uname('a'), 5);
        $machine = $this->get_machine();

        $cpuinfo = '';
        if (@is_readable('/usr/bin/lscpu')) {
            $data = $this->lscpu() ?: array();
            foreach ($data as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $val) = explode(':', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    if ($key === 'Socket(s)') {
                        $cpuinfo .= $val . ' Sockets(s) | ';
                    }
                    if ($key === 'Core(s) per socket') {
                        $cpuinfo .= $val . ' Core(s) | ';
                    }
                    if ($key === 'Thread(s) per core') {
                        $cpuinfo .= $val . ' Threads(s) | ';
                    }
                    if ($key === 'Model name') {
                        $cpuinfo .= $val . ' | ';
                    }
                    if ($key === 'CPU MHz') {
                        $cpuinfo .= $val . 'MHz | ';
                    }
                    if ($key === 'BogoMIPS') {
                        $cpuinfo .= $val . 'MIPS | ';
                    }
                }
            }
        }

        $server_addr = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        $host_text = $host . ' | ' . @gethostbyaddr(gethostbyname($host)) . ' | (' . $server_addr . ')';

        return [
            'Machine' => $machine,
            'CPU' => $cpuinfo,
            'OS' => $os_name . ' ' . $kernel,
            'Host' => $host_text,
            'Date' => date('Y-m-d H:i:s T'),
            'Uptime' => $this->uptime(),
        ];
    }

    // ── Memory ───────────────────────────────────────────────────────────────

    private function parseMeminfo(): array
    {
        $raw = file_get_contents('/proc/meminfo') ?: '';
        $mem = [];
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $mem[$m[1]] = (int)$m[2] * 1024; // kB -> bytes
            }
        }
        return $mem;
    }

    private function formatBytes(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, $decimals) . ' ' . $units[$i];
    }

    private function formatSize($bytes)
    {
        return $this->formatBytes((int)$bytes);
    }

    private function getMemoryInfo(): array
    {
        $m = $this->parseMeminfo();

        $ram_total = $m['MemTotal'] ?? 0;
        $ram_used = $ram_total - ($m['MemFree'] ?? 0) - ($m['Buffers'] ?? 0) - ($m['Cached'] ?? 0);
        $ram_free = $ram_total - $ram_used;

        $swap_total = $m['SwapTotal'] ?? 0;
        $swap_free = $m['SwapFree'] ?? 0;
        $swap_used = $swap_total - $swap_free;
        $ram_percent = $ram_total > 0 ? ($ram_used / $ram_total) * 100 : 0;
        $swap_percent = $swap_total > 0 ? ($swap_used / $swap_total) * 100 : 0;

        return [
            'RAM' => [
                'Used' => sprintf('%.2f', $ram_percent) . '%',
                'Total' => $this->formatBytes($ram_total),
                'Used Value' => $this->formatBytes($ram_used),
                'Free' => $this->formatBytes($ram_free),
            ],
            'Swap' => [
                'Used' => $swap_total > 0 ? sprintf('%.2f', $swap_percent) . '%' : '0%',
                'Total' => $this->formatBytes($swap_total),
                'Used Value' => $swap_total > 0 ? $this->formatBytes($swap_used) : '',
                'Free' => $this->formatBytes($swap_free),
            ],
        ];
    }

    // ── Disk ─────────────────────────────────────────────────────────────────

    private function getDiskInfo(): array
    {
        $partitions = $this->disk_list();
        $disk_info = $this->get_mountpoints($partitions);
        $out = array();

        foreach ($disk_info as $disk) {
            $out[$disk['mountpoint']] = array(
                'Used' => $disk['percent'] . '%',
                'Total' => $disk['total'],
                'Used Value' => $disk['used'],
                'Free' => $disk['free'],
                'Read Load' => $disk['readload'],
                'Write Load' => $disk['writeload'],
                'Load Time' => $disk['statsloadtime'],
            );
        }

        return $out;
    }

    // ── HTTP ─────────────────────────────────────────────────────────────────

    private function getHttpInfo(): array
    {
        $server = ($_SERVER['SERVER_SOFTWARE'] ?? '') . ' ' . ($_SERVER['SERVER_PROTOCOL'] ?? '') . ' ' . ($_SERVER['GATEWAY_INTERFACE'] ?? '') . ' ' . ($_SERVER['SERVER_PORT'] ?? '');
        return ['Server' => $server];
    }

    // ── MySQL / MariaDB ─────────────────────────────────────────────────────

    private function getMysqlInfo(): array
    {
        $host = $this->settings['sql']['server'] ?? (isset($this->mysqli->host_info) ? $this->mysqli->host_info : 'localhost');

        $info = array(
            'Version' => isset($this->mysqli->server_info) ? $this->mysqli->server_info : 'unknown',
            'Host' => $host . ' (' . gethostbyname($host) . ')',
            'Date' => '',
            'Stats' => '',
        );

        if ($this->mysqli instanceof mysqli) {
            $result = $this->mysqli->query("select now() as datetime, time_format(timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00')),'%H:%i') AS timezone");
            if ($result) {
                $db = $result->fetch_array();
                $info['Date'] = $db['datetime'] . ' (UTC ' . $db['timezone'] . ')';
            } else {
                $info['Date'] = gmdate('Y-m-d H:i:s') . ' (UTC ' . date('P') . ')';
            }

            $status = array();
            $result = mysqli_query($this->mysqli, 'SHOW STATUS');
            if ($result) {
                while ($row = mysqli_fetch_row($result)) {
                    $status[$row[0]] = $row[1];
                }
                mysqli_free_result($result);
            }

            $uptime = (int)($status['Uptime'] ?? 0);
            $threads = (int)($status['Threads_connected'] ?? 0);
            $questions = (int)($status['Questions'] ?? 0);
            $slow = (int)($status['Slow_queries'] ?? 0);
            $opens = (int)($status['Opened_tables'] ?? 0);
            $open = (int)($status['Open_tables'] ?? 0);
            $qps = $uptime > 0 ? number_format($questions / $uptime, 3) : '0.000';

            $info['Stats'] = 'Uptime: ' . $uptime . '  Threads: ' . $threads . '  Questions: ' . $questions . '  '
                           . 'Slow queries: ' . $slow . '  Opens: ' . $opens . '  Open tables: ' . $open . '  '
                           . 'Queries per second avg: ' . $qps;
        } else {
            $info['Date'] = gmdate('Y-m-d H:i:s') . ' UTC';
            $info['Stats'] = 'unavailable';
        }

        return $info;
    }

    // ── PHP ──────────────────────────────────────────────────────────────────

    private function getPhpInfo(): array
    {
        $php_version = PHP_VERSION;
        $modules = $this->php_modules(get_loaded_extensions());

        return [
            'Version' => $php_version . ' (Zend Version ' . (function_exists('zend_version') ? zend_version() : 'n/a') . ')',
            'Run user' => 'User: ' . $this->whoami() . ' Group: ' . $this->id_groups() . ' Script Owner: ' . (function_exists('get_current_user') ? get_current_user() : 'n/a'),
            'Modules' => array_map(function ($module) use ($php_version) {
                return str_replace('v' . $php_version, '', $module);
            }, $modules),
        ];
    }

    // ── Redis ────────────────────────────────────────────────────────────────

    private function getRedisInfo(): array
    {
        $info = array(
            'Redis Server' => '',
            'PHP Redis' => phpversion('redis') ?: '',
            'Python Redis' => '',
            'Host' => ($this->settings['redis']['host'] ?? 'localhost') . ':' . ($this->settings['redis']['port'] ?? '6379'),
            'Size' => '0 keys ()',
            'Uptime' => '0 days',
        );

        if ($this->redis) {
            try {
                $redis_info = $this->redis->info();
                $info['Redis Server'] = isset($redis_info['redis_version']) ? $redis_info['redis_version'] : '';
                $info['PHP Redis'] = phpversion('redis') ?: $info['PHP Redis'];
                $info['Size'] = (isset($redis_info['dbSize']) ? $redis_info['dbSize'] : $this->redis->dbSize()) . ' keys (' . (isset($redis_info['used_memory_human']) ? $redis_info['used_memory_human'] : '') . ')';
                $info['Uptime'] = (isset($redis_info['uptime_in_days']) ? $redis_info['uptime_in_days'] : 0) . ' days';
            } catch (Exception $e) {
                // Keep defaults if Redis is unavailable.
            }
        }

        return $info;
    }

    // ── MQTT ─────────────────────────────────────────────────────────────────

    private function mqtt_version()
    {
        $v = '?';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $v = 'n/a';
        } else {
            if (@file_exists('/usr/sbin/mosquitto')) {
                if (file_exists('/.dockerenv')) {
                    $v = $this->mosquitto_version_docker();
                } else {
                    $v = $this->mosquitto_version();
                }
            }
        }
        return $v;
    }

    private function getMqttInfo(): array
    {
        if (empty($this->settings['mqtt']['enabled'])) {
            return array();
        }

        $host = $this->settings['mqtt']['host'] ?? 'localhost';
        $port = $this->settings['mqtt']['port'] ?? '1883';

        return array(
            'Version' => 'Mosquitto ' . $this->mqtt_version(),
            'Host' => $host . ':' . $port . ' (' . gethostbyname($host) . ')',
        );
    }

    // ── Pi ───────────────────────────────────────────────────────────────────

    private function is_Pi()
    {
        if (@file_exists('/proc/device-tree/model')) {
            $model = @file_get_contents('/proc/device-tree/model');
            if (stripos($model, 'Raspberry Pi') !== false) {
                return true;
            }
        }
        if (@is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if (preg_match('/^Hardware\s*:\s*BCM\d+/mi', $cpuinfo)) {
                return true;
            }
            if (preg_match('/^Model\s*:\s*Raspberry Pi/mi', $cpuinfo)) {
                return true;
            }
        }
        return false;
    }

    private function get_rpi_info()
    {
        $rpi_info = array_map(function () {
            return '';
        }, array_flip(explode(',', 'rev,sn,model,emonpiRelease,cputemp,gputemp,currentfs')));

        if (!$this->is_Pi()) {
            return $rpi_info;
        }

        $rpi_info['model'] = 'Unknown';
        if (@is_readable('/proc/cpuinfo')) {
            $rpi_revision = array();
            if (@is_readable(__DIR__ . '/pi-model.json')) {
                $rpi_revision = json_decode(file_get_contents(__DIR__ . '/pi-model.json'), true);
                foreach ($rpi_revision as $k => $rev) {
                    if (empty($rev['Code'])) {
                        continue;
                    }
                    $rpi_revision[$rev['Code']] = $rev;
                    unset($rpi_revision[$k]);
                }
            }

            preg_match_all('/^(revision|serial|hardware)\s*: (.*)/mi', file_get_contents('/proc/cpuinfo'), $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $key) {
                    $key = strtolower($key);
                    if ($key === 'revision') {
                        $rpi_info['rev'] = $matches[2][$index];
                    } elseif ($key === 'serial') {
                        $rpi_info['sn'] = $matches[2][$index];
                    } elseif ($key === 'hardware') {
                        $rpi_info['model'] = $matches[2][$index];
                    }
                }
            }

            $empty_model = array_map(function () {
                return '';
            }, array_flip(explode(',', 'Model,Revision,RAM,Manufacturer')));
            $model_info = !empty($rpi_revision[$rpi_info['rev']]) ? $rpi_revision[$rpi_info['rev']] : $empty_model;
            $model = !empty($model_info['Model']) ? $model_info['Model'] : '';
            if (!empty($model)) {
                $rpi_info['model'] = 'Raspberry Pi ';
                if (ctype_digit($model[0])) {
                    $ver = $model[0];
                    $model = substr($model, 1);
                    $rpi_info['model'] .= $ver . ' Model ' . $model;
                } elseif (substr($model, 0, 2) == 'CM') {
                    $rpi_info['model'] .= ' Compute Module';
                    if (isset($model[2]) && ctype_digit($model[2]) && $model[2] > 1) {
                        $rpi_info['model'] .= ' ' . $model[2];
                    }
                } else {
                    $rpi_info['model'] .= ' Model ' . $model;
                }
                $rpi_info['model'] .= ' Rev ' . $model_info['Revision'] . ' - ' . $model_info['RAM'] . ' (' . $model_info['Manufacturer'] . ')';
            }

            $vcgencmd_known_paths = array('/usr/bin/vcgencmd', '/usr/local/bin/vcgencmd', '/opt/vc/bin/vcgencmd');
            $vcgencmd_which = trim((string)$this->which_vcgencmd());
            $vcgencmd_path = in_array($vcgencmd_which, $vcgencmd_known_paths, true) ? $vcgencmd_which : '/opt/vc/bin/vcgencmd';
            $rpi_info['gputemp'] = $this->gpu_temp($vcgencmd_path);

            if (strpos($rpi_info['gputemp'], 'temp=') !== false) {
                $rpi_info['gputemp'] = str_replace('temp=', '', $rpi_info['gputemp']);
                $rpi_info['gputemp'] = str_replace("'C", '°C', $rpi_info['gputemp']);
            } else {
                $rpi_info['gputemp'] = 'N/A';
                $rpi_info['gputemp'] .= tr(' (to show GPU temp execute this command from the console "sudo usermod -G video www-data" )');
            }

            if (glob('/boot/emonSD-*')) {
                foreach (glob('/boot/emonSD-*') as $emonpiRelease) {
                    $rpi_info['emonpiRelease'] = str_replace('/boot/', '', $emonpiRelease);
                }
            }
        }

        $rpi_info['currentfs'] = $this->get_fs_state();
        return $rpi_info;
    }

    private function get_machine()
    {
        $vendor = trim((string)$this->readDmi('board_vendor'));
        $product = trim((string)$this->readDmi('product_name'));
        $board = trim((string)$this->readDmi('board_name'));
        $bios = trim(trim((string)$this->readDmi('bios_version')) . ' ' . trim((string)$this->readDmi('bios_date')));

        $machine = $vendor;
        if ($product !== '') {
            $machine .= ' ' . $product;
        }
        if ($board !== '') {
            $machine .= '/' . $board;
        }
        if ($bios !== '') {
            $machine .= ', BIOS ' . $bios;
        }
        if ($machine === '') {
            return '';
        }

        $junk = '/ ?(To be filled by O\.E\.M\.|System manufacturer|System Product Name|Not Specified|Default string) ?/i';
        return trim(preg_replace('/^\/,?/', '', preg_replace($junk, '', $machine)));
    }

    private function get_fs_state()
    {
        $currentfs = 'read-only';
        $mount_result = $this->mount();
        $matches = null;
        preg_match('/^\/dev\/mmcblk0p2 on \/ .*(\(rw).*/mi', implode("\n", $mount_result), $matches);
        if (!empty($matches)) {
            $currentfs = 'read-write';
        }
        if (!$this->is_Pi()) {
            $currentfs = '?';
        }
        return $currentfs;
    }

    private function disk_list()
    {
        $in_docker = file_exists('/.dockerenv');

        if ($in_docker && file_exists('/opt/openenergymonitor/emoncms_pre.sh')) {
            $output = $this->df_data() ?: array();
        } else {
            $output = $this->df();
            if (!$output) {
                return array();
            }
        }

        $partitions = array();
        foreach ($output as $line) {
            $columns = array_values(array_filter(array_map('trim', explode(' ', $line))));
            if (count($columns) !== 6) {
                continue;
            }

            $filesystem = $columns[0];
            $partition = $columns[5];

            $partitions[$partition]['Temporary']['bool'] = in_array($filesystem, array('tmpfs', 'devtmpfs'), true);
            $partitions[$partition]['Partition']['text'] = $partition;
            $partitions[$partition]['FileSystem']['text'] = $filesystem;

            if (is_numeric($columns[1]) && is_numeric($columns[2]) && is_numeric($columns[3])) {
                $partitions[$partition]['Size']['value'] = $columns[1];
                $partitions[$partition]['Used']['value'] = $columns[2];
                $partitions[$partition]['Free']['value'] = $columns[3];
            } else {
                $partitions[$partition]['Size']['text'] = $columns[1];
                $partitions[$partition]['Used']['text'] = $columns[2];
                $partitions[$partition]['Free']['text'] = $columns[3];
            }

            [$partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime] = $this->resolve_disk_stats($filesystem, $partition, $in_docker);

            if ($this->redis && $partition_name) {
                [$readload, $writeload, $loadtime] = $this->redis_disk_load($partition_name, $bytes_read, $bytes_written);
            }

            $partitions[$partition]['ReadLoad']['value'] = $readload;
            $partitions[$partition]['WriteLoad']['value'] = $writeload;
            $partitions[$partition]['LoadTime']['value'] = $loadtime;
        }

        return $partitions;
    }

    private function get_mountpoints($partitions)
    {
        $mounts = array();
        if (count($partitions) > 0) {
            foreach ($partitions as $fs) {
                if (!$fs['Temporary']['bool'] && $fs['FileSystem']['text'] != 'none' && $fs['FileSystem']['text'] != 'udev') {
                    $diskFree = $fs['Free']['value'];
                    $diskTotal = $fs['Size']['value'];
                    $diskUsed = $fs['Used']['value'];
                    $readLoad = $fs['ReadLoad']['value'];
                    $writeLoad = $fs['WriteLoad']['value'];
                    $loadTime = $fs['LoadTime']['value'];
                    $diskPercentRaw = ($diskUsed / $diskTotal) * 100;
                    $diskPercent = sprintf('%.2f', $diskPercentRaw);
                    $diskPercentTable = number_format(round($diskPercentRaw, 2), 2, '.', '');
                    if (strlen($fs['Partition']['text']) > 30) {
                        $mountpoint = substr($fs['Partition']['text'], 0, 30) . '...';
                    } else {
                        $mountpoint = $fs['Partition']['text'];
                    }

                    $readloadstr = 'n/a';
                    $writeloadstr = 'n/a';
                    $loadstr = 'n/a';
                    if ($loadTime) {
                        $readloadstr = $this->formatSize($readLoad) . '/s';
                        $writeloadstr = $this->formatSize($writeLoad) . '/s';
                    }
                    if ($loadTime == -1) {
                        $loadstr = 'since boot';
                    } elseif ($loadTime > 0) {
                        $days = floor($loadTime / 86400);
                        $hours = floor(($loadTime - ($days * 86400)) / 3600);
                        $mins = floor(($loadTime - ($days * 86400) - ($hours * 3600)) / 60);
                        $loadstr = '';
                        if ($days) {
                            $loadstr .= $days . ' days ';
                        }
                        if ($hours) {
                            $loadstr .= $hours . ' hours ';
                        }
                        $loadstr .= $mins . ' mins';
                    }

                    $mounts[] = array(
                        'free' => $this->formatSize($diskFree),
                        'total' => $this->formatSize($diskTotal),
                        'used' => $this->formatSize($diskUsed),
                        'readload' => $readloadstr,
                        'writeload' => $writeloadstr,
                        'statsloadtime' => $loadstr,
                        'raw' => $diskPercentRaw,
                        'percent' => $diskPercent,
                        'table' => $diskPercentTable,
                        'mountpoint' => $mountpoint,
                    );
                }
            }
        }
        return $mounts;
    }

    private function resolve_disk_stats(string $filesystem, string $partition, bool $in_docker): array
    {
        $partition_name = false;
        $bytes_read = $bytes_written = $readload = $writeload = $loadtime = 0;

        if (!$in_docker && $this->is_command_available('iostat')) {
            $stats = $this->iostat($filesystem);
            if (isset($stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0])) {
                $disk = $stats['sysstat']['hosts'][0]['statistics'][0]['disk'][0];
                $partition_name = $disk['disk_device'];
                $readload = round($disk['kB_read/s'] * 1024);
                $writeload = round($disk['kB_wrtn/s'] * 1024);
                $bytes_read = round($disk['kB_read'] * 1024);
                $bytes_written = round($disk['kB_wrtn'] * 1024);
                $loadtime = -1;
                return array($partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime);
            }
        }

        $mount_map = array(
            '/boot' => 'mmcblk0p1',
            '/' => 'mmcblk0p2',
            '/var/opt/emoncms' => 'mmcblk0p3',
            '/home/pi/data' => 'mmcblk0p3',
        );
        $partition_name = $mount_map[$partition] ?? false;

        if ($in_docker) {
            $parts = explode('/', $filesystem);
            $partition_name = end($parts);
        }

        if ($partition_name && @is_readable('/proc/diskstats')) {
            $sectors_read = $sectors_written = null;
            foreach (explode("\n", file_get_contents('/proc/diskstats')) as $dline) {
                $dparts = preg_split('/\s+/', trim($dline));
                if (isset($dparts[2]) && $dparts[2] === $partition_name) {
                    $sectors_read = isset($dparts[5]) ? (int)$dparts[5] : null;
                    $sectors_written = isset($dparts[9]) ? (int)$dparts[9] : null;
                    break;
                }
            }
            if ($sectors_read === null || $sectors_written === null) {
                $partition_name = false;
            } else {
                $bytes_read = $sectors_read * 512;
                $bytes_written = $sectors_written * 512;
            }
        } elseif ($partition_name) {
            $partition_name = false;
        }

        return array($partition_name, $bytes_read, $bytes_written, $readload, $writeload, $loadtime);
    }

    private function redis_disk_load(string $partition_name, int $bytes_read, int $bytes_written): array
    {
        if ($this->redis->exists('diskstats:starttime') &&
            $this->redis->exists('diskstats:' . $partition_name . ':read') &&
            $this->redis->exists('diskstats:' . $partition_name . ':write')) {
            $last_bytes_read = $this->redis->get('diskstats:' . $partition_name . ':read');
            $last_bytes_written = $this->redis->get('diskstats:' . $partition_name . ':write');
            $elapsed = time() - $this->redis->get('diskstats:starttime');
            $readload = $elapsed > 0 ? ($bytes_read - $last_bytes_read) / $elapsed : 0;
            $writeload = $elapsed > 0 ? ($bytes_written - $last_bytes_written) / $elapsed : 0;
            return array($readload, $writeload, $elapsed);
        }

        $this->redis->set('diskstats:' . $partition_name . ':read', $bytes_read);
        $this->redis->set('diskstats:' . $partition_name . ':write', $bytes_written);
        $this->redis->set('diskstats:starttime', time());
        return array(0, 0, 0);
    }

    private function php_modules($_modules)
    {
        natcasesort($_modules);
        $modules = array();
        foreach ($_modules as $extension) {
            $module_version = phpversion($extension);
            $modules[] = $module_version ? $extension . ' v' . $module_version : $extension;
        }
        return $modules;
    }

    private function which_vcgencmd()
    {
        return $this->exec('which vcgencmd 2>/dev/null');
    }

    private function gpu_temp($vcgencmd_path)
    {
        return $this->exec(escapeshellarg($vcgencmd_path) . ' measure_temp');
    }

    private function mosquitto_version_docker()
    {
        return $this->exec('/usr/sbin/mosquitto -h | grep version');
    }

    private function mosquitto_version()
    {
        return $this->exec('/usr/sbin/mosquitto -h | grep -oP \'(?<=mosquitto\\sversion\\s)[0-9.]+(?=\\s*)\'');
    }

    private function mount()
    {
        return $this->exec_array('mount');
    }

    private function df()
    {
        return $this->exec_array('df -B 1 -x squashfs');
    }

    private function df_data()
    {
        return $this->exec_array('df -B 1 /data');
    }

    private function iostat($filesystem)
    {
        ob_start();
        @passthru('iostat -o JSON -k ' . escapeshellarg($filesystem) . ' 2>/dev/null');
        $output = trim(ob_get_clean());
        return !empty($output) ? json_decode($output, true) : null;
    }

    private function is_command_available($command)
    {
        $result = $this->which($command);
        return !empty(trim($result));
    }

    private function get_client_info(): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $host = $_SERVER['REMOTE_HOST'] ?? gethostbyaddr($ip) ?? 'localhost';

        return array(
            'Browser' => $agent,
            'IP' => $ip,
            'Forwarded IP' => $fwd,
            'Client Hostname' => $host,
        );
    }

    // ── Client ───────────────────────────────────────────────────────────────

    private function getClientInfo(): array
    {
        return $this->get_client_info();
    }

    // ── Assemble ─────────────────────────────────────────────────────────────

    public function getSystemInfo(): array
    {
        $system_information = array(
            'Emoncms' => $this->getEmoncmsInfo(),
            'Server' => $this->getServerInfo(),
            'Memory' => $this->getMemoryInfo(),
            'Disk' => $this->getDiskInfo(),
            'HTTP' => $this->getHttpInfo(),
            'MySQL' => $this->getMysqlInfo(),
            'PHP' => $this->getPhpInfo(),
        );

        if (!empty($this->settings['redis']['enabled'])) {
            $system_information['Redis'] = $this->getRedisInfo();
        }

        $mqtt_info = $this->getMqttInfo();
        if (!empty($mqtt_info)) {
            $system_information['MQTT Server'] = $mqtt_info;
        }

        $rpi_info = $this->get_rpi_info();
        $has_pi_data = false;
        foreach ($rpi_info as $value) {
            if (!empty($value)) {
                $has_pi_data = true;
                break;
            }
        }
        if ($has_pi_data) {
            $serial = strtoupper(ltrim(isset($rpi_info['sn']) ? $rpi_info['sn'] : '', '0'));
            $pi_section = array(
                'Model' => isset($rpi_info['model']) ? $rpi_info['model'] : '',
                'Serial num.' => $serial,
                'CPU Temperature' => isset($rpi_info['cputemp']) ? $rpi_info['cputemp'] : '',
                'GPU Temperature' => isset($rpi_info['gputemp']) ? $rpi_info['gputemp'] : '',
                'File-system' => isset($rpi_info['currentfs']) ? $rpi_info['currentfs'] : '',
            );
            if (!empty($rpi_info['emonpiRelease'])) {
                $pi_section['emonpiRelease'] = $rpi_info['emonpiRelease'];
            }
            $system_information['Pi'] = $pi_section;
        }

        return array(
            'Services' => false, // added in controller.
            'System Information' => $system_information,
            'Client Information' => $this->getClientInfo(),
        );
    }
}