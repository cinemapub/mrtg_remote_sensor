<?php

declare(strict_types=1);

namespace MrtgSensor\Sensor;

use MrtgSensor\OS\OSTools;

final class Sensor
{
    public const VERSION = '2.0';

    private readonly string $serverName;
    private readonly string $uptime;
    private readonly string $baseUrl;

    public function __construct(
        private readonly OSTools $osTools,
        string $serverName,
        string $scriptName,
        bool $isHttps = false,
    ) {
        $this->serverName = $serverName;
        $this->uptime = $this->osTools->uptime();
        $this->baseUrl = ($isHttps ? 'https://' : 'http://') . $serverName . $scriptName;
    }

    public function cpuusage(bool $asPercent = false): SensorResult
    {
        $cpuLoad = $this->osTools->cpuload();
        $cpuInfo = $this->osTools->cpuinfo();

        $load5 = $cpuLoad['5min'];
        $load15 = $cpuLoad['15min'];
        $cores = $cpuInfo['cores'];
        $server = $cores == 1
            ? "{$cpuInfo['ghz']} GHz (bogomips {$cpuInfo['bogomips']})"
            : "{$cores} cores x {$cpuInfo['ghz']} GHz (bogomips {$cpuInfo['bogomips']})";

        if (!$asPercent) {
            return new SensorResult(
                value1: $load5 * 100,
                value2: $load15 * 100,
                uptime: $this->uptime,
                server: $server,
                name1: 'Avg load over 5 min',
                name2: 'Avg load over 15 min',
                description: "{$this->serverName}: CPU (5/15 min - {$cores} CPUs)",
                mrtgUnit: 'load',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: 500 * $cores,
                mrtgKmg: ',k,M,G,T,P',
                url: $this->buildUrl('cpu', ''),
                cfgUrl: $this->buildUrl('cpu', '', true),
                mrtgName: $this->buildCounterName('cpu'),
                version: self::VERSION,
            );
        }

        return new SensorResult(
            value1: round($load5 * 100 / $cores, 2),
            value2: round($load15 * 100 / $cores, 2),
            uptime: $this->uptime,
            server: $server,
            name1: '% used - 5 min',
            name2: '% used - 15 min',
            description: "{$this->serverName}: CPU% (5/15 min - {$cores} CPUs)",
            mrtgUnit: '%',
            mrtgOptions: 'growright,nobanner,gauge,nopercent',
            mrtgMaxBytes: 1000,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('cpu%', ''),
            cfgUrl: $this->buildUrl('cpu%', '', true),
            mrtgName: $this->buildCounterName('cpu%'),
            version: self::VERSION,
        );
    }

    public function memusage(bool $asPercent = false): SensorResult
    {
        $mem = $this->osTools->memusage();
        $server = strtolower($this->serverName);

        if (!$asPercent) {
            return new SensorResult(
                value1: $mem['used'],
                value2: $mem['total'],
                uptime: $this->uptime,
                server: $server,
                name1: 'Used RAM',
                name2: 'Total RAM',
                description: "{$server}: Mem (used/total)",
                mrtgUnit: 'B',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: $mem['total'],
                mrtgKmg: 'k,M,G,T,P',
                url: $this->buildUrl('mem', ''),
                cfgUrl: $this->buildUrl('mem', '', true),
                mrtgName: $this->buildCounterName('mem'),
                version: self::VERSION,
            );
        }

        return new SensorResult(
            value1: round($mem['used'] * 100 / $mem['total'], 2),
            value2: 100,
            uptime: $this->uptime,
            server: $server,
            name1: '% RAM used',
            name2: '100%',
            description: "{$server}: Mem %",
            mrtgUnit: '%',
            mrtgOptions: 'growright,nobanner,gauge,nopercent',
            mrtgMaxBytes: 100,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('mem%', ''),
            cfgUrl: $this->buildUrl('mem%', '', true),
            mrtgName: $this->buildCounterName('mem%'),
            version: self::VERSION,
        );
    }

    public function diskusage(?string $path = null, bool $asPercent = false): SensorResult
    {
        $path ??= '.';
        $disk = $this->osTools->diskusage($path);
        $server = strtolower($this->serverName);

        if (!$asPercent) {
            return new SensorResult(
                value1: $disk['used'],
                value2: $disk['total'],
                uptime: $this->uptime,
                server: $server,
                name1: 'Used disk space',
                name2: 'Total disk space',
                description: "{$server}: Disk (used/total) [{$path}]",
                mrtgUnit: 'B',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: $disk['total'],
                mrtgKmg: 'k,M,G,T,P',
                url: $this->buildUrl('disk', $path),
                cfgUrl: $this->buildUrl('disk', $path, true),
                mrtgName: $this->buildCounterName('disk'),
                version: self::VERSION,
            );
        }

        $percent = $disk['total'] > 0 ? round($disk['used'] * 100 / $disk['total'], 2) : 0;
        return new SensorResult(
            value1: $percent,
            value2: 100,
            uptime: $this->uptime,
            server: $server,
            name1: 'Used disk %',
            name2: '100%',
            description: "{$server}: Disk usage % [{$path}]",
            mrtgUnit: '%',
            mrtgOptions: 'growright,nobanner,gauge',
            mrtgMaxBytes: 100,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('disk%', $path),
            cfgUrl: $this->buildUrl('disk%', $path, true),
            mrtgName: $this->buildCounterName('disk%'),
            version: self::VERSION,
        );
    }

    public function foldersize(string $folder, string $options = ''): SensorResult
    {
        $result = $this->osTools->foldersize($folder);
        $desc = "Folder Size [{$folder}]";

        return new SensorResult(
            value1: $result['size'],
            value2: $result['total'],
            uptime: $this->uptime,
            server: $this->serverName,
            name1: 'Folder size',
            name2: 'Total disk size',
            description: $desc,
            mrtgUnit: 'B',
            mrtgOptions: 'growright,nobanner,gauge,noo',
            mrtgMaxBytes: $result['total'],
            mrtgKmg: 'k,M,G,T,P',
            url: $this->buildUrl('foldersize', $folder),
            cfgUrl: $this->buildUrl('foldersize', $folder, true),
            mrtgName: $this->buildCounterName('foldersize'),
            version: self::VERSION,
        );
    }

    public function filecount(string $folder, string $options = ''): SensorResult
    {
        $params = $this->parseOptions($options);
        $findopt = '';

        if (!($params['recursive'] ?? false)) {
            $findopt .= '-maxdepth 1 ';
        }
        if (isset($params['mtime'])) {
            $findopt .= "-mtime {$params['mtime']} ";
        }
        if (isset($params['name'])) {
            $findopt .= "-name {$params['name']} ";
        }
        $findopt .= '-type f';

        $result = $this->osTools->executor->execute("find \"{$folder}\" {$findopt} | wc -l");
        $nb = (int)trim($result->getFirstLine());

        $desc = "File count [{$folder}]";
        if ($options) {
            $desc .= " [{$options}]";
        }

        return new SensorResult(
            value1: $nb,
            value2: 0,
            uptime: $this->uptime,
            server: $this->serverName,
            name1: $desc,
            name2: '',
            description: $desc,
            mrtgUnit: 'file(s)',
            mrtgOptions: 'growright,nobanner,gauge,noo,nopercent',
            mrtgMaxBytes: 1000000,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('filecount', $folder),
            cfgUrl: $this->buildUrl('filecount', $folder, true),
            mrtgName: $this->buildCounterName('filecount'),
            version: self::VERSION,
        );
    }

    public function foldercount(string $folder, string $options = ''): SensorResult
    {
        $params = $this->parseOptions($options);
        $findopt = '';

        if (!($params['recursive'] ?? false)) {
            $findopt .= '-maxdepth 1 ';
        }
        if (isset($params['mtime'])) {
            $findopt .= "-mtime {$params['mtime']} ";
        }
        if (isset($params['name'])) {
            $findopt .= "-name {$params['name']} ";
        }
        $findopt .= '-type d';

        $result = $this->osTools->executor->execute("find \"{$folder}\" {$findopt} | wc -l");
        $nb = (int)trim($result->getFirstLine());

        $desc = "Folder count [{$folder}]";
        if ($options) {
            $desc .= " [{$options}]";
        }

        return new SensorResult(
            value1: $nb,
            value2: 0,
            uptime: $this->uptime,
            server: $this->serverName,
            name1: $desc,
            name2: '',
            description: $desc,
            mrtgUnit: 'folder(s)',
            mrtgOptions: 'growright,nobanner,gauge,noo,nopercent',
            mrtgMaxBytes: 1000000,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('foldercount', $folder),
            cfgUrl: $this->buildUrl('foldercount', $folder, true),
            mrtgName: $this->buildCounterName('foldercount'),
            version: self::VERSION,
        );
    }

    public function proccount(?string $filter = null): SensorResult
    {
        $result = $this->osTools->proccount($filter);
        $server = strtolower($this->serverName);
        $desc = "{$server}: server load";
        $descf = $desc;
        if ($filter) {
            $descf .= " [{$filter}]";
        }

        return new SensorResult(
            value1: $result['filtered'],
            value2: $result['total'],
            uptime: $this->uptime,
            server: $server,
            name1: $descf,
            name2: $desc,
            description: $desc,
            mrtgUnit: 'proc',
            mrtgOptions: 'growright,nobanner,gauge,noo,nopercent',
            mrtgMaxBytes: 1000000,
            mrtgKmg: ',k,M,G,T,P',
            url: $this->buildUrl('proc', $filter ?? ''),
            cfgUrl: $this->buildUrl('proc', $filter ?? '', true),
            mrtgName: $this->buildCounterName('proc'),
            version: self::VERSION,
        );
    }

    public function pingtime(string $address, int $port = 80): SensorResult
    {
        $results = [];
        for ($i = 0; $i < 4; $i++) {
            $results[] = $this->tcpping($address, $port);
        }

        $min = min($results);
        $max = max($results);
        $desc = $port == 80 ? "ping time to {$address}" : "ping time to {$address}:{$port}";

        return new SensorResult(
            value1: (int)$min,
            value2: (int)$max,
            uptime: $this->uptime,
            server: $this->serverName,
            name1: "MIN {$desc}",
            name2: "MAX {$desc}",
            description: $desc,
            mrtgUnit: 'sec',
            mrtgOptions: 'growright,nobanner,gauge,nopercent',
            mrtgMaxBytes: 1000000000,
            mrtgKmg: 'u,m,,k,M,G',
            url: $this->buildUrl('pingtime', $address),
            cfgUrl: $this->buildUrl('pingtime', $address, true),
            mrtgName: $this->buildCounterName('pingtime'),
            version: self::VERSION,
        );
    }

    public function battery(string $type = ''): SensorResult
    {
        $result = $this->osTools->battery();
        if (!$result) {
            throw new \RuntimeException('Battery information not available on this system');
        }

        $server = strtolower($this->serverName);

        return match ($type) {
            '-' => new SensorResult(
                value1: $result['battery_capacity'] - $result['battery_charge'],
                value2: $result['battery_capacity'],
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery consumed Ah',
                name2: 'Battery maximum Ah',
                description: "{$server}: Battery charge",
                mrtgUnit: 'Ah',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: $result['battery_capacity'],
                mrtgKmg: ',k,M,G,T,P',
                url: $this->buildUrl('battery-'),
                cfgUrl: $this->buildUrl('battery-', '', true),
                mrtgName: $this->buildCounterName('battery-'),
                version: self::VERSION,
            ),
            '%' => new SensorResult(
                value1: $result['battery_charge_%'],
                value2: $result['charger_busy'] * 100,
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery charge %',
                name2: 'Charger active',
                description: "{$server}: Battery charge %",
                mrtgUnit: '%',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: 2,
                mrtgKmg: ',k,M,G,T,P',
                url: $this->buildUrl('battery%'),
                cfgUrl: $this->buildUrl('battery%', '', true),
                mrtgName: $this->buildCounterName('battery%'),
                version: self::VERSION,
            ),
            'V' => new SensorResult(
                value1: $result['battery_mvolt'],
                value2: 0,
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery voltage',
                name2: '',
                description: "{$server}: Battery voltage",
                mrtgUnit: 'V',
                mrtgOptions: 'growright,nobanner,gauge,noo',
                mrtgMaxBytes: 15000,
                mrtgKmg: 'm,,k,M,G,T',
                url: $this->buildUrl('batt_volt'),
                cfgUrl: $this->buildUrl('batt_volt', '', true),
                mrtgName: $this->buildCounterName('batt_volt'),
                version: self::VERSION,
            ),
            'A' => new SensorResult(
                value1: $result['battery_mamp'],
                value2: 0,
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery ampere',
                name2: '',
                description: "{$server}: Battery ampere",
                mrtgUnit: 'A',
                mrtgOptions: 'growright,nobanner,gauge,noo',
                mrtgMaxBytes: 15000,
                mrtgKmg: ',k,M,G,T,P',
                url: $this->buildUrl('batt_amp'),
                cfgUrl: $this->buildUrl('batt_amp', '', true),
                mrtgName: $this->buildCounterName('batt_amp'),
                version: self::VERSION,
            ),
            'C' => new SensorResult(
                value1: $result['battery_cycles'],
                value2: 0,
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery cycles',
                name2: '',
                description: "{$server}: Battery cycles",
                mrtgUnit: '#',
                mrtgOptions: 'growright,nobanner,gauge,noo',
                mrtgMaxBytes: 15000,
                mrtgKmg: 'm,,k,M,G,T',
                url: $this->buildUrl('batt_cycles'),
                cfgUrl: $this->buildUrl('batt_cycles', '', true),
                mrtgName: $this->buildCounterName('batt_cycles'),
                version: self::VERSION,
            ),
            default => new SensorResult(
                value1: $result['battery_charge'],
                value2: $result['battery_capacity'],
                uptime: "Health: {$result['battery_health']} after {$result['battery_cycles']} charging cycles",
                server: $server,
                name1: 'Battery available Ah',
                name2: 'Battery maximum Ah',
                description: "{$server}: Battery charge",
                mrtgUnit: 'Ah',
                mrtgOptions: 'growright,nobanner,gauge',
                mrtgMaxBytes: $result['battery_capacity'],
                mrtgKmg: ',k,M,G,T,P',
                url: $this->buildUrl('battery'),
                cfgUrl: $this->buildUrl('battery', '', true),
                mrtgName: $this->buildCounterName('battery'),
                version: self::VERSION,
            ),
        };
    }

    private function buildUrl(string $key, string $param = '', bool $withConfig = false): string
    {
        $url = "{$this->baseUrl}?key={$key}";
        if ($param) {
            $url .= "&param={$param}";
        }
        return $withConfig ? "{$url}&config=1" : $url;
    }

    private function buildCounterName(string $key): string
    {
        $digest = substr(hash('sha256', $this->serverName), 0, 6);
        $keyShort = str_replace(
            ['filecount', 'foldercount', 'foldersize', 'folder'],
            ['fil', 'fld', 'fsz', 'fsz'],
            $key
        );
        return "{$digest}.{$keyShort}";
    }

    private function parseOptions(string $options): array
    {
        if (!$options) {
            return [];
        }

        $result = [];
        $params = explode(',', $options);

        foreach ($params as $param) {
            $param = self::sanitize($param);
            if (str_contains($param, '=')) {
                [$key, $val] = explode('=', $param, 2);
                $result[$key] = $val;
            } else {
                $result[$param] = $param;
            }
        }

        return $result;
    }

    public static function sanitize(string $text): string
    {
        return str_replace([';', '"'], '', $text);
    }

    private function tcpping(string $ip, int $port): float
    {
        $timeout = 4;
        $t1 = microtime(true);
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return 6666.0;
        }

        fclose($fp);
        $t2 = microtime(true);

        return round(($t2 - $t1) * 1000000);
    }
}
