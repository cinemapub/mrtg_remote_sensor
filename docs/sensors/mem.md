# Memory Usage Sensor (`mem`)

Monitors RAM usage in bytes (used and total).

## Overview

- **Sensor Key**: `mem`
- **Platforms**: Linux, macOS, Windows, BusyBox
- **Parameters**: None
- **Cache TTL**: 30 seconds
- **MRTG Type**: Gauge

## Description

Reports memory usage in bytes, showing both used RAM and total RAM available.

## Usage

### Basic Request

```bash
curl "http://server/index.php?key=mem"
```

### Output Format

```
8589934592
17179869184
1d 12h 35m
myserver
```

**Fields**:
1. **Value 1**: Used RAM (bytes)
2. **Value 2**: Total RAM (bytes)
3. **Uptime**: System uptime
4. **Server**: Server name

### MRTG Configuration

```mrtg
Target[server_mem]: `curl -s "http://server/index.php?key=mem"`
Title[server_mem]: Server: Memory Usage
PageTop[server_mem]: <h1>Server: Memory Usage</h1>
LegendI[server_mem]: Used RAM
LegendO[server_mem]: Total RAM
ShortLegend[server_mem]: B
Options[server_mem]: growright,nobanner,gauge
MaxBytes[server_mem]: 17179869184
kMG[server_mem]: k,M,G,T,P
YLegend[server_mem]: Bytes
```

## OS Implementations

### Linux
Uses `free -b` command:
```bash
free -b
              total        used        free
Mem:    17179869184  8589934592  8589934592
```

### macOS
Uses `vm_stat` and `sysctl`:
```bash
vm_stat
sysctl hw.memsize
```

### Windows
Uses `wmic OS get FreePhysicalMemory,TotalVisibleMemorySize`

## Conversion

```
1 KB = 1,024 bytes
1 MB = 1,048,576 bytes
1 GB = 1,073,741,824 bytes
16 GB = 17,179,869,184 bytes
```

## See Also

- [Memory Percentage Sensor](mem-percent.md)
- [Sensors Overview](README.md)
