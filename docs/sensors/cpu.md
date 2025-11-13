# CPU Load Sensor (`cpu`)

Monitors CPU load average over 5 and 15 minute periods.

## Overview

- **Sensor Key**: `cpu`
- **Platforms**: Linux, macOS, Windows, BusyBox
- **Parameters**: None
- **Cache TTL**: 30 seconds
- **MRTG Type**: Gauge

## Description

The CPU sensor reports system load averages, which represent the average number of processes waiting to run. Values are multiplied by 100 for MRTG compatibility.

## Usage

### Basic Request

```bash
curl "http://server/index.php?key=cpu"
```

### Output Format

```
5000
1500
1d 12h 35m
4 cores x 2.4 GHz (bogomips 9600)
```

**Fields**:

1. **Value 1**: 5-minute load average × 100
2. **Value 2**: 15-minute load average × 100
3. **Uptime**: System uptime
4. **Server**: CPU information (cores, frequency, bogomips)

### With Configuration

```bash
curl "http://server/index.php?key=cpu&config=1"
```

**Output**:
```mrtg
Target[server_cpu_abc123]: `curl -s "http://server/index.php?key=cpu"`
Title[server_cpu_abc123]: server: CPU (5/15 min - 4 CPUs)
PageTop[server_cpu_abc123]: <h1>server: CPU (5/15 min - 4 CPUs)</h1>
LegendI[server_cpu_abc123]: Avg load over 5 min
LegendO[server_cpu_abc123]: Avg load over 15 min
ShortLegend[server_cpu_abc123]: load
Options[server_cpu_abc123]: growright,nobanner,gauge
MaxBytes[server_cpu_abc123]: 2000
kMG[server_cpu_abc123]: ,k,M,G,T,P
```

## MRTG Configuration

### Example Configuration

```mrtg
# Monitor CPU load average
Target[myserver_cpu]: `curl -s "http://myserver.local/index.php?key=cpu"`
Title[myserver_cpu]: MyServer: CPU Load Average
PageTop[myserver_cpu]: <h1>MyServer: CPU Load Average</h1>
LegendI[myserver_cpu]: 5 minute average
LegendO[myserver_cpu]: 15 minute average
ShortLegend[myserver_cpu]: load
Options[myserver_cpu]: growright,nobanner,gauge
MaxBytes[myserver_cpu]: 2000
kMG[myserver_cpu]: ,k,M,G,T,P
YLegend[myserver_cpu]: Load Average
Legend1[myserver_cpu]: 5 min avg
Legend2[myserver_cpu]: 15 min avg
```

### MaxBytes Calculation

`MaxBytes = 500 × number_of_cores`

**Examples**:
- 1 core: MaxBytes = 500
- 2 cores: MaxBytes = 1000
- 4 cores: MaxBytes = 2000
- 8 cores: MaxBytes = 4000

## Implementation Details

### Method Signature

```php
public function cpuusage(bool $asPercent = false): SensorResult
```

**Location**: `src/Sensor/Sensor.php:27`

### OS-Specific Implementations

#### Linux (`src/OS/LinuxTools.php`)

Uses `sys_getloadavg()` PHP function:

```php
public function cpuload(): array
{
    $load = sys_getloadavg();
    return [
        '1min' => round($load[0], 3),
        '5min' => round($load[1], 3),
        '15min' => round($load[2], 3),
    ];
}
```

#### macOS (`src/OS/DarwinTools.php`)

Uses `sysctl -n vm.loadavg`:

```php
public function cpuload(): array
{
    $result = $this->executor->execute('sysctl -n vm.loadavg');
    // Parses: { 2.50 1.80 1.20 }
}
```

#### Windows (`src/OS/WindowsTools.php`)

Uses `wmic cpu get loadpercentage`:

```php
public function cpuload(): array
{
    $result = $this->executor->execute('wmic cpu get loadpercentage');
    // Returns simulated load based on current percentage
}
```

#### BusyBox/Synology (`src/OS/BusyBoxTools.php`)

Uses `uptime` command output parsing:

```php
public function cpuload(): array
{
    $result = $this->executor->execute('uptime');
    // Parses: load average: 0.50, 0.15, 0.10
}
```

### CPU Info

Retrieved via `cpuinfo()` method:

```php
[
    'cores' => 4,           // Number of CPU cores
    'ghz' => '2.4',        // Clock speed
    'bogomips' => '9600',  // Performance metric
]
```

## Interpretation

### Load Average Values

- **< 1.0 per core**: System is not busy
- **= 1.0 per core**: System is fully utilized
- **> 1.0 per core**: Processes are waiting (potential bottleneck)

**Examples** (4-core system):
- Load of 2.0 = 50% utilized (healthy)
- Load of 4.0 = 100% utilized (busy)
- Load of 6.0 = 150% utilized (overloaded)

### Time Periods

- **5 minutes**: Recent load trend
- **15 minutes**: Longer-term trend

## Comparison with cpu%

| Feature | `cpu` | `cpu%` |
|---------|-------|--------|
| **Unit** | Load average | Percentage |
| **Scale** | Per-core | 0-100% |
| **Interpretation** | Absolute load | Relative usage |
| **Best For** | System capacity | Utilization rate |

**Recommendation**: Use `cpu%` for easier interpretation of CPU utilization.

## Troubleshooting

### Load Shows as 0

**Possible Causes**:
- System is idle (normal)
- sys_getloadavg() not available
- Platform-specific command failed

**Solution**: Check with `debug=1`:
```bash
curl "http://server/index.php?key=cpu&debug=1"
```

### Load Values Seem High

**Check**:
- Is this normal for your workload?
- Compare 5min vs 15min (spike or sustained?)
- Check number of cores (divide load by cores)

**Example**: Load of 8.0 on an 8-core system = 100% utilization (normal under load)

### Windows Shows Different Values

**Note**: Windows doesn't have traditional load averages. The implementation simulates load based on current CPU percentage.

## Performance Impact

- **Minimal**: Uses system-provided metrics
- **No disk I/O**: Data from kernel/system
- **Cached**: 30-second TTL reduces overhead
- **Instant**: No computation required

## Related Sensors

- **[cpu%](cpu-percent.md)**: CPU usage as percentage (easier to interpret)
- **[proc](proc.md)**: Monitor process count (related to load)

## Examples

### High Load Alert

Monitor when 5-minute load exceeds 80% of capacity:

```mrtg
Target[server_cpu]: `curl -s "http://server/index.php?key=cpu"`
MaxBytes[server_cpu]: 2000
WithPeak[server_cpu]: ymwd
Unscaled[server_cpu]: dwmy
```

### Compare Short vs Long Term

Use both 5min and 15min values to detect:
- **Spikes**: 5min high, 15min low (temporary load)
- **Sustained**: Both high (consistent load)
- **Recovery**: 5min low, 15min high (load decreasing)

## API Response Example

```json
{
    "value1": 250.5,
    "value2": 180.2,
    "uptime": "1d 12h 35m",
    "server": "4 cores x 2.4 GHz (bogomips 9600)",
    "name1": "Avg load over 5 min",
    "name2": "Avg load over 15 min",
    "description": "server: CPU (5/15 min - 4 CPUs)",
    "mrtgUnit": "load",
    "mrtgOptions": "growright,nobanner,gauge",
    "mrtgMaxBytes": 2000,
    "mrtgKmg": ",k,M,G,T,P",
    "url": "http://server/index.php?key=cpu",
    "cfgUrl": "http://server/index.php?key=cpu&config=1",
    "mrtgName": "server_cpu_abc123",
    "version": "2.0"
}
```

## See Also

- [CPU Percentage Sensor](cpu-percent.md)
- [Process Count Sensor](proc.md)
- [Sensors Overview](README.md)
