# JBZoo Event  [![Build Status](https://travis-ci.org/JBZoo/Event.svg?branch=master)](https://travis-ci.org/JBZoo/Event)      [![Coverage Status](https://coveralls.io/repos/JBZoo/Event/badge.svg?branch=master&service=github)](https://coveralls.io/github/JBZoo/Event?branch=master)

#### PHP Library for event-based development

[![License](https://poser.pugx.org/JBZoo/Event/license)](https://packagist.org/packages/JBZoo/Event) [![Latest Stable Version](https://poser.pugx.org/JBZoo/Event/v/stable)](https://packagist.org/packages/JBZoo/Event) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/JBZoo/Event/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/JBZoo/Event/?branch=master)

The EventEmitter is a simple pattern that allows you to create an object that emits events, and allow you to listen to those events.

### Install
```sh
composer require jbzoo/event
```


### Simple example
```php
require_once './vendor/autoload.php'; // composer autoload.php

// Get needed classes
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
benchOneUndefined |  | 3 | 100000 | 0.65μs | 0.00μs | 0.54% | 6,291,456b | 1.00x
benchOneWithStarBegin |  | 3 | 100000 | 0.67μs | 0.00μs | 0.34% | 6,291,456b | 1.04x
benchOneWithAllStars |  | 3 | 100000 | 0.68μs | 0.01μs | 1.64% | 6,291,456b | 1.06x
benchOneWithStarEnd | readme | 3 | 100000 | 0.69μs | 0.02μs | 3.57% | 6,291,456b | 1.06x
benchOneNested |  | 3 | 100000 | 43.00μs | 0.08μs | 0.18% | 6,291,456b | 66.35x
benchOneSimple |  | 3 | 100000 | 43.38μs | 0.61μs | 1.40% | 6,291,456b | 66.94x

**benchmark: ManyCallbacksWithPriority**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneUndefined | readme | 3 | 100000 | 0.65μs | 0.01μs | 1.24% | 6,291,456b | 1.00x
benchOneNestedStarAll |  | 3 | 100000 | 0.66μs | 0.00μs | 0.66% | 6,291,456b | 1.01x
benchOneWithStarBegin |  | 3 | 100000 | 0.67μs | 0.01μs | 1.28% | 6,291,456b | 1.02x
benchOneWithStarEnd | readme | 3 | 100000 | 0.67μs | 0.01μs | 1.04% | 6,291,456b | 1.03x
benchOneSimple | readme | 3 | 100000 | 4.54μs | 0.02μs | 0.47% | 6,291,456b | 6.96x
benchOneNested |  | 3 | 100000 | 4.60μs | 0.04μs | 0.89% | 6,291,456b | 7.05x

**benchmark: OneCallback**

subject | groups | its | revs | mean | stdev | rstdev | mem_real | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- 
benchOneUndefined | readme | 3 | 100000 | 0.65μs | 0.01μs | 0.87% | 6,291,456b | 1.00x
benchOneNestedStarAll |  | 3 | 100000 | 0.66μs | 0.01μs | 0.92% | 6,291,456b | 1.02x
benchOneWithStarEnd | readme | 3 | 100000 | 0.68μs | 0.01μs | 1.60% | 6,291,456b | 1.05x
benchOneWithStarBegin |  | 3 | 100000 | 0.68μs | 0.01μs | 0.90% | 6,291,456b | 1.05x
benchOneSimple | readme | 3 | 100000 | 1.17μs | 0.02μs | 1.30% | 6,291,456b | 1.80x
benchOneNested |  | 3 | 100000 | 1.20μs | 0.01μs | 0.80% | 6,291,456b | 1.85x


## Unit tests and check code style
```sh
make update
make test-all
```


## License

MIT
