<?php

declare(strict_types=1);

namespace MrtgSensor\Enum;

enum SensorType: string
{
    case CPU = 'cpu';
    case CPU_PERCENT = 'cpu%';
    case MEMORY = 'mem';
    case MEMORY_PERCENT = 'mem%';
    case DISK = 'disk';
    case DISK_PERCENT = 'disk%';
    case BATTERY = 'battery';
    case BATTERY_PERCENT = 'battery%';
    case BATTERY_INVERSE = 'battery-';
    case BATTERY_VOLTAGE = 'batt_volt';
    case BATTERY_AMPERE = 'batt_amp';
    case BATTERY_CYCLES = 'batt_cycles';
    case PROCESS_COUNT = 'proc';
    case FOLDER_SIZE = 'foldersize';
    case FILE_COUNT = 'filecount';
    case FOLDER_COUNT = 'foldercount';
    case PING_TIME = 'pingtime';

    public static function tryFromString(string $key): ?self
    {
        return self::tryFrom(strtolower($key));
    }
}
