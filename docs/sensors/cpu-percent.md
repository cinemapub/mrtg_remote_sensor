# CPU Usage Percentage Sensor (`cpu%`)

Monitors CPU utilization as a percentage (0-100%).

## Overview

- **Sensor Key**: `cpu%`
- **Platforms**: Linux, macOS, Windows, BusyBox
- **Parameters**: None
- **Cache TTL**: 30 seconds
- **MRTG Type**: Gauge

## Description

The CPU percentage sensor reports CPU utilization normalized to 0-100%, making it easier to interpret than raw load averages. Calculates utilization by dividing load average by number of cores.

## Usage

### Basic Request

```bash
curl "http://server/index.php?key=cpu%"
```

### Output Format

```
62.50
45.05
1d 12h 35m
4 cores x 2.4 GHz (bogomips 9600)
```

**Fields**:
1. **Value 1**: 5-minute CPU usage percentage
2. **Value 2**: 15-minute CPU usage percentage
3. **Uptime**: System uptime
4. **Server**: CPU information

### MRTG Configuration

```mrtg
Target[server_cpu_pct]: `curl -s "http://server/index.php?key=cpu%"`
Title[server_cpu_pct]: Server: CPU Usage Percentage
PageTop[server_cpu_pct]: <h1>Server: CPU Usage %</h1>
LegendI[server_cpu_pct]: 5 min usage
LegendO[server_cpu_pct]: 15 min usage
ShortLegend[server_cpu_pct]: %
Options[server_cpu_pct]: growright,nobanner,gauge,nopercent
MaxBytes[server_cpu_pct]: 1000
YLegend[server_cpu_pct]: CPU Usage %
```

## Calculation

```
CPU% = (Load Average / Number of Cores) × 100
```

**Example** (4-core system):
- Load 2.0 = 2.0 / 4 × 100 = 50%
- Load 4.0 = 4.0 / 4 × 100 = 100%

## vs. cpu Sensor

| Aspect | `cpu` | `cpu%` |
|--------|-------|--------|
| **Values** | Raw load (0-∞) | Percentage (0-100) |
| **Scaling** | Per-core | Normalized |
| **Max** | 500 × cores | 1000 (100%) |
| **Easier?** | No | ✅ Yes |

## Implementation

**Location**: `src/Sensor/Sensor.php:27` (same method, parameter `$asPercent = true`)

```php
return new SensorResult(
    value1: round($load5 * 100 / $cores, 2),
    value2: round($load15 * 100 / $cores, 2),
    // ...
    mrtgMaxBytes: 1000,  // 100% = 1000 in MRTG
);
```

## See Also

- [CPU Load Sensor](cpu.md)
- [Sensors Overview](README.md)
