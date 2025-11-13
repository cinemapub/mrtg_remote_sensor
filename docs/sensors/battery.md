# Battery Charge Sensor (`battery`)

Monitors battery charge level (macOS only).

## Overview

- **Sensor Key**: `battery`
- **Platform**: macOS only
- **Cache TTL**: 30 seconds

## Usage

```bash
curl "http://server/index.php?key=battery"
```

## Output

```
85
100
1d 12h 35m
MacBook Pro
```

Values: Current charge, Maximum charge

## Implementation

Uses `system_profiler SPPowerDataType` to extract battery information from XML output.

## Related Battery Sensors

- [battery%](battery-percent.md) - Percentage remaining
- [battery-](battery-inverse.md) - Percentage used
- [batt_volt](battery-voltage.md) - Voltage
- [batt_amp](battery-amperage.md) - Amperage
- [batt_cycles](battery-cycles.md) - Charge cycles

## Platform Support

- ✅ macOS - Full support
- ❌ Linux - Not available
- ❌ Windows - Not available
- ❌ BusyBox - Not available
