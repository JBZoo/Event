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

/**
 * Class ManagerTest
 * @package JBZoo\PHPUnit
 */
class EventsTest extends PHPUnit
{
    function testInit()
    {
        $events = new EventManager();
        $this->assertInstanceOf('JBZoo\\Event\\EventManager', $events);
    }

    function testListeners()
    {
        $events = new EventManager();

        $callback1 = function () {
        };

        $callback2 = function () {
        };

        $events->on('foo', $callback1, 200);
        $events->on('foo', $callback2, 100);

        is([$callback2, $callback1], $events->listeners('foo'));
    }

    /**
     * @depends testInit
     */
    function testHandleEvent()
    {
        $argResult = null;

        $events = new EventManager();
        $events->on('foo', function ($arg) use (&$argResult) {
            $argResult = $arg;
        });

        isTrue($events->trigger('foo', ['bar']));
        is('bar', $argResult);
    }

    /**
     * @depends testHandleEvent
     */
    function testCancelEvent()
    {
        $argResult = 0;

        $events = new EventManager();
        $events->on('foo', function () use (&$argResult) {
            $argResult = 1;
            return false;
        });

        $events->on('foo', function () use (&$argResult) {
            $argResult = 2;
        });

        isFalse($events->trigger('foo', ['bar']));
        is(1, $argResult);
    }

    /**
     * @depends testCancelEvent
     */
    function testPriority()
    {
        $argResult = 0;

        $events = new EventManager();
        $events->on('foo', function () use (&$argResult) {
            $argResult = 1;
            return false;
        });

        $events->on('foo', function () use (&$argResult) {
            $argResult = 2;
            return false;
        }, 1);

        isFalse($events->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    /**
     * @depends testPriority
     */
    function testPriority2()
    {
        $result = [];
        $events = new EventManager();

        $events->on('foo', function () use (&$result) {
            $result[] = 'a';
        }, 200);

        $events->on('foo', function () use (&$result) {
            $result[] = 'b';
        }, 50);

        $events->on('foo', function () use (&$result) {
            $result[] = 'c';
        }, 300);

        $events->on('foo', function () use (&$result) {
            $result[] = 'd';
        });

        $events->trigger('foo');

        is(['b', 'd', 'a', 'c'], $result);
    }

    function testRemoveListener()
    {
        $result = false;
        $events = new EventManager();

        $callBack = function () use (&$result) {
            $result = true;
        };

        $events->on('foo', $callBack);

        $events->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($events->removeListener('foo', $callBack));

        $events->trigger('foo');
        isFalse($result);
    }

    function testRemoveUnknownListener()
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $events = new EventManager();
        $events->on('foo', $callBack);
        $events->trigger('foo');
        isTrue($result);

        $result = false;
        isFalse($events->removeListener('bar', $callBack));
        $events->trigger('foo');
        isTrue($result);
    }

    function testRemoveListenerTwice()
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $events = new EventManager();
        $events->on('foo', $callBack);
        $events->trigger('foo');
        isTrue($result);

        $result = false;
        isTrue($events->removeListener('foo', $callBack));
        isFalse($events->removeListener('foo', $callBack));

        $events->trigger('foo');
        isFalse($result);
    }

    function testRemoveAllListeners()
    {
        $result   = false;
        $callBack = function () use (&$result) {
            $result = true;
        };

        $events = new EventManager();
        $events->on('foo', $callBack);
        $events->trigger('foo');
        isTrue($result);
        $result = false;

        $events->removeAllListeners('foo');

        $events->trigger('foo');
        isFalse($result);
    }

    function testRemoveAllListenersNoArg()
    {
        $result   = false;
        $callBack = function () use (&$result) {
            $result = true;
        };

        $events = new EventManager();
        $events->on('foo', $callBack);

        $events->trigger('foo');
        isTrue($result);
        $result = false;

        $events->removeAllListeners();

        $events->trigger('foo');
        isFalse($result);
    }

    function testOnce()
    {
        $result = 0;

        $callBack = function () use (&$result) {
            $result++;
        };

        $events = new EventManager();
        $events->once('foo', $callBack);

        $events->trigger('foo');
        $events->trigger('foo');

        is(1, $result);
    }

    /**
     * @depends testCancelEvent
     */
    function testPriorityOnce()
    {
        $argResult = 0;

        $events = new EventManager();
        $events->once('foo', function () use (&$argResult) {
            $argResult = 1;
            return false;
        });

        $events->once('foo', function () use (&$argResult) {
            $argResult = 2;
            return false;

        }, 1);

        isFalse($events->trigger('foo', ['bar']));
        is(2, $argResult);
    }
}