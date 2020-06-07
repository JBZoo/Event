# JBZoo Event  [![Build Status](https://travis-ci.org/JBZoo/Event.svg?branch=master)](https://travis-ci.org/JBZoo/Event)      [![Coverage Status](https://coveralls.io/repos/JBZoo/Event/badge.svg?branch=master&service=github)](https://coveralls.io/github/JBZoo/Event?branch=master)

#### PHP Library for event-based development

[![License](https://poser.pugx.org/JBZoo/Event/license)](https://packagist.org/packages/JBZoo/Event) [![Latest Stable Version](https://poser.pugx.org/JBZoo/Event/v/stable)](https://packagist.org/packages/JBZoo/Event) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/JBZoo/Event/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/JBZoo/Event/?branch=master)

The EventEmitter is a simple pattern that allows you to create an object that emits events, and allow you to listen to those events.

### Install
```sh
# add to project
composer require jbzoo/event --update-no-dev
# via update
composer update jbzoo/event --no-dev
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
By supplying a priority, you are ensured that subscribers are handled in a specific order. The default priority is EventManager::MID.
Anything below that will be triggered earlier, anything higher later.
If there's two subscribers with the same priority, they will execute in an undefined, but deterministic order.
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


### Benchmarks and performance

See details about each test [here](tests/phpbench)

benchmark | subject | mean | stdev | rstdev | diff
 --- | --- | --- | --- | --- | --- 
ManyCallbacksWithPriority | benchOneUndefined | 0.65μs | 0.01μs | 1.17% | 1.00x
OneCallback | benchOneUndefined | 0.65μs | 0.01μs | 0.84% | 1.00x
ManyCallbacksWithPriority | benchOneWithStarEnd | 0.68μs | 0.01μs | 1.18% | 1.04x
OneCallback | benchOneWithStarEnd | 0.68μs | 0.01μs | 1.02% | 1.04x
ManyCallbacks | benchOneWithStarEnd | 0.69μs | 0.01μs | 1.64% | 1.05x
OneCallback | benchOneSimple | 1.19μs | 0.02μs | 1.46% | 1.81x
Random | benchOneSimple | 4.19μs | 0.21μs | 4.93% | 6.41x
ManyCallbacksWithPriority | benchOneSimple | 4.55μs | 0.02μs | 0.51% | 6.96x


### License

MIT
