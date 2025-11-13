# Disk Usage Percentage Sensor (`disk%`)

Monitors disk space usage as percentage.

## Overview

- **Sensor Key**: `disk%`
- **Parameters**: **Required** `param=/path`

## Usage

```bash
curl "http://server/index.php?key=disk%&param=/"
```

## Output

```
50.00
50.00
1d 12h 35m
myserver
```

Values: Used%, Free%

## See Also

- [Disk Bytes Sensor](disk.md)
