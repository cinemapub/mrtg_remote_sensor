![GitHub Release](https://img.shields.io/github/v/release/cinemapub/mrtg_remote_sensor)

# MRTG Remote Sensor

**Modern PHP 8.1+ System Monitoring for MRTG**

A type-safe, cross-platform web service that provides system metrics (CPU, memory, disk, battery, etc.) in MRTG-compatible format. Built as a simpler alternative to SNMP for monitoring remote servers via HTTP(S) endpoints.

## Features

- **17 Sensor Types**: CPU, Memory, Disk, Battery (6 types), Process count, Folder operations, Network ping
- **Cross-Platform**: Windows, Linux, macOS, BusyBox (Synology NAS)
- **Modern PHP 8.1+**: Enums, readonly properties, strict types, match expressions
- **Type-Safe**: PHPStan level 8 analysis
- **Tested**: PHPUnit integration and unit tests
- **PSR-4 Autoloading**: Composer-based dependency management
- **100% Backward Compatible**: Existing MRTG configs work unchanged

## Requirements

- PHP 8.1 or higher
- Composer
- Web server (Apache, Nginx, built-in PHP server)
- Shell access for system commands (`exec()` enabled)

## Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Web Server

#### Option A: Point Document Root to `public/`

For Apache or Nginx, set the document root to the `public/` directory:

```apache
DocumentRoot /path/to/mrtg_remote_sensor/public
```

#### Option B: PHP Built-in Server (Development)

```bash
php -S localhost:8080 -t public
```

#### Option C: Symlink (Quick Setup)

```bash
ln -s public/index.php index.php
```

### 3. Test Installation

```bash
# CPU usage
curl "http://localhost/index.php?key=cpu"

# Memory usage
curl "http://localhost/index.php?key=mem"

# Disk usage for root
curl "http://localhost/index.php?key=disk&param=/"
```

## Usage

### Available Sensors

| Sensor Key | Description | Parameters | Example |
|------------|-------------|------------|---------|
| `cpu` | CPU load (1-min average) | - | `?key=cpu` |
| `cpu%` | CPU percentage | - | `?key=cpu%` |
| `mem` | Memory usage (bytes) | - | `?key=mem` |
| `mem%` | Memory percentage | - | `?key=mem%` |
| `disk` | Disk usage (bytes) | `param=/path` | `?key=disk&param=/` |
| `disk%` | Disk percentage | `param=/path` | `?key=disk%&param=/home` |
| `battery` | Battery percentage (macOS) | - | `?key=battery` |
| `battery%` | Battery remaining (macOS) | - | `?key=battery%` |
| `battery-` | Battery used (macOS) | - | `?key=battery-` |
| `batt_volt` | Battery voltage (macOS) | - | `?key=batt_volt` |
| `batt_amp` | Battery amperage (macOS) | - | `?key=batt_amp` |
| `batt_cycles` | Battery cycles (macOS) | - | `?key=batt_cycles` |
| `proc` | Process count | `param=filter` | `?key=proc&param=apache` |
| `foldersize` | Folder size (MB) | `param=/path` | `?key=foldersize&param=/var/log` |
| `filecount` | File count | `param=/path` | `?key=filecount&param=/tmp` |
| `foldercount` | Subfolder count | `param=/path` | `?key=foldercount&param=/etc` |
| `pingtime` | Ping time (ms) | `param=host` | `?key=pingtime&param=8.8.8.8` |

### URL Parameters

- `key` (required): Sensor type
- `param` (optional): Sensor-specific parameter (path, hostname, filter)
- `options` (optional): Additional options (comma-separated `key=value`)
- `config=1` (optional): Return full MRTG configuration block
- `debug=1` (optional): Enable debug output

### Options Parameter

For file/folder operations, use `options` for filtering:

```
?key=filecount&param=/var&options=recursive=1,name=*.log
```

Available options:
- `recursive=1`: Enable recursive operations
- `mtime=<value>`: Filter by modification time (passed to `find`)
- `name=<pattern>`: Filter by filename pattern

## MRTG Integration

### Basic Configuration

Add to your `mrtg.cfg`:

```mrtg
Target[server_cpu]: `curl -s "http://server/index.php?key=cpu"`
Title[server_cpu]: Server: CPU Usage
PageTop[server_cpu]: <h1>Server: CPU Usage</h1>
MaxBytes[server_cpu]: 500
Options[server_cpu]: gauge,growright,nobanner
```

### Auto-Generate Configuration

Add `config=1` to get a complete MRTG configuration block:

```bash
curl "http://server/index.php?key=cpu&config=1"
```

Output:
```mrtg
Target[server_cpu_abc123]: `curl -s "http://server/index.php?key=cpu"`
Title[server_cpu_abc123]: server: CPU Usage
PageTop[server_cpu_abc123]: <h1>server: CPU Usage</h1>
ShortLegend[server_cpu_abc123]:
Options[server_cpu_abc123]: gauge,growright,nobanner
MaxBytes[server_cpu_abc123]: 500
kMG[server_cpu_abc123]: k,M,G,T,P
```

### Interactive Testing

Open `public/overview.html` in a browser to test all sensor types interactively.

## Development

### Run Tests

```bash
# All tests
composer test

# Unit tests only
vendor/bin/phpunit tests/Unit

# Integration tests only
vendor/bin/phpunit tests/Integration
```

### Static Analysis

```bash
composer analyse
```

Runs PHPStan level 8 analysis on `src/` directory.

### Code Formatting

```bash
composer format
```

Applies PSR-12 coding standards using PHP-CS-Fixer.

### Project Structure

```
.
├── public/                 # Web root
│   ├── index.php          # Entry point
│   ├── overview.html      # Interactive testing UI
│   └── view.html          # Single sensor view
├── src/                   # PSR-4 source code
│   ├── Cache/            # File caching
│   ├── Command/          # Command execution
│   ├── Enum/             # Type-safe enums
│   ├── Http/             # Request/Response
│   ├── OS/               # OS abstraction layer
│   ├── Sensor/           # Sensor logic & results
│   └── Output/           # MRTG formatting
├── tests/                # PHPUnit tests
│   ├── Unit/            # Unit tests
│   └── Integration/     # Integration tests
├── var/                  # Runtime data
│   └── cache/           # Cache files
├── composer.json         # Dependencies
├── phpunit.xml          # Test configuration
├── phpstan.neon         # Static analysis config
└── .php-cs-fixer.php    # Code style config
```

## Architecture

### Modern PHP 8.1+ Features

- **Enums** (PHP 8.1+): `SensorType`, `OSType` for type safety
- **Readonly Properties** (PHP 8.1+): Immutable `SensorResult` value objects
- **#[\Override] Attribute** (PHP 8.1+): Safe method overriding
- **Constructor Property Promotion** (PHP 8.0+): Concise dependency injection
- **Match Expressions** (PHP 8.0+): Clean sensor routing
- **Union Types** (PHP 8.0+): Flexible type hints (`int|float|string`)
- **Strict Types**: `declare(strict_types=1)` everywhere
- **Typed Properties** (PHP 7.4+): Full type coverage

### Core Components

#### Namespace: `MrtgSensor\`

- **Sensor** (`Sensor\Sensor`): Main business logic, all 17 sensor implementations
- **OSTools** (`OS\OSTools`): Abstract base for OS-specific implementations
  - `LinuxTools`: Linux/Unix commands
  - `DarwinTools`: macOS with battery support
  - `WindowsTools`: Windows via `wmic`
  - `BusyBoxTools`: Synology NAS
- **OSDetector** (`OS\OSDetector`): Auto-detect operating system
- **FileCache** (`Cache\FileCache`): Command output caching
- **CommandExecutor** (`Command\CommandExecutor`): Shell command execution
- **SensorResult** (`Sensor\SensorResult`): Immutable sensor data (15 properties)
- **MrtgFormatter** (`Output\MrtgFormatter`): MRTG output generation
- **Request** (`Http\Request`): Readonly request object
- **Response** (`Http\Response`): Immutable response with factory methods

## Platform Support

### Linux
- Commands: `sys_getloadavg()`, `free`, `df`, `du`, `ps`, `uptime`
- Full support for all sensor types except battery

### macOS (Darwin)
- Commands: `sysctl`, `vm_stat`, `df`, `du`, `system_profiler`, `ps`
- Full battery support via `system_profiler SPPowerDataType`

### Windows
- Commands: `wmic` (CPU, memory, disk, processes)
- Folder operations require PHP COM extension

### BusyBox (Synology NAS)
- Detected via `/usr/syno/synoman/`
- Limited command set with fallbacks

## Security

- **Read-only operations**: No write/modify commands executed
- **No root/sudo**: All commands run with web server privileges
- **Input sanitization**: `Sensor::sanitize()` removes shell metacharacters
- **Type safety**: Strict types and enums prevent type juggling
- **Validated parameters**: Enum validation for sensor types

**Recommended**: Restrict access via web server configuration (IP whitelist, basic auth).

## Performance

- **File-based caching**: Command output cached (30-3600 seconds)
- **Lazy loading**: PSR-4 autoloading loads classes on demand
- **Efficient commands**: OS-specific optimizations
- **Automatic cleanup**: Expired cache files removed

## Migration from Legacy PHP 5.x

The modernized version maintains **100% API compatibility** with the legacy version:

- Same URL parameters and sensor keys
- Same MRTG 4-line output format
- Existing MRTG configurations work unchanged
- Simply run `composer install` and point web server to `public/`

**Note**: Requires PHP 8.1+ (uses enums, readonly properties, and match expressions).

See [MODERNIZATION_SUMMARY.md](MODERNIZATION_SUMMARY.md) for detailed migration information.

## Troubleshooting

### Commands Not Found

On macOS, `free` command doesn't exist (expected). The `DarwinTools` class uses `vm_stat` instead.

### Permission Denied

Ensure PHP can execute shell commands:
```bash
php -r "echo exec('whoami');"
```

If blocked, check `disable_functions` in `php.ini`.

### Cache Directory

Cache is stored in `var/cache/`. Ensure it's writable:
```bash
chmod 755 var/cache
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Run quality checks:
   ```bash
   composer test
   composer analyse
   composer format
   ```
5. Submit a pull request

## License

This project is open source. Check LICENSE file for details.

## Credits

- Original concept: Simple alternative to SNMP for MRTG
- Modernization: PHP 8.1+ refactoring with strict types and testing
- Powered by: MRTG (Multi Router Traffic Grapher)
