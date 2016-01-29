<?php
/**
 * JBZoo Event
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package   Event
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Event
 * @author    Denis Smetannikov <denis@jbzoo.com>
 */

namespace JBZoo\PHPUnit;

use JBZoo\Event\EventManager;
use JBZoo\Event\ExceptionStop;

/**
 * Class ManagerTest
 * @package JBZoo\PHPUnit
 */
class EventsTest extends PHPUnit
{
    public function testInit()
    {
        $eManager = new EventManager();
        $this->assertInstanceOf('JBZoo\\Event\\EventManager', $eManager);
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

        is([$callback2, $callback1], $eManager->listeners('foo'));
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
        is('Something wrong', $result);
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

        is('Something wrong #2', $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }


    public function testPriority2()
    {
        $result   = [];
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
        $result   = false;
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
        $result   = false;
        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);
        $eManager->trigger('foo');
        isTrue($result);
        $result = false;

        $eManager->removeAllListeners('foo');

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveAllListenersNoArg()
    {
        $result   = false;
        $callback = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callback);

        is(1, $eManager->trigger('foo'));
        isTrue($result);
        $result = false;

        $eManager->removeAllListeners();

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

        is('Something wrong #2', $eManager->trigger('foo', ['bar']));
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
        $testVar  = 0;
        $eManager = new EventManager();

        $eManager
            ->on('foo', function () use (&$testVar) {
                $testVar++;
            })
            ->on('foo', function () use (&$testVar) {
                $testVar++;
            })
            ->trigger('foo', [], function () {
                return false; // force fail after first action
            });

        is(1, $testVar);

        $eManager->trigger('foo', [], function () {
            return true; // force after first action
        });
        is(3, $testVar);


        $eManager->trigger('foo', [], function () {
        });
        is(5, $testVar);
    }

    public function testStopViaContinueCallBack()
    {
        $testVar = 0;

        $eManager = new EventManager();
        $eManager
            ->on('foo', function () use (&$testVar) {
                $testVar++;
            })
            ->on('foo', function () use (&$testVar) {
                $testVar++;
                throw new ExceptionStop('Something wrong');
            })
            ->on('foo', function () use (&$testVar) {
                $testVar++;
            });

        is('Something wrong', $eManager->trigger('foo', [], function () {
            // noop
        }));

        is(2, $testVar);
    }


    public function testEventnameCleaner()
    {
        $eManager = new EventManager();

        is('foo', $eManager->cleanEventName('FOO'));
        is('foo', $eManager->cleanEventName('FOO.'));
        is('foo', $eManager->cleanEventName('.FOO.'));
        is('foo', $eManager->cleanEventName(' . FOO . '));
        is('foo.bar', $eManager->cleanEventName('FOO.bar'));
        is('foo.bar', $eManager->cleanEventName('FOO . bar'));

        is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        is('foo.bar', $eManager->cleanEventName('FOO .. bar'));
        is('foo.bar', $eManager->cleanEventName('FOO ... bar'));
        is('foo.bar', $eManager->cleanEventName('FOO .... bar'));
        is('foo.bar', $eManager->cleanEventName('FOO .. . . bar'));

        is('*', $eManager->cleanEventName('*'));
        is('foo.*', $eManager->cleanEventName('FOO.*'));
        is('foo.*', $eManager->cleanEventName('FOO.*.'));
        is('foo.123', $eManager->cleanEventName('FOO.123'));
        is('foo', $eManager->cleanEventName('FOO.#$%^&()'));
    }

    public function testNamespaces()
    {
        $eManager = new EventManager();
        $fnc      = function () {
        };

        $eManager->on('*.save.*', $fnc, 103);
        $eManager->on('*.save.*', $fnc, 102);
        $eManager->on('*.save.*', $fnc, 104);
        $eManager->on('*.save.*', $fnc, 101);
        $eManager->on('*.save.*.*', $fnc, 500);
        $eManager->on('*.save.before', $fnc, 10);
        $eManager->on('item.save.before', $fnc, -1);

        is(6, $eManager->trigger('item.save.before'));

        is(1, $eManager->trigger('item.save.before.deeeep'));
        is(1, $eManager->trigger('category.save.before.deeeep'));

        is(0, $eManager->trigger('item.load'));
        is(0, $eManager->trigger('item.load.before'));
        is(0, $eManager->trigger('item.save'));
        is(4, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.456'));
        is(0, $eManager->trigger('save.before'));
    }

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testEmptyTrigger()
    {
        $eManager = new EventManager();
        $eManager->trigger(' ');
    }

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testTriggerAll()
    {
        $eManager = new EventManager();
        $eManager->trigger(' * ');
    }
}
