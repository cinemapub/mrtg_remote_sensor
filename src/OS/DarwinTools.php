<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

final class DarwinTools extends OSTools
{
    #[\Override]
    public function cpuload(): array
    {
        $load = sys_getloadavg();
        return [
            '1min' => round($load[0], 3),
            '5min' => round($load[1], 3),
            '15min' => round($load[2], 3),
        ];
    }

    #[\Override]
    public function cpuinfo(): array
    {
        $cores = $this->sysctl('hw.ncpu');
        $freq = $this->sysctl('hw.cpufrequency');
        $ghz = round($freq / 1000000000, 1);
        $bogomips = (int)($cores * $ghz * 1000);

        return [
            'cores' => (int)$cores,
            'ghz' => $ghz,
            'bogomips' => $bogomips,
        ];
    }

    #[\Override]
    public function memusage(): array
    {
        $result = $this->executor->execute('free | grep Mem');
        $line = preg_replace('/\s\s*/', "\t", trim($result->getFirstLine()));
        $parts = explode("\t", $line);

        return [
            'free' => (int)($parts[3] ?? 0),
            'used' => (int)($parts[2] ?? 0),
            'total' => (int)($parts[1] ?? 0),
        ];
    }

    #[\Override]
    public function diskusage(string $path): array
    {
        $result = $this->executor->execute("df -m \"{$path}\"");
        $stdout = $result->stdout;
        if (count($stdout) < 2) {
            return ['total' => 0, 'used' => 0, 'free' => 0];
        }

        $line = preg_replace('/\s\s*/', "\t", $stdout[1]);
        $parts = explode("\t", $line);

        $blocks = (int)($parts[1] ?? 0);
        $used = (int)($parts[2] ?? 0);

        return [
            'total' => $blocks * 1024,
            'used' => $used * 1024,
            'free' => ($blocks - $used) * 1024,
        ];
    }

    #[\Override]
    public function foldersize(string $path): array
    {
        $diskusage = $this->diskusage($path);
        $result = $this->executor->execute("du -sk \"{$path}\"", null, 3600);
        $line = preg_replace('/\s\s*/', "\t", $result->getFirstLine());
        $parts = explode("\t", $line);
        $size = (int)($parts[0] ?? 0);

        return [
            'total' => $diskusage['total'],
            'size' => $size,
        ];
    }

    #[\Override]
    public function uptime(): string
    {
        $result = $this->executor->execute('/usr/bin/uptime');
        if (count($result->stdout) === 0) {
            return '0 days';
        }

        $output = str_replace(['up', 'load'], '|', $result->stdout[0]);
        $parts = explode('|', $output);
        if (count($parts) < 2) {
            return '0 days';
        }

        $uptime = preg_replace('/([0-9]+ users)/', '', $parts[1]);
        $uptime = str_replace(',', '', $uptime);

        return trim($uptime);
    }

    #[\Override]
    public function proccount(?string $filter): array
    {
        $psall = $this->executor->execute('ps -ax | wc -l');
        $total = (int)($psall->stdout[0] ?? 0) - 3;

        if ($filter) {
            $psfilter = $this->executor->execute("ps -ax | grep \"{$filter}\" | wc -l");
            $nb = (int)($psfilter->stdout[0] ?? 0) - 1;
        } else {
            $nb = $total;
        }

        return [
            'total' => $total,
            'filtered' => $nb,
        ];
    }

    #[\Override]
    public function battery(): ?array
    {
        if (!file_exists('/usr/sbin/system_profiler')) {
            return null;
        }

        $output = $this->executor->execute('system_profiler SPPowerDataType', null, 600);
        $parsed = $this->parseProfiler($output->stdout);

        $result = [
            'battery_capacity' => $this->findVal('Full Charge Capacity', $parsed),
            'charger_watt' => $this->findVal('AC Charger Information - Wattage', $parsed),
            'battery_present' => $this->findBool('Battery Information - Battery Installed', $parsed),
            'charger_busy' => $this->findBool('AC Charger Information - Charging', $parsed),
            'charger_present' => $this->findBool('AC Charger Information - Connected', $parsed),
            'charger_done' => $this->findBool('Fully Charged', $parsed),
            'battery_health' => $this->findVal('Health Information - Condition', $parsed),
            'battery_cycles' => $this->findVal('Cycle Count', $parsed),
            'battery_mamp' => $this->findVal('Battery Information - Amperage', $parsed),
            'battery_mvolt' => $this->findVal('Battery Information - Voltage', $parsed),
            'battery_charge' => $this->findVal('Charge Remaining', $parsed),
        ];

        $capacity = $result['battery_capacity'] ?? 1;
        $result['battery_charge_%'] = $capacity > 0 ? round(100 * ($result['battery_charge'] ?? 0) / $capacity, 3) : 0;

        return $result;
    }

    private function sysctl(string $key): float|int
    {
        $result = $this->executor->execute("sysctl -a | grep '{$key}:'", null, 3600);
        if (count($result->stdout) === 0) {
            return 0;
        }

        $parts = explode(':', $result->stdout[0], 2);
        return isset($parts[1]) ? (float)trim($parts[1]) : 0;
    }

    private function parseProfiler(array $lines): array
    {
        $parsed = [];
        $titles = [];
        $previndent = -1;

        foreach ($lines as $line) {
            if (strlen(trim($line)) === 0) {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));
            $parts = explode(':', trim($line), 2);
            $key = $parts[0];
            $val = isset($parts[1]) ? trim($parts[1]) : '';

            if (strlen($val) > 0) {
                $tree = [];
                foreach ($titles as $level => $title) {
                    if ($level < $indent && strlen($title) > 0) {
                        $tree[] = $title;
                    }
                }
                $tree[] = $key;
                $combined = implode(' - ', $tree);
                $parsed[] = "{$combined}: {$val}";
            }

            $titles[$indent] = $key;
            $previndent = $indent;
        }

        return $parsed;
    }

    private function findVal(string $pattern, array $subject): string|int
    {
        $results = preg_grep("/{$pattern}/", $subject);
        if (!$results) {
            return '';
        }

        foreach ($results as $result) {
            $parts = explode(':', $result, 2);
            if (count($parts) === 2) {
                return trim($parts[1]);
            }
        }

        return '';
    }

    private function findBool(string $pattern, array $subject): int
    {
        $results = preg_grep("/{$pattern}/", $subject);
        if (!$results) {
            return 0;
        }

        foreach ($results as $result) {
            $parts = explode(':', $result, 2);
            if (count($parts) === 2) {
                $val = strtoupper(trim($parts[1]));
                return match ($val) {
                    '1', 'TRUE', 'YES', 'OUI' => 1,
                    default => str_starts_with($val, 'N') ? 0 : 1,
                };
            }
        }

        return 0;
    }
}
