# Memory Usage Percentage Sensor (`mem%`)

Monitors RAM usage as a percentage (0-100%).

## Overview

- **Sensor Key**: `mem%`
- **Platforms**: Linux, macOS, Windows, BusyBox
- **Parameters**: None
- **MRTG Type**: Gauge

## Usage

```bash
curl "http://server/index.php?key=mem%"
```

## Output

```
75.50
100.00
1d 12h 35m
myserver
```

Values: Used% and Total% (always 100)

## MRTG Config

```mrtg
Target[server_mem_pct]: `curl -s "http://server/index.php?key=mem%"`
Title[server_mem_pct]: Server: Memory Usage %
MaxBytes[server_mem_pct]: 100
Options[server_mem_pct]: growright,nobanner,gauge,nopercent
```

## See Also

- [Memory Bytes Sensor](mem.md)
