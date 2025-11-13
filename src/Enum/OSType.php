<?php

declare(strict_types=1);

namespace MrtgSensor\Enum;

enum OSType: string
{
    case WINDOWS = 'winnt';
    case LINUX = 'linux';
    case DARWIN = 'darwin';
    case BUSYBOX = 'busybox';
}
