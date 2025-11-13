# Process Count Sensor (`proc`)

Counts running processes, optionally filtered by name.

## Overview

- **Sensor Key**: `proc`
- **Platforms**: All
- **Parameters**: Optional `param=filter`
- **Cache TTL**: 30 seconds

## Usage

### Count All Processes

```bash
curl "http://server/index.php?key=proc"
```

### Count Filtered Processes

```bash
# Count Apache processes
curl "http://server/index.php?key=proc&param=apache"

# Count PHP processes
curl "http://server/index.php?key=proc&param=php"
```

## Output

```
156
156
1d 12h 35m
myserver
```

Values: Process count (both values are the same)

## MRTG Config

```mrtg
Target[server_proc_total]: `curl -s "http://server/index.php?key=proc"`
Title[server_proc_total]: Server: Total Processes
MaxBytes[server_proc_total]: 1000
Options[server_proc_total]: growright,nobanner,gauge

Target[server_proc_apache]: `curl -s "http://server/index.php?key=proc&param=apache"`
Title[server_proc_apache]: Server: Apache Processes
MaxBytes[server_proc_apache]: 100
```

## OS Commands

- **Linux/macOS**: `ps aux | grep filter | wc -l`
- **Windows**: `tasklist | find "filter" /C`

## Use Cases

- Monitor total process count
- Track specific service processes
- Alert on process count spikes
- Detect service crashes (count = 0)
