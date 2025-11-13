# Disk Usage Sensor (`disk`)

Monitors disk space usage in bytes for a specified mount point or path.

## Overview

- **Sensor Key**: `disk`
- **Platforms**: All
- **Parameters**: **Required** `param=/path`
- **Cache TTL**: 30 seconds

## Usage

```bash
curl "http://server/index.php?key=disk&param=/"
curl "http://server/index.php?key=disk&param=/home"
```

## Output

```
50000000000
100000000000
1d 12h 35m
myserver
```

Values: Used bytes, Total bytes

## MRTG Config

```mrtg
Target[server_disk_root]: `curl -s "http://server/index.php?key=disk&param=/"`
Title[server_disk_root]: Server: Root Disk Usage
MaxBytes[server_disk_root]: 1000000000000
kMG[server_disk_root]: k,M,G,T,P
Options[server_disk_root]: growright,nobanner,gauge
```

## OS Commands

- **Linux/macOS**: `df -B1 /path`
- **Windows**: `wmic logicaldisk where DeviceID="C:" get FreeSpace,Size`

## See Also

- [Disk Percentage Sensor](disk-percent.md)
