# JBZoo / Event

[![CI](https://github.com/JBZoo/Event/actions/workflows/main.yml/badge.svg?branch=master)](https://github.com/JBZoo/Event/actions/workflows/main.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/JBZoo/Event/badge.svg?branch=master)](https://coveralls.io/github/JBZoo/Event?branch=master)
[![Psalm Coverage](https://shepherd.dev/github/JBZoo/Event/coverage.svg)](https://shepherd.dev/github/JBZoo/Event)
[![Psalm Level](https://shepherd.dev/github/JBZoo/Event/level.svg)](https://shepherd.dev/github/JBZoo/Event)
[![CodeFactor](https://www.codefactor.io/repository/github/jbzoo/event/badge)](https://www.codefactor.io/repository/github/jbzoo/event/issues)

[![Stable Version](https://poser.pugx.org/jbzoo/event/version)](https://packagist.org/packages/jbzoo/event/)
[![Total Downloads](https://poser.pugx.org/jbzoo/event/downloads)](https://packagist.org/packages/jbzoo/event/stats)
[![Dependents](https://poser.pugx.org/jbzoo/event/dependents)](https://packagist.org/packages/jbzoo/event/dependents?order_by=downloads)
[![GitHub License](https://img.shields.io/github/license/jbzoo/event)](https://github.com/JBZoo/Event/blob/master/LICENSE)


A lightweight PHP event manager library that provides a simple yet powerful pattern for event-driven development. Create objects that emit events and register listeners to handle them with support for priorities, namespaces, and wildcard matching.

## Features

- **Priority-based event handling** - Control execution order with built-in priority levels
- **Namespace support with wildcards** - Listen to `item.*`, `*.save`, or `*.*` patterns
- **Multiple callback types** - Closures, functions, static methods, and object methods
- **Event propagation control** - Stop event chains with `ExceptionStop`
- **One-time listeners** - Auto-removing listeners with `once()`
- **Reference parameter passing** - Communicate between listeners
- **High performance** - Optimized for speed with comprehensive benchmarks
- **PHP 8.2+ with strict types** - Modern PHP with full type safety

## Installation
```sh
composer require jbzoo/event
```

## Usage

### Quick Start
```php
use JBZoo\Event\EventManager;

$eManager = new EventManager();

// Simple
$eManager->on('create', function () {
    echo "Something action";
});

// Just do it!
$eManager->trigger('create');
```


### Priority-Based Execution
By supplying a priority, you are ensured that subscribers handle in a specific order. The default priority is EventManager::MID.
Anything below that will be triggered earlier, anything higher later.
If there are two subscribers with the same priority, they will execute in an undefined, but deterministic order.
```php
// Run it first
$eManager->on('create', function () {
    echo "Something high priority action";
}, EventManager::HIGH);

// Run it latest
$eManager->on('create', function () {
    echo "Something another action";
}, EventManager::LOW);

// Custom index
$eManager->on('create', function () {
    echo "Something action";
}, 42);

// Don't care...
$eManager->on('create', function () {
    echo "Something action";
});
```

### Callback Types
All standard PHP callable types are supported:
```php
$eManager->on('create', function(){ /* ... */ }); // Custom function
$eManager->on('create', 'myFunction');            // Custom function name
$eManager->on('create', ['myClass', 'myMethod']); // Static function
$eManager->on('create', [$object, 'Method']);     // Method of instance
```


### Event Propagation Control
```php
use JBZoo\Event\ExceptionStop;

$eManager->on('create', function () {
    throw new ExceptionStop('Some reason'); // Special exception for JBZoo/Event
});

$eManager->trigger('create'); // Returns count of executed listeners
```


### Passing Arguments
Event data can be passed to listeners as function arguments:
```php
$eManager->on('create', function ($entityId) {
    echo "An entity with id ", $entityId, " just got created.\n";
});
$entityId = 5;
$eManager->trigger('create', [$entityId]);
```

Because you cannot really do anything with the return value of a listener, you can pass arguments by reference to communicate between listeners and back to the emitter.
```php
$eManager->on('create', function ($entityId, &$warnings) {
    echo "An entity with id ", $entityId, " just got created.\n";
    $warnings[] = "Something bad may or may not have happened.\n";
});
$warnings = [];
$eManager->trigger('create', [$entityId, &$warnings]);
```

### Namespace Wildcards
Use wildcards to listen to multiple related events:
```php
$eManager->on('item.*', function () {
    // item.init
    // item.save
    echo "Any actions with item";
});

$eManager->on('*.init', function () {
    // tag.init
    // item.init
    echo "Init any entity";
});

$eManager->on('*.save', function () {
    // tag.save
    // item.save
    echo "Saving any entity in system";
});

$eManager->on('*.save.after', function () {
    // tag.save.after
    // item.save.after
    echo "Any entity on after save";
});

$eManager->trigger('tag.init');
$eManager->trigger('tag.save.before');
$eManager->trigger('tag.save');
$eManager->trigger('tag.save.after');

$eManager->trigger('item.init');
$eManager->trigger('item.save.before');
$eManager->trigger('item.save');
$eManager->trigger('item.save.after');
```

### One-Time Listeners
For events that should only be handled once:
```php
$eManager->once('app.init', function () {
    echo "This will only run once, then auto-remove itself";
});

$eManager->trigger('app.init'); // Executes
$eManager->trigger('app.init'); // Does nothing - listener was removed
```

### Advanced Usage
```php
use JBZoo\Event\EventManager;

// Create a global event manager
EventManager::setDefault(new EventManager());
$globalManager = EventManager::getDefault();

// Get summary of registered events
$summary = $eManager->getSummeryInfo();
// Returns: ['user.create' => 3, 'user.update' => 1, ...]

// Remove specific listeners
$callback = function() { echo "test"; };
$eManager->on('test', $callback);
$eManager->removeListener('test', $callback);

// Remove all listeners for an event
$eManager->removeListeners('test');

// Remove ALL listeners
$eManager->removeListeners();
```


## Performance Benchmarks

Extensive performance testing with 100,000 iterations shows excellent performance characteristics.
Benchmark tests use [phpbench/phpbench](https://github.com/phpbench/phpbench) - see detailed results in [`tests/phpbench`](tests/phpbench).

> **Note:** `1μs = 1/1,000,000 of a second` - these are microseconds!

**benchmark: ManyCallbacks**

| subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| benchOneUndefined | undefined | 10 | 100000 | 0.65μs | 0.01μs | 1.00% | 6,291,456b | 1.00x |
| benchOneWithStarBegin | *.bar | 10 | 100000 | 0.67μs | 0.01μs | 1.44% | 6,291,456b | 1.04x |
| benchOneWithAllStars | \*.\* | 10 | 100000 | 0.68μs | 0.03μs | 4.18% | 6,291,456b | 1.04x |
| benchOneWithStarEnd | foo.* | 10 | 100000 | 0.68μs | 0.01μs | 1.24% | 6,291,456b | 1.04x |
| benchOneNested | foo.bar | 10 | 100000 | 43.23μs | 0.46μs | 1.07% | 6,291,456b | 66.56x |
| benchOneSimple | foo | 10 | 100000 | 45.07μs | 2.63μs | 5.83% | 6,291,456b | 69.39x |

**benchmark: ManyCallbacksWithPriority**

| subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| benchOneUndefined | undefined | 10 | 100000 | 0.65μs | 0.01μs | 1.35% | 6,291,456b | 1.00x |
| benchOneNestedStarAll | \*.\* | 10 | 100000 | 0.67μs | 0.01μs | 1.34% | 6,291,456b | 1.03x |
| benchOneWithStarBegin | *.bar | 10 | 100000 | 0.67μs | 0.01μs | 1.10% | 6,291,456b | 1.04x |
| benchOneWithStarEnd | foo.* | 10 | 100000 | 0.68μs | 0.01μs | 1.13% | 6,291,456b | 1.05x |
| benchOneSimple | foo | 10 | 100000 | 4.54μs | 0.02μs | 0.35% | 6,291,456b | 7.03x |
| benchOneNested | foo.bar | 10 | 100000 | 4.58μs | 0.04μs | 0.81% | 6,291,456b | 7.10x |

**benchmark: OneCallback**

| subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| benchOneWithStarBegin | *.bar | 10 | 100000 | 0.69μs | 0.03μs | 4.00% | 6,291,456b | 1.00x |
| benchOneWithStarEnd | foo.* | 10 | 100000 | 0.70μs | 0.03μs | 4.22% | 6,291,456b | 1.00x |
| benchOneNestedStarAll | \*.\* | 10 | 100000 | 0.70μs | 0.04μs | 6.02% | 6,291,456b | 1.01x |
| benchOneUndefined | undefined | 10 | 100000 | 0.71μs | 0.05μs | 7.44% | 6,291,456b | 1.02x |
| benchOneSimple | foo | 10 | 100000 | 1.18μs | 0.03μs | 2.27% | 6,291,456b | 1.70x |
| benchOneNested | foo.bar | 10 | 100000 | 1.25μs | 0.03μs | 2.46% | 6,291,456b | 1.81x |

**benchmark: Random**

| subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| benchOneSimple | random.*.triggers | 10 | 100000 | 4.29μs | 0.33μs | 7.69% | 6,291,456b | 1.00x |


## Development

### Setup
```bash
make update  # Install dependencies
```

### Testing
```bash
make test      # Run PHPUnit tests
make test-all  # Run tests + code style checks
make codestyle # Run all linters
```

## License

MIT
