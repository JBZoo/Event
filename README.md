# JBZoo / Event

[![Build Status](https://travis-ci.org/JBZoo/Event.svg?branch=master)](https://travis-ci.org/JBZoo/Event)    [![Coverage Status](https://coveralls.io/repos/JBZoo/Event/badge.svg)](https://coveralls.io/github/JBZoo/Event?branch=master)    [![Psalm Coverage](https://shepherd.dev/github/JBZoo/Event/coverage.svg)](https://shepherd.dev/github/JBZoo/Event)    
[![Latest Stable Version](https://poser.pugx.org/JBZoo/Event/v)](https://packagist.org/packages/JBZoo/Event)    [![Latest Unstable Version](https://poser.pugx.org/JBZoo/Event/v/unstable)](https://packagist.org/packages/JBZoo/Event)    [![Dependents](https://poser.pugx.org/JBZoo/Event/dependents)](https://packagist.org/packages/JBZoo/Event/dependents?order_by=downloads)    [![GitHub Issues](https://img.shields.io/github/issues/JBZoo/Event)](https://github.com/JBZoo/Event/issues)    [![Total Downloads](https://poser.pugx.org/JBZoo/Event/downloads)](https://packagist.org/packages/JBZoo/Event/stats)    [![GitHub License](https://img.shields.io/github/license/JBZoo/Event)](https://github.com/JBZoo/Event/blob/master/LICENSE)


The EventEmitter is a simple pattern that allows you to create an object that emits events, and allow you to listen to those events.

### Install
```sh
composer require jbzoo/event
```


### Simple example
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


### Set priority
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

### Types of Callback
All default PHP callbacks are supported, so closures are not required.
```php
$eManager->on('create', function(){ /* ... */ }); // Custom function
$eManager->on('create', 'myFunction');            // Custom function name
$eManager->on('create', ['myClass', 'myMethod']); // Static function
$eManager->on('create', [$object, 'Method']);     // Method of instance
```


###  Cancel queue of events
```php
use JBZoo\Event\ExceptionStop;

$eManager->on('create', function () {
    throw new ExceptionStop('Some reason'); // Special exception for JBZoo/Event
});

$eManager->trigger('create'); // return 'Some reason' or TRUE if all events done
```


### Passing arguments
Arguments can be passed as an array.
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

### Namespaces
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


## Summary benchmark info (execution time) PHP v7.4
All benchmark tests are executing without xdebug and with a huge random array and 100.000 iterations.

Benchmark tests based on the tool [phpbench/phpbench](https://github.com/phpbench/phpbench). See details [here](tests/phpbench).   

Please, pay attention - `1μs = 1/1.000.000 of second!`

**benchmark: ManyCallbacks**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneUndefined | undefined | 10 | 100000 | 0.65μs | 0.01μs | 1.00% | 6,291,456b | 1.00x
benchOneWithStarBegin | *.bar | 10 | 100000 | 0.67μs | 0.01μs | 1.44% | 6,291,456b | 1.04x
benchOneWithAllStars | \*.\* | 10 | 100000 | 0.68μs | 0.03μs | 4.18% | 6,291,456b | 1.04x
benchOneWithStarEnd | foo.* | 10 | 100000 | 0.68μs | 0.01μs | 1.24% | 6,291,456b | 1.04x
benchOneNested | foo.bar | 10 | 100000 | 43.23μs | 0.46μs | 1.07% | 6,291,456b | 66.56x
benchOneSimple | foo | 10 | 100000 | 45.07μs | 2.63μs | 5.83% | 6,291,456b | 69.39x

**benchmark: ManyCallbacksWithPriority**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneUndefined | undefined | 10 | 100000 | 0.65μs | 0.01μs | 1.35% | 6,291,456b | 1.00x
benchOneNestedStarAll | \*.\* | 10 | 100000 | 0.67μs | 0.01μs | 1.34% | 6,291,456b | 1.03x
benchOneWithStarBegin | *.bar | 10 | 100000 | 0.67μs | 0.01μs | 1.10% | 6,291,456b | 1.04x
benchOneWithStarEnd | foo.* | 10 | 100000 | 0.68μs | 0.01μs | 1.13% | 6,291,456b | 1.05x
benchOneSimple | foo | 10 | 100000 | 4.54μs | 0.02μs | 0.35% | 6,291,456b | 7.03x
benchOneNested | foo.bar | 10 | 100000 | 4.58μs | 0.04μs | 0.81% | 6,291,456b | 7.10x

**benchmark: OneCallback**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneWithStarBegin | *.bar | 10 | 100000 | 0.69μs | 0.03μs | 4.00% | 6,291,456b | 1.00x
benchOneWithStarEnd | foo.* | 10 | 100000 | 0.70μs | 0.03μs | 4.22% | 6,291,456b | 1.00x
benchOneNestedStarAll | \*.\* | 10 | 100000 | 0.70μs | 0.04μs | 6.02% | 6,291,456b | 1.01x
benchOneUndefined | undefined | 10 | 100000 | 0.71μs | 0.05μs | 7.44% | 6,291,456b | 1.02x
benchOneSimple | foo | 10 | 100000 | 1.18μs | 0.03μs | 2.27% | 6,291,456b | 1.70x
benchOneNested | foo.bar | 10 | 100000 | 1.25μs | 0.03μs | 2.46% | 6,291,456b | 1.81x

**benchmark: Random**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneSimple | random.*.triggers | 10 | 100000 | 4.29μs | 0.33μs | 7.69% | 6,291,456b | 1.00x


## Unit tests and check code style
```sh
make update
make test-all
```


## License

MIT
