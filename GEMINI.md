# GEMINI.md

## Project Overview

This project is a modern PHP 8.1+ web service that provides system metrics in a format compatible with the MRTG (Multi Router Traffic Grapher) monitoring tool. It serves as a type-safe, cross-platform alternative to SNMP for monitoring remote servers via HTTP(S) endpoints.

The service can report on a wide range of system metrics, including:
- CPU usage (load and percentage)
- Memory usage (bytes and percentage)
- Disk usage (bytes and percentage)
- Battery status (for macOS)
- Process count
- Folder size, file count, and subfolder count
- Network ping time

It is designed to be highly compatible with existing MRTG setups, offering a "drop-in" replacement for legacy monitoring scripts. The project is built with modern PHP features, including enums, readonly properties, and strict typing, and it follows PSR-4 autoloading standards.

## Building and Running

### Installation

To install the project dependencies, run the following command:

```bash
composer install
```

### Running the Service

There are a few ways to run the service:

**1. Using the PHP Built-in Web Server (for development)**

```bash
php -S localhost:8080 -t public
```

**2. Using a Standard Web Server (Apache, Nginx, etc.)**

Configure your web server's document root to point to the `public/` directory of the project.

**3. Using a Symbolic Link**

For a quick setup, you can create a symbolic link from the `public/index.php` file to the root of the project:

```bash
ln -s public/index.php index.php
```

### Testing the Installation

You can test the installation by sending requests to the service using `curl` or by opening the `public/overview.html` file in your web browser for an interactive testing interface.

**Example `curl` commands:**

```bash
# Get CPU usage
curl "http://localhost:8080/?key=cpu"

# Get memory usage
curl "http://localhost:8080/?key=mem"

# Get disk usage for the root directory
curl "http://localhost:8080/?key=disk&param=/"
```

## Development Conventions

### Testing

The project uses PHPUnit for testing. To run the test suite, use the following command:

```bash
composer test
```

### Static Analysis

The project uses PHPStan for static analysis. To run the static analysis, use the following command:

```bash
composer analyse
```

### Code Formatting

The project uses PHP-CS-Fixer to enforce a consistent code style. To format the code, use the following command:

```bash
composer format
```

### All Checks

To run all checks (static analysis and tests), use the following command:

```bash
composer check
```
