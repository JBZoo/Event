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
use JBZoo\Event\Exception;

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
            throw new Exception('Something wrong');
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
            throw new Exception('Something wrong #1');
        });

        $eManager->on('foo', function () use (&$argResult) {
            $argResult = 2;
            throw new Exception('Something wrong #2');
        }, 1);

        is('Something wrong #2', $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    /**
     * @depends testPriority
     */
    public function testPriority2()
    {
        $result   = [];
        $eManager = new EventManager();

        $eManager
            ->on('foo', function () use (&$result) {
                $result[] = 'a';
            }, 200)
            ->on('foo', function () use (&$result) {
                $result[] = 'b';
            }, 50)
            ->on('foo', function () use (&$result) {
                $result[] = 'c';
            }, 300)
            ->on('foo', function () use (&$result) {
                $result[] = 'd';
            })
            ->trigger('foo');

        is(['b', 'd', 'a', 'c'], $result);
    }

    public function testRemoveListener()
    {
        $result   = false;
        $eManager = new EventManager();

        $callBack = function () use (&$result) {
            $result = true;
        };

        $eManager->on('foo', $callBack);

        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($eManager->removeListener('foo', $callBack));

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveUnknownListener()
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callBack);
        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isFalse($eManager->removeListener('bar', $callBack));
        $eManager->trigger('foo');
        isTrue($result);
    }

    public function testRemoveListenerTwice()
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callBack);
        $eManager->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($eManager->removeListener('foo', $callBack));
        isFalse($eManager->removeListener('foo', $callBack));

        $eManager->trigger('foo');
        isFalse($result);
    }

    public function testRemoveAllListeners()
    {
        $result   = false;
        $callBack = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callBack);
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
        $callBack = function () use (&$result) {
            $result = true;
        };

        $eManager = new EventManager();
        $eManager->on('foo', $callBack);

        $eManager->trigger('foo');
        isTrue($result);
        $result = false;

        $eManager->removeAllListeners();

        $eManager->trigger('foo');
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
                throw new Exception('Something wrong #1');
            })
            ->once('foo', function () use (&$argResult) {
                $argResult = 2;
                throw new Exception('Something wrong #2');
            }, 1);

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
                throw new Exception('Something wrong');
            })
            ->on('foo', function () use (&$testVar) {
                $testVar++;
            });

        is('Something wrong', $eManager->trigger('foo', [], function () {
            // noop
        }));

        is(2, $testVar);
    }

    public function testNamespaces()
    {
        $eManager = new EventManager();

        $eManager
            ->on('foo', function () {

            })
            ->on('foo.bar', function () {

            })
            ->on('foo.bar.woo', function () {

            })
            ->on('foo.bar.buu', function () {

            })
            ->on('foo.woo', function () {

            })
            ->on('foo.woo.', function () {

            })
            ->on('*', function () {

            })
            ->on('foo.*', function () {

            })
            ->on('foo.*.woo', function () {

            })
            ->on('foo.*.woo', function () {

            });

    }

}