<?php

declare(strict_types=1);

namespace MrtgSensor\Tests\Integration;

use MrtgSensor\Cache\CacheAdapter;
use MrtgSensor\Command\CommandExecutor;
use MrtgSensor\Enum\OSType;
use MrtgSensor\OS\BusyBoxTools;
use MrtgSensor\OS\DarwinTools;
use MrtgSensor\OS\LinuxTools;
use MrtgSensor\OS\OSDetector;
use MrtgSensor\OS\WindowsTools;
use MrtgSensor\Sensor\Sensor;
use PHPUnit\Framework\TestCase;

final class SensorIntegrationTest extends TestCase
{
    private Sensor $sensor;

    protected function setUp(): void
    {
        $cache = new CacheAdapter(sys_get_temp_dir().'/mrtg_cache');
        $executor = new CommandExecutor($cache, false);

        $osType = OSDetector::detect();
        $osTools = match ($osType) {
            OSType::WINDOWS => new WindowsTools($executor),
            OSType::DARWIN => new DarwinTools($executor),
            OSType::BUSYBOX => new BusyBoxTools($executor),
            OSType::LINUX => new LinuxTools($executor),
        };

        $this->sensor = new Sensor($osTools, 'testserver', '/test.php', false);
    }

    public function test_cpu_usage_returns_valid_result(): void
    {
        $result = $this->sensor->cpuusage();

        $this->assertIsNumeric($result->value1);
        $this->assertIsNumeric($result->value2);
        $this->assertNotEmpty($result->server);
        $this->assertNotEmpty($result->description);
    }

    public function test_memory_usage_returns_valid_result(): void
    {
        $result = $this->sensor->memusage();

        $this->assertIsNumeric($result->value1);
        $this->assertIsNumeric($result->value2);
        $this->assertNotEmpty($result->description);
    }

    public function test_disk_usage_returns_valid_result(): void
    {
        $result = $this->sensor->diskusage('.');

        $this->assertIsNumeric($result->value1);
        $this->assertIsNumeric($result->value2);
        $this->assertStringContainsString('Disk', $result->description);
    }
}
