# Folder Count Sensor (`foldercount`)

Counts subdirectories in a directory.

## Overview

- **Sensor Key**: `foldercount`
- **Parameters**: **Required** `param=/path`
- **Cache TTL**: 3600 seconds

## Usage

```bash
curl "http://server/index.php?key=foldercount&param=/var"
```

## Output

```
42
42
1d 12h 35m
myserver
```

Values: Subfolder count (both values same)

## OS Commands

- **Linux/macOS**: `find /path -maxdepth 1 -type d | wc -l`
- **Windows**: Directory enumeration

## See Also

- [Folder Size Sensor](foldersize.md)
- [File Count Sensor](filecount.md)
