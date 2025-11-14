<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

final class WindowsTools extends OSTools
{
    #[\Override]
    public function cpuload(): array
    {
        $load = [];
        $load[0] = $this->wmic('cpu get LoadPercentage', 0);
        sleep(1);
        $load[1] = $this->wmic('cpu get LoadPercentage');

        $load_avg = ($load[0] + $load[1]) / 2;
        $load_min = min($load);
        $load_max = max($load);

        return [
            '1min' => round($load_max, 1),
            '5min' => round($load_avg, 1),
            '15min' => round($load_min, 1),
        ];
    }

    #[\Override]
    public function cpuinfo(): array
    {
        $cores = $this->wmic('cpu get NumberOfCores', 3600);
        $mhz = $this->wmic('cpu get MaxClockSpeed', 3600);
        $ghz = round($mhz / 1000, 1);
        $bogomips = (int) ($cores * $ghz * 1000);

        return [
            'cores' => $cores,
            'ghz' => $ghz,
            'bogomips' => $bogomips,
        ];
    }

    #[\Override]
    public function memusage(): array
    {
        $total = $this->wmic('OS get TotalVisibleMemorySize', 3600);
        $free = $this->wmic('OS get FreePhysicalMemory');

        return [
            'free' => $free,
            'used' => $total - $free,
            'total' => $total,
        ];
    }

    #[\Override]
    public function diskusage(string $path): array
    {
        $disk = strtolower(substr($path, 0, 1).':');
        $result = $this->executor->execute('wmic logicaldisk get size,freespace,caption');

        $free = 0;
        $total = 0;

        foreach ($result->stdout as $line) {
            $line = strtolower(trim($line));
            if (! str_contains($line, ' ')) {
                continue;
            }

            $line = preg_replace('/\s\s*/', "\t", $line);
            $parts = explode("\t", $line);
            if (count($parts) < 3) {
                continue;
            }

            $dname = $parts[0];
            if ($dname === $disk) {
                $free = (int) $parts[1];
                $total = (int) $parts[2];
                break;
            }
        }

        return [
            'total' => (int) round($total / 1000),
            'free' => (int) round($free / 1000),
            'used' => (int) round(($total - $free) / 1000),
        ];
    }

    #[\Override]
    public function foldersize(string $path): array
    {
        $diskusage = $this->diskusage($path);

        // Windows COM-based folder size calculation
        // This requires COM extension, so we'll provide a fallback
        $size = 0;
        if (class_exists('COM')) {
            try {
                $obj = new \COM('scripting.filesystemobject');
                if (is_object($obj)) {
                    $ref = $obj->getfolder($path);
                    $size = (int) round($ref->size / 1000);
                    $obj = null;
                }
            } catch (\Throwable $e) {
                // COM not available or error accessing folder
                $size = 0;
            }
        }

        return [
            'total' => $diskusage['total'],
            'size' => $size,
        ];
    }

    #[\Override]
    public function uptime(): string
    {
        $lastboot = $this->wmicString('os get lastbootuptime', 3600);
        $bdate = substr($lastboot, 0, 4).'-'.substr($lastboot, 4, 2).'-'.substr($lastboot, 6, 2).' ';
        $bdate .= substr($lastboot, 8, 2).':'.substr($lastboot, 10, 2).':'.substr($lastboot, 12, 2);

        $btime = strtotime($bdate);
        $since = time() - $btime;
        $sincedays = $since / (3600 * 24);

        return match (true) {
            $sincedays < 1 => round($since / 3600, 1).' hours',
            $sincedays < 60 => round($since / (3600 * 24), 1).' days',
            $sincedays < 365 => round($since / (3600 * 24 * 7), 1).' weeks',
            default => round($since / (3600 * 24 * 365), 1).' years',
        };
    }

    #[\Override]
    public function proccount(?string $filter): array
    {
        $result = $this->executor->execute('tasklist');
        $total = count($result->stdout) - 3;

        if ($filter) {
            $nb = 0;
            foreach ($result->stdout as $task) {
                if (stristr($task, $filter)) {
                    $nb++;
                }
            }
        } else {
            $nb = $total;
        }

        return [
            'total' => $total,
            'filtered' => $nb,
        ];
    }

    private function wmic(string $command, int $cacheSeconds = 30): int
    {
        $result = $this->executor->execute("wmic {$command}", null, $cacheSeconds);
        if (count($result->stdout) < 2) {
            return 0;
        }

        return (int) trim($result->stdout[1]);
    }

    private function wmicString(string $command, int $cacheSeconds = 30): string
    {
        $result = $this->executor->execute("wmic {$command}", null, $cacheSeconds);
        if (count($result->stdout) < 2) {
            return '';
        }

        return trim($result->stdout[1]);
    }
}
