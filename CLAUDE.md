# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

JBZoo Event is a lightweight PHP library for event-based development, providing an EventManager with support for event listeners, priorities, namespaces, and wildcard matching.

## Core Architecture

The library consists of three main components in the `src/` directory:

### EventManager (`src/EventManager.php`)
The core event manager class providing:
- Event subscription with priority levels (LOWEST=0, LOW=50, MID=100, HIGH=500, HIGHEST=1000)
- Namespace-based event matching with wildcards (e.g., `item.*`, `*.save`)
- One-time event listeners with `once()`
- Event propagation control via `ExceptionStop`
- Static default manager instance

### Exception Classes
- `Exception.php` - Base exception for the library
- `ExceptionStop.php` - Special exception to halt event propagation

## Common Commands

### Development Setup
```bash
make update          # Install/update dependencies via Composer
```

### Testing
```bash
make test           # Run PHPUnit tests
make test-all       # Run all tests and code style checks
make codestyle      # Run linters (PHPStan, Psalm, PHP-CS-Fixer, etc.)
```

### Individual QA Tools (via JBZoo Toolbox)
```bash
make test-phpstan        # Static analysis
make test-psalm          # Psalm analysis
make test-phpcs          # Code style check
make test-phpcsfixer-fix # Auto-fix code style
make test-phpmd          # Mess detector
```

### Reports
```bash
make report-all         # Generate coverage and analysis reports
make report-coveralls   # Upload coverage to Coveralls
```

## Event System Architecture

### Event Registration
Events are registered using dot notation with support for:
- Simple events: `user.create`
- Namespaced events: `user.profile.update`
- Wildcard matching: `user.*`, `*.save`, `*.*`

### Priority System
Listeners execute in priority order (highest to lowest):
- Multiple listeners at same priority execute in deterministic but undefined order
- Default priority is `EventManager::MID` (100)

### Event Propagation
- Listeners can throw `ExceptionStop` to halt further event processing
- `trigger()` returns count of successfully executed listeners
- Continue callbacks can control execution flow

## Testing Structure

Tests are located in `tests/` directory:
- `EventTest.php` - Core functionality tests
- `EventNamespacesTest.php` - Wildcard and namespace matching
- `EventPackageTest.php` - Package-level integration tests
- `phpbench/` - Performance benchmarks

## Code Standards

- PHP 8.3+ required
- Strict types declaration (`declare(strict_types=1)`)
- PSR-12 coding standard
- Full test coverage expected for new features
- Static analysis with PHPStan level max and Psalm

## Usage Patterns

When working with the EventManager:
1. Use descriptive namespaced event names (`entity.action.phase`)
2. Consider priority when order matters
3. Use `once()` for initialization events
4. Throw `ExceptionStop` to halt propagation when needed
5. Pass data via reference in arguments for listener communication