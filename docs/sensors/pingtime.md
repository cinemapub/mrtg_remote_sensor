# Network Ping Time Sensor (`pingtime`)

Measures connection latency to a host.

## Overview

- **Sensor Key**: `pingtime`
- **Parameters**: **Required** `param=hostname`
- **Optional**: `port` (default: 80)
- **Cache TTL**: 30 seconds

## Usage

### Basic

```bash
curl "http://server/index.php?key=pingtime&param=google.com"
curl "http://server/index.php?key=pingtime&param=192.168.1.1"
```

### With Port

```bash
# SSH port
curl "http://server/index.php?key=pingtime&param=server.local&port=22"
```

## Output

```
15.250
15.250
1d 12h 35m
myserver
```

Values: Connection time in milliseconds (both values same)

## Implementation

Uses `fsockopen()` to measure TCP connection time:

```php
$start = microtime(true);
$socket = @fsockopen($address, $port, $errno, $errstr, 5);
$pingTime = (microtime(true) - $start) * 1000; // Convert to ms
```

## MRTG Config

```mrtg
Target[server_ping_google]: `curl -s "http://server/index.php?key=pingtime&param=google.com"`
Title[server_ping_google]: Latency to Google
MaxBytes[server_ping_google]: 1000
YLegend[server_ping_google]: Milliseconds
Options[server_ping_google]: growright,nobanner,gauge
```

## Use Cases

- Monitor internet connectivity
- Track latency to external services
- Detect network issues
- Monitor internal service availability

## Troubleshooting

### High Latency

- Check network path
- DNS resolution delays
- Firewall/routing issues
- Target server load

### Connection Failed

- Host unreachable
- Port blocked by firewall
- Service not running
- DNS resolution failed

## See Also

- [Process Sensor](proc.md) - Monitor service processes
- [Sensors Overview](README.md)
