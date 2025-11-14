<?php

declare(strict_types=1);

namespace MrtgSensor\OS;

use MrtgSensor\Enum\OSType;

final class OSDetector
{
    private static ?OSType $detectedOS = null;

    public static function detect(): OSType
    {
        if (self::$detectedOS !== null) {
            return self::$detectedOS;
        }

        $osName = strtolower(php_uname('s'));

        // Check for Synology/BusyBox
        if (is_dir('/usr/syno/synoman/')) {
            self::$detectedOS = OSType::BUSYBOX;

            return self::$detectedOS;
        }

        // Check for Windows
        if (str_starts_with($osName, 'windows')) {
            self::$detectedOS = OSType::WINDOWS;

            return self::$detectedOS;
        }

        // Check for Darwin (macOS)
        if ($osName === 'darwin') {
            self::$detectedOS = OSType::DARWIN;

            return self::$detectedOS;
        }

        // Default to Linux
        self::$detectedOS = OSType::LINUX;

        return self::$detectedOS;
    }
}
