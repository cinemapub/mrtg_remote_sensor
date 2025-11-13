<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MrtgSensor\Cache\CacheAdapter;
use MrtgSensor\Command\CommandExecutor;
use MrtgSensor\Http\Request;
use MrtgSensor\Http\Response;
use MrtgSensor\OS\OSDetector;
use MrtgSensor\OS\{BusyBoxTools, DarwinTools, LinuxTools, WindowsTools};
use MrtgSensor\Enum\{OSType, SensorType};
use MrtgSensor\Sensor\{Sensor, MrtgFormatter};

// Initialize dependencies
$debug = isset($_GET['debug']);
$cache = new CacheAdapter(__DIR__ . '/../var/cache');
$executor = new CommandExecutor($cache, $debug);

// Detect OS and create appropriate OS tools
$osType = OSDetector::detect();
$osTools = match($osType) {
    OSType::WINDOWS => new WindowsTools($executor),
    OSType::DARWIN => new DarwinTools($executor),
    OSType::BUSYBOX => new BusyBoxTools($executor),
    OSType::LINUX => new LinuxTools($executor),
};

// Create request
$request = Request::fromGlobals();

// Create sensor
$sensor = new Sensor(
    $osTools,
    $request->getServerName(),
    $request->getScriptName(),
    $request->isHttps()
);

// Get parameters
$keyParam = $request->get('key', 'cpu');
$param = $request->get('param');
$options = $request->get('options', '');
$withConfig = $request->has('config');

// Parse sensor type
$sensorType = SensorType::tryFromString($keyParam);
if ($sensorType === null) {
    Response::error("Unknown sensor type: {$keyParam}")->send();
    exit(1);
}

// Execute sensor
try {
    $result = match($sensorType) {
        SensorType::CPU => $sensor->cpuusage(),
        SensorType::CPU_PERCENT => $sensor->cpuusage(true),
        SensorType::MEMORY => $sensor->memusage(),
        SensorType::MEMORY_PERCENT => $sensor->memusage(true),
        SensorType::DISK => $sensor->diskusage($param),
        SensorType::DISK_PERCENT => $sensor->diskusage($param, true),
        SensorType::PROCESS_COUNT => $sensor->proccount($param),
        SensorType::BATTERY => $sensor->battery(''),
        SensorType::BATTERY_PERCENT => $sensor->battery('%'),
        SensorType::BATTERY_INVERSE => $sensor->battery('-'),
        SensorType::BATTERY_VOLTAGE => $sensor->battery('V'),
        SensorType::BATTERY_AMPERE => $sensor->battery('A'),
        SensorType::BATTERY_CYCLES => $sensor->battery('C'),
        SensorType::FOLDER_SIZE => $sensor->foldersize($param ?? '.', $options),
        SensorType::FILE_COUNT => $sensor->filecount($param ?? '.', $options),
        SensorType::FOLDER_COUNT => $sensor->foldercount($param ?? '.', $options),
        SensorType::PING_TIME => $sensor->pingtime($param ?? 'localhost'),
    };

    $output = MrtgFormatter::formatOutput($result, $withConfig);
    Response::text($output)->send();

} catch (\Throwable $e) {
    if ($debug) {
        Response::error(
            "Error: {$e->getMessage()}\n{$e->getTraceAsString()}",
            500
        )->send();
    } else {
        Response::error('Sensor error occurred', 500)->send();
    }
    exit(1);
}
