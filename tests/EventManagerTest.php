<?php

/**
 * JBZoo Toolbox - Event
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Event
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Event
 */

declare(strict_types=1);

namespace JBZoo\PHPUnit;

use JBZoo\Event\EventManager;
use JBZoo\Event\ExceptionStop;
use JBZoo\Utils\Str;

/**
 * Class EventManagerTest
 *
 * @package JBZoo\PHPUnit
 */
class EventManagerTest extends PHPUnit
{
    /**
     * @var \Closure
     */
    protected $noop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noop = function () {
        };
    }

    public function testInit()
    {
        $eManager = new EventManager();
        self::assertInstanceOf(EventManager::class, $eManager);
    }

    public function testDefault()
    {
        $eManager = new EventManager();

        EventManager::setDefault($eManager);
        isSame($eManager, EventManager::getDefault());
    }

    public function testLastArgument()
    {
        $eManager = new EventManager();
        $eventName = 'event_' . Str::random();

        $callback = function () use ($eventName) {
            $args = func_get_args();
            isSame($eventName, end($args));
        };

        $eManager->on($eventName, $callback);
        isSame(1, $eManager->trigger($eventName, ['1', 2, 3, 5]));
    }

    public function testListeners()
    {
        $eManager = new EventManager();

        $callback1 = function () {
        };

        $callback2 = function () {
        };

        $eManager->on('foo', $callback1, 200);
        $eManager->on('foo', $callback2, 100);

        is([$callback2, $callback1], $eManager->getList('foo'));
    }

    /**
     * @depends testInit
     */
    public function testHandleEvent()
    {
        $argResult = null;

        $eManager = new EventManager();
        $eManager->on('foo', function ($arg) use (&$argResult) {
            $argResult = $arg;
        });

        isTrue($eManager->trigger('foo', ['bar']));
        is('bar', $argResult);
    }

    /**
     * @depends testHandleEvent
     */
    public function testCancelEvent()
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager->on('foo', function () use (&$argResult) {
            $argResult = 1;
            throw new ExceptionStop('Something wrong');
        });

        $eManager->on('foo', function () use (&$argResult) {
            $argResult = 2;
        });

        $result = $eManager->trigger('foo', ['bar']);
        is(0, $result);
        is(1, $argResult);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriority()
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager->on('foo', function () use (&$argResult) {
            $argResult = 1;
            throw new ExceptionStop('Something wrong #1');
        }, EventManager::LOW);

        $eManager->on('foo', function () use (&$argResult) {
            $argResult = 2;
            throw new ExceptionStop('Something wrong #2');
        }, EventManager::MID);

        is(0, $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    public function testPriority2()
    {
        $result = [];
        $eManager = new EventManager();

        $eManager
            ->on('foo', function () use (&$result) {
                $result[] = 'b';
            }, EventManager::HIGH)
            ->on('foo', function () use (&$result) {
                $result[] = 'f';
            }, -1000)
            ->on('foo', function () use (&$result) {
                $result[] = 'c';
            }, EventManager::MID)
            ->on('foo', function () use (&$result) {
                $result[] = 'a';
            }, EventManager::HIGHEST)
            ->on('foo', function () use (&$result) {
                $result[] = 'e';
            }, EventManager::LOWEST)
            ->on('foo', function () use (&$result) {
                $result[] = 'd';
            }, EventManager::LOW)
            ->trigger('foo');

        is(['a', 'b', 'c', 'd', 'e', 'f'], $result);
    }

    public function testRemoveListener()
    {
        $result = false;
        $eManager = new EventManager();

        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager->on('foo', $callback);

        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($eManager->removeListener('foo', $callback));

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveUnknownListener()
    {
        $result = false;

        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);
        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isFalse($eManager->removeListener('bar', $callback));
        $eManager->trigger('foo');
        isTrue($result);
    }

    public function testRemoveListenerTwice()
    {
        $result = false;

        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);
        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($eManager->removeListener('foo', $callback));
        isFalse($eManager->removeListener('foo', $callback));

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveAllListeners()
    {
        $result = false;
        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);
        $eManager->trigger('foo');
        isTrue($result);
        $result = false;

        $eManager->removeListeners('foo');

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveAllListenersNoArg()
    {
        $result = false;
        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);

        is(1, $eManager->trigger('foo'));
        isTrue($result);
        $result = false;

        $eManager->removeListeners();

        is(0, $eManager->trigger('foo'));
        isFalse($result);
    }

    public function testOnce()
    {
        $result = 0;

        $eManager = new EventManager();
        $eManager->once('foo', function () use (&$result) {
            $result++;
        });

        $eManager->trigger('foo');
        $eManager->trigger('foo');

        is(1, $result);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriorityOnce()
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager
            ->once('foo', function () use (&$argResult) {
                $argResult = 1;
                throw new ExceptionStop('Something wrong #1');
            }, EventManager::HIGH)
            ->once('foo', function () use (&$argResult) {
                $argResult = 2;
                throw new ExceptionStop('Something wrong #2');
            }, EventManager::HIGHEST);

        is(0, $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    public function testContinueCallBack()
    {
        $testVar = 0;

        $eManager = new EventManager();
        $eManager
            ->on('foo', function () {
                // noop
            })
            ->on('foo', function () {
                // noop
            });

        // Set true with $continueCallBack
        $eManager->trigger('foo', [], function () use (&$testVar) {
            $testVar = 1;
            return true;
        });

        is(1, $testVar);
    }

    public function testCallbackFail()
    {
        $testVar = 0;
        $eManager = new EventManager();

        $eManager
            ->on('foo', function () use (&$testVar) {
                $testVar += 2;
            })
            ->on('foo', function () use (&$testVar) {
                $testVar += 4;
            })
            ->trigger('foo', [], function () {
                return false; // force fail after first action
            });

        is(2, $testVar);

        $eManager->trigger('foo', [], function () {
            return true; // force after first action
        });
        is(8, $testVar);


        $eManager->trigger('foo', [], function () {
        });

        is(10, $testVar);

        $eManager->trigger('foo');
        is(16, $testVar);
    }

    public function testStopViaContinueCallback()
    {
        $testVar = 0;

        $eManager = new EventManager();
        $eManager
            ->on('foo', function () use (&$testVar) {
                $testVar += 2;
            }, 3)
            ->on('foo', function () use (&$testVar) {
                $testVar += 4;
                throw new ExceptionStop('Something wrong');
            }, 2)
            ->on('foo', function () use (&$testVar) {
                $testVar += 8;
            }, 1);

        is(0, $testVar);

        $eManager->trigger('foo', [], function () {
            // noop
        });

        is(2, $testVar);
    }

    public function testEventNameCleaner()
    {
        is('foo', EventManager::cleanEventName('FOO'));
        is('foo', EventManager::cleanEventName('FOO.'));
        is('foo', EventManager::cleanEventName('.FOO.'));

        is('foo.bar', EventManager::cleanEventName('FOO.bar'));

        is('*', EventManager::cleanEventName('*'));
        is('foo.*', EventManager::cleanEventName('FOO.*'));
        is('foo.*', EventManager::cleanEventName('FOO.*.'));
        is('foo.123', EventManager::cleanEventName('FOO.123'));

        // too slow to handle the next cases
        //is('foo', $eManager->cleanEventName(' . FOO . '));
        //is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO .. bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO ... bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO .... bar'));
        //is('foo.bar', $eManager->cleanEventName('FOO .. . . bar'));
        //is('foo', $eManager->cleanEventName('FOO.#$%^&()'));
    }

    public function testEmptyTrigger()
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->trigger(' ');
    }

    public function testListenersEmpty()
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->getList(' ');
    }

    public function testRunAll()
    {
        $testVar = 0;

        $eManager = new EventManager();

        $eManager
            ->on('*', function () use (&$testVar) {
                $testVar += 2;
            })
            ->on('*.*', function () use (&$testVar) {
                $testVar += 4;
            })
            ->on('foo.qwerty', function () use (&$testVar) {
                $testVar += 8;
            });

        $eManager->trigger('foo.qwerty');
        isSame(12, $testVar);

        $eManager->trigger('foo');
        isSame(14, $testVar);
    }

    public function testGetList()
    {
        $eManager = new EventManager();

        $eManager
            ->on('foo', $this->noop)
            ->on('*', $this->noop)
            ->on('foo.bar', $this->noop)
            ->on('*.*', $this->noop)
            ->on('foo', $this->noop)
            ->once('foo', $this->noop);

        isSame([
            '*'       => 1,
            '*.*'     => 1,
            'foo'     => 3,
            'foo.bar' => 1
        ], $eManager->getSummeryInfo());
    }

    public function testEmptyEventNameOn()
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->on(' ', function () {
        });
    }

    public function testEmptyEventNameTrigger()
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->trigger(' ');
    }
}
