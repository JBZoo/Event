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

        is(array($callback2, $callback1), $eManager->getList('foo'));
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

        isTrue($eManager->trigger('foo', array('bar')));
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

        $result = $eManager->trigger('foo', array('bar'));
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

        is('Something wrong #2', $eManager->trigger('foo', array('bar')));
        is(2, $argResult);
    }


    public function testPriority2()
    {
        $result   = array();
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

        is(array('a', 'b', 'c', 'd', 'e', 'f'), $result);
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

        $eManager->removeListeners('foo');

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

        is('Something wrong #2', $eManager->trigger('foo', array('bar')));
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
        $eManager->trigger('foo', array(), function () use (&$testVar) {
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
            ->trigger('foo', array(), function () {
                return false; // force fail after first action
            });

        is(1, $testVar);

        $eManager->trigger('foo', array(), function () {
            return true; // force after first action
        });
        is(3, $testVar);


        $eManager->trigger('foo', array(), function () {
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

        is('Something wrong', $eManager->trigger('foo', array(), function () {
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

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testListenersEmpty()
    {
        $eManager = new EventManager();
        $eManager->getList(' ');
    }

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testListenersAll()
    {
        $eManager = new EventManager();
        $eManager->getList('*');
    }

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testEmptyEventNameOn()
    {
        $eManager = new EventManager();
        $eManager->on(' ', function () {

        });
    }

    /**
     * @expectedException \JBZoo\Event\Exception
     */
    public function testEmptyEventNameTrigger()
    {
        $eManager = new EventManager();
        $eManager->trigger(' ');
    }
}
