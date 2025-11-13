# MRTG Remote Sensor - Sensors Documentation

Complete documentation for all 17 sensors available in MRTG Remote Sensor.

## Quick Reference

| Sensor | Description | Parameters | Platforms |
|--------|-------------|------------|-----------|
| **CPU Sensors** | | | |
| [cpu](cpu.md) | CPU load average | - | All |
| [cpu%](cpu-percent.md) | CPU usage percentage | - | All |
| **Memory Sensors** | | | |
| [mem](mem.md) | Memory usage (bytes) | - | All |
| [mem%](mem-percent.md) | Memory usage percentage | - | All |
| **Disk Sensors** | | | |
| [disk](disk.md) | Disk usage (bytes) | `param=/path` | All |
| [disk%](disk-percent.md) | Disk usage percentage | `param=/path` | All |
| **Battery Sensors** | | | |
| [battery](battery.md) | Battery charge level | - | macOS only |
| [battery%](battery-percent.md) | Battery percentage | - | macOS only |
| [battery-](battery-inverse.md) | Battery used (inverse) | - | macOS only |
| [batt_volt](battery-voltage.md) | Battery voltage | - | macOS only |
| [batt_amp](battery-amperage.md) | Battery amperage | - | macOS only |
| [batt_cycles](battery-cycles.md) | Battery charge cycles | - | macOS only |
| **Process Sensor** | | | |
| [proc](proc.md) | Process count | `param=filter` | All |
| **Folder Sensors** | | | |
| [foldersize](foldersize.md) | Folder size in bytes | `param=/path` | All |
| [filecount](filecount.md) | File count in folder | `param=/path` | All |
| [foldercount](foldercount.md) | Subfolder count | `param=/path` | All |
| **Network Sensor** | | | |
| [pingtime](pingtime.md) | Connection latency | `param=host` | All |

## Sensor Categories

### System Monitoring
- **[CPU Sensors](cpu.md)**: Monitor processor load and utilization
- **[Memory Sensors](mem.md)**: Track RAM usage and availability
- **[Disk Sensors](disk.md)**: Monitor disk space usage

### Power Management
- **[Battery Sensors](battery.md)**: Comprehensive battery monitoring (macOS only)
  - Charge level, percentage, voltage, amperage, cycles

### Process Management
- **[Process Sensor](proc.md)**: Count and filter running processes

### File System
- **[Folder Sensors](foldersize.md)**: Monitor directories
  - Size, file count, subfolder count
  - Supports recursive operations and filtering

### Network
- **[Network Sensor](pingtime.md)**: Measure connection latency

## Usage Patterns

### Basic Usage

```bash
# CPU usage
curl "http://server/index.php?key=cpu"

# Memory percentage
curl "http://server/index.php?key=mem%"

# Disk usage for root
curl "http://server/index.php?key=disk&param=/"
```

### With Configuration Generation

Add `config=1` to generate MRTG configuration:

```bash
curl "http://server/index.php?key=cpu&config=1"
```

### With Debugging

Add `debug=1` to enable debug output:

```bash
curl "http://server/index.php?key=mem&debug=1"
```

### With Options

Some sensors support additional options:

```bash
# Recursive file count with name filter
curl "http://server/index.php?key=filecount&param=/var/log&options=recursive=1,name=*.log"
```

## Platform Support

| Platform | CPU | Memory | Disk | Battery | Process | Folder | Network |
|----------|-----|--------|------|---------|---------|--------|---------|
| **Linux** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ |
| **macOS** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Windows** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️* | ✅ |
| **BusyBox** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ |

*Windows folder operations require PHP COM extension

## Implementation Details

### Sensor Class

All sensors are implemented in `src/Sensor/Sensor.php` as methods that return `SensorResult` objects.

```php
public function cpuusage(bool $asPercent = false): SensorResult
public function memusage(bool $asPercent = false): SensorResult
public function diskusage(?string $path = null, bool $asPercent = false): SensorResult
public function battery(string $type = ''): SensorResult
public function proccount(?string $filter = null): SensorResult
public function foldersize(?string $path = null, array $options = []): SensorResult
public function filecount(?string $path = null, array $options = []): SensorResult
public function foldercount(?string $path = null, array $options = []): SensorResult
public function pingtime(string $address, int $port = 80): SensorResult
```

### OS Abstraction

Each sensor uses OS-specific implementations via the `OSTools` interface:

- **LinuxTools**: Uses `sys_getloadavg()`, `free`, `df`, `du`, `ps`
- **DarwinTools**: Uses `sysctl`, `vm_stat`, `system_profiler`
- **WindowsTools**: Uses `wmic` commands
- **BusyBoxTools**: Limited command set for Synology NAS

### Caching

All sensor data is cached using phpfastcache:
- Default TTL: 30 seconds for most sensors
- Longer TTL: 3600 seconds for static data (uptime, folder sizes)
- Configurable per command execution

### Security

- **Input Sanitization**: All parameters sanitized via `Sensor::sanitize()`
- **Path Validation**: Disk and folder operations validate paths
- **Read-Only**: No write operations, no sudo/root required
- **Command Injection Prevention**: Shell metacharacters removed

## MRTG Output Format

All sensors return data in standard MRTG 4-line format:

```
<value1>
<value2>
<uptime>
<server>
```

### With config=1

When `config=1` is specified, sensors return complete MRTG configuration:

```
Target[id]: `curl -s "http://server/index.php?key=cpu"`
Title[id]: server: CPU Usage
PageTop[id]: <h1>server: CPU Usage</h1>
ShortLegend[id]: load
Options[id]: growright,nobanner,gauge
MaxBytes[id]: 500
kMG[id]: ,k,M,G,T,P
```

## Error Handling

### Common Error Responses

```
# Unknown sensor type
Error: Unknown sensor type: invalid

# Missing required parameter
Error: Disk sensor requires 'param' parameter

# Invalid path
Error: Path does not exist: /invalid/path

# Platform not supported
Error: Battery sensors only available on macOS
```

### HTTP Status Codes

- **200 OK**: Successful sensor reading
- **400 Bad Request**: Invalid parameters
- **500 Internal Server Error**: Sensor execution failed

## Testing

Each sensor can be tested individually:

```bash
# Test CPU sensor
curl -s "http://localhost/index.php?key=cpu" | head -4

# Test with debug output
curl -s "http://localhost/index.php?key=mem&debug=1"

# Test configuration generation
curl -s "http://localhost/index.php?key=disk&param=/&config=1"
```

## Performance Considerations

### Caching Strategy

- **Fast Sensors** (CPU, Memory): 30-second cache
- **Medium Sensors** (Disk, Process): 30-second cache
- **Slow Sensors** (Folder operations): 3600-second cache
- **Network Sensor**: 30-second cache

### Resource Usage

- **CPU**: Minimal impact, uses system load averages
- **Memory**: Instant, reads from `/proc` or system calls
- **Disk**: Fast, uses `df` command
- **Folder**: Can be slow for large directories (use caching!)
- **Process**: Fast, simple count
- **Network**: Depends on target latency

## Best Practices

### 1. Use Appropriate Sensors

- Use `cpu%` for percentage-based monitoring
- Use `disk%` when absolute bytes aren't needed
- Use `proc` with filters to monitor specific services

### 2. Configure Caching

- Don't disable caching for expensive operations
- Adjust TTL based on update frequency needs
- Use longer TTL for folder operations

### 3. MRTG Configuration

- Use `growright` option for forward-growing graphs
- Use `gauge` for current values (not counters)
- Set appropriate `MaxBytes` for scaling

### 4. Security

- Restrict access via web server config
- Use HTTPS for remote monitoring
- Validate all paths and parameters
- Don't expose debug mode in production

## Contributing

To add a new sensor:

1. Add enum case to `src/Enum/SensorType.php`
2. Add abstract method to `src/OS/OSTools.php`
3. Implement in all OS tool classes
4. Add sensor method to `src/Sensor/Sensor.php`
5. Add route in `public/index.php`
6. Write tests in `tests/Integration/`
7. Document in `docs/sensors/<sensor>.md`

