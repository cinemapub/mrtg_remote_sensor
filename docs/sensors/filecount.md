# File Count Sensor (`filecount`)

Counts files in a directory.

## Overview

- **Sensor Key**: `filecount`
- **Parameters**: **Required** `param=/path`
- **Options**: `recursive=1`, `name=*.log`
- **Cache TTL**: 3600 seconds

## Usage

```bash
# Count files in directory
curl "http://server/index.php?key=filecount&param=/var/log"

# Recursive with filter
curl "http://server/index.php?key=filecount&param=/var/log&options=recursive=1,name=*.log"
```

## Output

```
1543
1543
1d 12h 35m
myserver
```

Values: File count (both values same)

## See Also

- [Folder Size Sensor](foldersize.md)
- [Folder Count Sensor](foldercount.md)
