<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

final class LinuxTools extends OSTools
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
        // Try to read /proc/cpuinfo if available
        if (! is_readable('/proc/cpuinfo')) {
            return ['cores' => 1, 'ghz' => 1.0, 'bogomips' => 1000];
        }

        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        $cores = count($matches[0]) > 0 ? count($matches[0]) : 1;

        // Extract bogomips
        $bogomips = $this->grepCpuInfo('bogomips', 1000);
        $numcores = $this->grepCpuInfo('cpu cores', $cores);
        $cpuMhz = $this->grepCpuInfo('cpu MHz', $bogomips / $numcores);
        $ghz = round($cpuMhz / 1000, 1);

        return [
            'cores' => (int) $numcores,
            'ghz' => $ghz,
            'bogomips' => (int) (round($bogomips / 10) * 10),
        ];
    }

    #[\Override]
    public function memusage(): array
    {
        $result = $this->executor->execute('free | grep Mem');
        $line = preg_replace('/\s\s*/', "\t", trim($result->getFirstLine()));
        $parts = explode("\t", $line);

        return [
            'free' => (int) ($parts[3] ?? 0),
            'used' => (int) ($parts[2] ?? 0),
            'total' => (int) ($parts[1] ?? 0),
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

        $blocks = (int) ($parts[1] ?? 0);
        $used = (int) ($parts[2] ?? 0);

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
        $size = (int) ($parts[0] ?? 0);

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
        $total = (int) ($psall->stdout[0] ?? 0) - 3;

        if ($filter) {
            $psfilter = $this->executor->execute("ps -ax | grep \"{$filter}\" | wc -l");
            $nb = (int) ($psfilter->stdout[0] ?? 0) - 1;
        } else {
            $nb = $total;
        }

        return [
            'total' => $total,
            'filtered' => $nb,
        ];
    }

    private function grepCpuInfo(string $param, float|int $default): float|int
    {
        if (! is_readable('/proc/cpuinfo')) {
            return $default;
        }

        $result = $this->executor->execute("grep -i \"{$param}\" /proc/cpuinfo", null, 3600);
        $val = $default;

        foreach ($result->stdout as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $val = trim($parts[1]);
            }
        }

        return is_numeric($val) ? (float) $val : $default;
    }
}
