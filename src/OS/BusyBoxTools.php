<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

final class BusyBoxTools extends OSTools
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
        // BusyBox systems often have limited /proc access
        return ['cores' => 1, 'ghz' => 1.0, 'bogomips' => 1000];
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
        // BusyBox ps doesn't support -ax option
        $psall = $this->executor->execute('ps | wc -l');
        $total = (int) ($psall->stdout[0] ?? 0) - 3;

        if ($filter) {
            $psfilter = $this->executor->execute("ps | grep \"{$filter}\" | wc -l");
            $nb = (int) ($psfilter->stdout[0] ?? 0) - 1;
        } else {
            $nb = $total;
        }

        return [
            'total' => $total,
            'filtered' => $nb,
        ];
    }
}
