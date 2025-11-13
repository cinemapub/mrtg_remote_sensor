# Folder Size Sensor (`foldersize`)

Measures total size of a folder in bytes.

## Overview

- **Sensor Key**: `foldersize`
- **Parameters**: **Required** `param=/path`
- **Options**: `recursive=1`, `mtime=+7`, `name=*.log`
- **Cache TTL**: 3600 seconds (1 hour)

## Usage

### Basic

```bash
curl "http://server/index.php?key=foldersize&param=/var/log"
```

### With Options

```bash
# Recursive with file filter
curl "http://server/index.php?key=foldersize&param=/var&options=recursive=1,name=*.log"

# Files modified in last 7 days
curl "http://server/index.php?key=foldersize&param=/tmp&options=mtime=-7"
```

## Output

```
5368709120
5368709120
1d 12h 35m
myserver
```

Values: Folder size in bytes (both values same)

## Options

- `recursive=1`: Include subdirectories
- `name=*.ext`: Filter by filename pattern
- `mtime=+7`: Files older than 7 days
- `mtime=-7`: Files newer than 7 days

## OS Commands

- **Linux/macOS**: `du -sb /path` or `find /path -type f -exec stat -c%s {} + | awk '{s+=$1} END {print s}'`
- **Windows**: Uses PHP COM objects

## Performance

**Warning**: Can be slow for large directories!
- Default cache: 1 hour
- Use recursive sparingly
- Consider filters to reduce scope

## See Also

- [File Count Sensor](filecount.md)
- [Folder Count Sensor](foldercount.md)
