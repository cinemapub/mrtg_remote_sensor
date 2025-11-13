# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**MRTG Remote Sensor** is a modern PHP 8.1+ web service that provides system metrics (CPU, memory, disk, battery, etc.) in MRTG-compatible format. It's designed as a simpler alternative to SNMP for monitoring remote servers via HTTP(S) endpoints.

**Version**: 2.0 (Modernized from legacy PHP 5.x)
**Namespace**: `MrtgSensor\`
**Autoloading**: PSR-4 via Composer
**PHP Version**: 8.1+

The service returns data in standard MRTG format:
1. value1
2. value2
3. uptime
4. hostname

## Modern Architecture (PHP 8.1+)

### Directory Structure

```
src/
├── Cache/               # File-based caching
│   └── FileCache.php
├── Command/            # Command execution
│   └── CommandExecutor.php
├── Enum/               # Type-safe enums (PHP 8.1+)
│   ├── OSType.php      # WINDOWS, LINUX, DARWIN, BUSYBOX
│   └── SensorType.php  # All 17 sensor types
├── Http/               # HTTP layer
│   ├── Request.php     # Readonly request from globals
│   └── Response.php    # Immutable response
├── OS/                 # OS abstraction layer
│   ├── OSDetector.php  # Auto-detect OS
│   ├── OSTools.php     # Abstract base class
│   ├── LinuxTools.php
│   ├── DarwinTools.php
│   ├── WindowsTools.php
│   └── BusyBoxTools.php
├── Output/             # Output formatting
│   └── MrtgFormatter.php
└── Sensor/             # Core business logic
    ├── Sensor.php      # 600+ lines, all 17 sensors
    ├── SensorResult.php # Readonly value object
    └── CommandResult.php # Readonly value object
```

### Modern PHP Features Used

**PHP 8.1+**:
- **Enums**: String-backed enums (`SensorType`, `OSType`)
- **Readonly properties**: Immutable value objects (`SensorResult`)
- **#[\Override] attribute**: Safe method overriding in OS tools

**PHP 8.0+**:
- **Constructor property promotion**: Concise dependency injection
- **Named arguments**: Clear object construction
- **Match expressions**: Clean sensor routing in `index.php`
- **Union types**: `int|float|string` for flexible typing
- **Null-safe operator**: `$param ?? '.'`

**PHP 7.4+**:
- **Typed properties**: All class properties are typed
- **Arrow functions**: Used sparingly in callbacks

**All Files**:
- `declare(strict_types=1)` everywhere
- Full type coverage (properties, parameters, return types)

## Entry Point

### public/index.php

**Key Features**:
- Dependency injection pattern
- Match expressions for OS selection and sensor routing
- Type-safe throughout
- Error handling with try-catch
- No globals used

**Flow**:
1. Load Composer autoloader
2. Create `Request` from `$_GET`
3. Auto-detect OS via `OSDetector::detect()`
4. Create OS-specific tools via match expression
5. Instantiate `Sensor` with dependencies
6. Route to sensor method via match on `SensorType` enum
7. Format output via `MrtgFormatter`
8. Return `Response` with proper headers

**Example Routing**:
```php
$osType = OSDetector::detect();
$osTools = match($osType) {
    OSType::WINDOWS => new WindowsTools($executor),
    OSType::DARWIN => new DarwinTools($executor),
    OSType::BUSYBOX => new BusyBoxTools($executor),
    OSType::LINUX => new LinuxTools($executor),
};

$result = match($sensorType) {
    SensorType::CPU => $sensor->cpuusage(),
    SensorType::CPU_PERCENT => $sensor->cpuusage(true),
    SensorType::MEMORY => $sensor->memusage(),
    // ... all 17 types
};
```

## Core Components

### Enums (Type Safety)

#### src/Enum/SensorType.php
**Purpose**: Type-safe enum for all 17 sensor types
**Backing Type**: `string`
**Methods**:
- `tryFromString(string): ?self` - Case-insensitive lookup

**Values**:
- `CPU`, `CPU_PERCENT` - CPU load and percentage
- `MEMORY`, `MEMORY_PERCENT` - Memory usage
- `DISK`, `DISK_PERCENT` - Disk usage
- `BATTERY`, `BATTERY_PERCENT`, `BATTERY_INVERSE`, `BATTERY_VOLTAGE`, `BATTERY_AMPERE`, `BATTERY_CYCLES` - Battery (macOS only)
- `PROCESS_COUNT` - Process counting
- `FOLDER_SIZE`, `FILE_COUNT`, `FOLDER_COUNT` - File operations
- `PING_TIME` - Network ping

#### src/Enum/OSType.php
**Purpose**: Type-safe enum for supported operating systems
**Backing Type**: `string`
**Values**: `WINDOWS`, `LINUX`, `DARWIN`, `BUSYBOX`

### Value Objects (Immutability)

#### src/Sensor/SensorResult.php
**Purpose**: Immutable data transfer object for sensor results
**Properties** (all readonly):
- `value1`, `value2`: Numeric or string values
- `uptime`: System uptime string
- `server`: Hostname
- `name1`, `name2`: Value labels
- `description`: Human-readable description
- `mrtgUnit`: Unit of measurement
- `mrtgOptions`: MRTG options string
- `mrtgMaxBytes`: Maximum value for scaling
- `mrtgKmg`: Unit scaling factor
- `url`: Sensor URL
- `cfgUrl`: Configuration URL
- `mrtgName`: MRTG counter name
- `version`: Version string

**Note**: Uses constructor property promotion (15 parameters)

#### src/Command/CommandResult.php
**Purpose**: Immutable result from command execution
**Properties**: `output` (string), `success` (bool), `cached` (bool)

### Caching System

#### src/Cache/FileCache.php
**Purpose**: File-based caching with automatic cleanup
**Constructor**: `__construct(private readonly string $cacheDir)`
**Methods**:
- `get(string $key, string $group, int $maxAge = 300): ?string`
- `set(string $key, string $group, string $data): void`
- `getArray(string $key, string $group, int $maxAge = 300): ?array`
- `setArray(string $key, string $group, array $data): void`
- `cleanup(): void` - Remove expired files

**Key Format**: `{group}_{sha1(key)}.cache`
**TTL**: Configurable per operation (30-3600 seconds)

### Command Execution

#### src/Command/CommandExecutor.php
**Purpose**: Execute shell commands with caching and debug support
**Constructor**: `__construct(private readonly FileCache $cache, private readonly bool $debug)`
**Method**:
```php
execute(
    string $command,
    int $cacheTtl = 300,
    string $cacheGroup = 'cmd'
): CommandResult
```

**Features**:
- Automatic caching via `FileCache`
- Debug output (if enabled)
- Returns `CommandResult` with success status

### OS Abstraction Layer

#### src/OS/OSDetector.php
**Purpose**: Auto-detect operating system
**Static Method**: `detect(): OSType`
**Detection Logic**:
1. Check for Synology NAS: `/usr/syno/synoman/` → `BUSYBOX`
2. Check `php_uname('s')`:
   - Starts with "windows" → `WINDOWS`
   - Equals "darwin" → `DARWIN`
   - Default → `LINUX`

#### src/OS/OSTools.php
**Purpose**: Abstract base class for OS-specific implementations
**Constructor**: `__construct(protected readonly CommandExecutor $executor)`
**Abstract Methods** (all return `array` or `int`):
- `cpuload()`: CPU load averages
- `cpupercent()`: CPU percentage
- `meminfo()`: Memory information
- `diskinfo(string $path)`: Disk usage
- `battery()`: Battery information (macOS only)
- `proccount(?string $filter)`: Process count
- `foldersize(string $path, array $options)`: Folder size
- `filecount(string $path, array $options)`: File count
- `foldercount(string $path, array $options)`: Folder count

**Note**: All subclasses use `#[\Override]` attribute for safety

#### src/OS/LinuxTools.php
**Key Commands**:
- `sys_getloadavg()`: CPU load
- `free -b`: Memory info
- `df -B1`: Disk usage
- `du -sb`: Folder size
- `ps aux`: Process listing

#### src/OS/DarwinTools.php
**Key Commands**:
- `sysctl -n vm.loadavg`: CPU load
- `vm_stat`: Memory info
- `df -k`: Disk usage
- `system_profiler SPPowerDataType`: Battery (unique to macOS)

**Battery Parsing**: Complex XML parsing from system_profiler output

#### src/OS/WindowsTools.php
**Key Commands**:
- `wmic cpu get loadpercentage`: CPU
- `wmic OS get FreePhysicalMemory,TotalVisibleMemorySize`: Memory
- `wmic logicaldisk`: Disk usage

**Note**: Folder operations may require PHP COM extension

#### src/OS/BusyBoxTools.php
**Purpose**: Synology NAS support with limited command set
**Detection**: `/usr/syno/synoman/` directory exists
**Limitations**: Simplified commands due to BusyBox environment

### Sensor Logic

#### src/Sensor/Sensor.php (600+ lines)
**Purpose**: Main business logic implementing all 17 sensor types
**Constructor**:
```php
__construct(
    private readonly OSTools $osTools,
    private readonly string $servername,
    private readonly string $url,
    private readonly bool $debug = false
)
```

**Constants**:
- `VERSION = '2.0'` (public class constant)

**Key Methods** (all return `SensorResult`):

**CPU Sensors**:
- `cpuusage(bool $asPercent = false): SensorResult`
  - Absolute: 1-min load average
  - Percent: CPU usage percentage

**Memory Sensors**:
- `memusage(bool $asPercent = false): SensorResult`
  - Absolute: Free/Used bytes
  - Percent: Free/Used percentage

**Disk Sensors**:
- `diskusage(?string $path = null, bool $asPercent = false): SensorResult`
  - Requires `$path` parameter (validated)
  - Absolute: Used/Total bytes
  - Percent: Used/Free percentage

**Battery Sensors** (macOS only):
- `battery(string $type = ''): SensorResult`
  - Uses match expression to select battery type
  - Types: charge%, voltage, amperage, cycles, remaining%, used%

**Process Sensor**:
- `proccount(?string $filter = null): SensorResult`
  - Optional filter parameter (sanitized)

**Folder Sensors**:
- `foldersize(?string $path = null, array $options = []): SensorResult`
- `filecount(?string $path = null, array $options = []): SensorResult`
- `foldercount(?string $path = null, array $options = []): SensorResult`
  - Support `options` array: `recursive`, `mtime`, `name`

**Network Sensor**:
- `pingtime(string $address, int $port = 80): SensorResult`
  - Measures connection time in milliseconds

**Helper Methods**:
- `sanitize(string $input): string` - Remove shell metacharacters
- `buildUrl(string $key, string $param = '', bool $withConfig = false): string`
- `digest(string $base): string` - Generate unique counter ID (SHA1)
- `uptime(): string` - Format system uptime

**Important Notes**:
- All methods populate and return immutable `SensorResult` objects
- Input sanitization via `sanitize()` prevents command injection
- Path validation prevents directory traversal
- Type-safe throughout with union types where needed

### Output Formatting

#### src/Output/MrtgFormatter.php
**Purpose**: Format `SensorResult` as MRTG output
**Methods**:
- `formatStandard(SensorResult $result): string` - 4-line MRTG format
- `formatConfig(SensorResult $result): string` - Full MRTG configuration block

**Standard Format**:
```
{value1}
{value2}
{uptime}
{server}
```

**Config Format**:
```mrtg
Target[name]: `curl -s "{url}"`
Title[name]: {server}: {description}
PageTop[name]: <h1>{server}: {description}</h1>
ShortLegend[name]: {mrtgUnit}
Options[name]: {mrtgOptions}
MaxBytes[name]: {mrtgMaxBytes}
kMG[name]: {mrtgKmg}
```

## Sensor Types Reference

### URL Format
```
http://server/index.php?key={type}&param={value}&options={opts}&config={0|1}&debug={0|1}
```

### Parameters

- **key** (required): Sensor type from `SensorType` enum
- **param** (optional): Sensor-specific parameter
  - Disk: `/path/to/mount`
  - Process: `filter_string`
  - Folder ops: `/path/to/folder`
  - Ping: `hostname_or_ip`
- **options** (optional): Comma-separated `key=value` pairs
  - `recursive=1`: Enable recursive operations
  - `mtime=+7`: Find modification time
  - `name=*.log`: Filename pattern
- **config** (optional): `1` = return full MRTG config, `0` = standard 4-line
- **debug** (optional): `1` = enable debug output

## Development Workflow

### Setup

```bash
# Install dependencies
composer install

# Run tests
composer test

# Static analysis
composer analyse

# Format code
composer format
```

### Adding New Sensors

1. **Add enum case** in `src/Enum/SensorType.php`:
   ```php
   case NEW_SENSOR = 'newsensor';
   ```

2. **Add abstract method** in `src/OS/OSTools.php`:
   ```php
   abstract public function newsensor(): array;
   ```

3. **Implement in all OS tools** (Linux, Darwin, Windows, BusyBox):
   ```php
   #[\Override]
   public function newsensor(): array {
       // OS-specific implementation
   }
   ```

4. **Add method** in `src/Sensor/Sensor.php`:
   ```php
   public function newsensor(): SensorResult {
       $data = $this->osTools->newsensor();
       return new SensorResult(/* ... */);
   }
   ```

5. **Add route** in `public/index.php`:
   ```php
   SensorType::NEW_SENSOR => $sensor->newsensor(),
   ```

6. **Write tests** in `tests/Integration/SensorIntegrationTest.php`

7. **Run validation**:
   ```bash
   composer test
   composer analyse
   composer format
   ```

### Testing Strategy

**Unit Tests** (`tests/Unit/`):
- Test individual classes in isolation
- Example: `FileCacheTest` tests caching logic

**Integration Tests** (`tests/Integration/`):
- Test sensor operations on real OS
- Example: `SensorIntegrationTest` runs actual sensors

**Running Tests**:
```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration

# With coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html var/coverage
```

### Code Quality

**PHPStan Level 8**:
```bash
vendor/bin/phpstan analyse src --level=8
```

Current known issues (non-blocking):
- Array type hints need generics (`array<string, mixed>`)
- Some nullable return types from system functions

**PHP-CS-Fixer (PSR-12)**:
```bash
vendor/bin/php-cs-fixer fix src
```

Automatically formats code to PSR-12 standards.

## Platform-Specific Notes

### macOS (Darwin)
- `free` command doesn't exist (use `vm_stat` instead)
- Battery sensors only work on macOS
- `system_profiler` provides detailed battery info

### Linux
- Uses GNU tools (`free`, `df`, `du`)
- `sys_getloadavg()` for CPU load
- `ps aux` for process listing

### Windows
- All commands via `wmic`
- Folder operations may need COM extension
- Different path separators (`\` vs `/`)

### BusyBox (Synology NAS)
- Limited command set
- Detected via `/usr/syno/synoman/`
- Simplified implementations

## Security Best Practices

### Input Sanitization
All user input goes through `Sensor::sanitize()`:
```php
private function sanitize(string $input): string
{
    return str_replace([';', '"', "'"], '', $input);
}
```

Prevents command injection by removing shell metacharacters.

### Path Validation
All path operations validate before use:
```php
if (!is_dir($path)) {
    // Handle error
}
```

### Read-Only Operations
- No `sudo` or root commands
- No write operations
- All commands are READ-ONLY

### Recommended Deployment
- Use web server IP whitelist
- Enable basic authentication
- Use HTTPS for remote access
- Restrict PHP `exec()` to necessary commands

## Performance Optimization

### Caching Strategy
- Command output cached (default: 300 seconds)
- Static data cached longer (e.g., uptime: 3600s)
- Expensive ops cached longest (folder size: 3600s)
- Automatic cleanup of expired cache files

### Lazy Loading
- PSR-4 autoloading loads classes on demand
- No upfront loading cost

### Efficient Commands
- OS-specific optimizations
- Minimal parsing overhead
- Direct system calls where possible

## MRTG Integration Examples

### CPU Monitoring
```mrtg
Target[server_cpu]: `curl -s "http://server/index.php?key=cpu"`
Title[server_cpu]: Server: CPU Load
PageTop[server_cpu]: <h1>Server: CPU Load</h1>
MaxBytes[server_cpu]: 500
Options[server_cpu]: gauge,growright,nobanner
```

### Memory Monitoring
```mrtg
Target[server_mem]: `curl -s "http://server/index.php?key=mem%"`
Title[server_mem]: Server: Memory Usage
PageTop[server_mem]: <h1>Server: Memory Usage</h1>
MaxBytes[server_mem]: 100
Options[server_mem]: gauge,growright,nobanner
```

### Disk Monitoring
```mrtg
Target[server_disk]: `curl -s "http://server/index.php?key=disk&param=/"`
Title[server_disk]: Server: Root Disk Usage
PageTop[server_disk]: <h1>Server: Root Disk Usage</h1>
MaxBytes[server_disk]: 1000000000000
kMG[server_disk]: k,M,G,T,P
```

### Auto-Generated Config
```bash
curl "http://server/index.php?key=cpu&config=1" >> mrtg.cfg
```

## Backward Compatibility

**100% API compatible** with legacy PHP 5.x version:
- Same URL parameters
- Same MRTG output format
- Same sensor keys
- Existing MRTG configs work unchanged

**Migration Path**:
1. Ensure PHP 8.1+ is installed
2. Run `composer install`
3. Point web server to `public/` directory
4. Done - no MRTG config changes needed

## Code Conventions

### Namespace
All classes use `MrtgSensor\` base namespace with PSR-4 structure.

### Strict Types
Every file starts with:
```php
<?php

declare(strict_types=1);
```

### Type Hints
- All properties are typed
- All parameters are typed
- All return types are declared
- Use union types where needed: `int|float|string`

### Immutability
- Value objects are `readonly`
- Use constructor property promotion
- Return new objects, don't modify existing

### Dependency Injection
- Constructor injection everywhere
- No global variables
- No static state (except utility methods)

### Error Handling
- Use exceptions for errors
- Try-catch in entry point
- Return meaningful error messages

### Comments
- PHPDoc for public API
- Inline comments for complex logic
- No commented-out code

## Troubleshooting

### Common Issues

**"Command not found"**:
- Verify OS detection is correct
- Check if command exists: `which {command}`
- May be expected (e.g., `free` on macOS)

**"Permission denied"**:
- Check PHP can execute commands: `php -r "echo exec('whoami');"`
- Verify `disable_functions` in `php.ini`
- Ensure web server user has permissions

**"Class not found"**:
- Run `composer install`
- Verify autoloader: `vendor/autoload.php` exists
- Check namespace matches file path

**Cache not working**:
- Verify `var/cache/` exists and is writable
- Check file permissions: `chmod 755 var/cache`
- Enable debug mode to see cache hits/misses

**Tests failing**:
- Ensure OS-specific commands are available
- Some tests may be platform-specific
- Check PHPUnit version: `vendor/bin/phpunit --version`

## References

- **MRTG**: https://oss.oetiker.ch/mrtg/
- **PHP 8.1**: https://www.php.net/releases/8.1/
- **PSR-4**: https://www.php-fig.org/psr/psr-4/
- **PSR-12**: https://www.php-fig.org/psr/psr-12/
- **PHPUnit**: https://phpunit.de/
- **PHPStan**: https://phpstan.org/

## Additional Documentation

- **MODERNIZATION_SUMMARY.md**: Detailed transformation report (PHP 5.x → 8.1+)
- **PRPs/2025-11-13.php-modernization.md**: Complete implementation blueprint (1,600+ lines)
- **README.md**: User-facing documentation and installation guide
- **composer.json**: Dependencies and scripts
- **phpunit.xml**: Test configuration
- **phpstan.neon**: Static analysis configuration
