<?php

/**
 * JBZoo Toolbox - Event.
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @see        https://github.com/JBZoo/Event
 */

declare(strict_types=1);

namespace JBZoo\PHPUnit;

use JBZoo\Event\EventManager;
use JBZoo\Event\ExceptionStop;
use JBZoo\Utils\Str;

class EventTest extends PHPUnit
{
    protected \Closure $noop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noop = static function (): void {
        };
    }

    public function testInit(): void
    {
        $eManager = new EventManager();
        self::assertInstanceOf(EventManager::class, $eManager);
    }

    public function testDefault(): void
    {
        $eManager = new EventManager();

        EventManager::setDefault($eManager);
        isSame($eManager, EventManager::getDefault());
    }

    public function testLastArgument(): void
    {
        $eManager  = new EventManager();
        $eventName = 'event_' . Str::random();

        $callback = static function () use ($eventName): void {
            $args = \func_get_args();
            isSame($eventName, \end($args));
        };

        $eManager->on($eventName, $callback);
        isSame(1, $eManager->trigger($eventName, ['1', 2, 3, 5]));
    }

    public function testListeners(): void
    {
        $eManager = new EventManager();

        $callback1 = static function (): void {
        };

        $callback2 = static function (): void {
        };

        $eManager->on('foo', $callback1, 200);
        $eManager->on('foo', $callback2, 100);

        is([$callback2, $callback1], $eManager->getList('foo'));
    }

    /**
     * @depends testInit
     */
    public function testHandleEvent(): void
    {
        $argResult = null;

        $eManager = new EventManager();
        $eManager->on('foo', static function ($arg) use (&$argResult): void {
            $argResult = $arg;
        });

        isTrue($eManager->trigger('foo', ['bar']) > 0);
        is('bar', $argResult);
    }

    /**
     * @depends testHandleEvent
     */
    public function testCancelEvent(): void
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager->on('foo', static function () use (&$argResult): void {
            $argResult = 1;
            throw new ExceptionStop('Something wrong');
        });

        $eManager->on('foo', static function () use (&$argResult): void {
            $argResult = 2;
        });

        $result = $eManager->trigger('foo', ['bar']);
        is(0, $result);
        is(1, $argResult);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriority(): void
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager->on('foo', static function () use (&$argResult): void {
            $argResult = 1;
            throw new ExceptionStop('Something wrong #1');
        }, EventManager::LOW);

        $eManager->on('foo', static function () use (&$argResult): void {
            $argResult = 2;
            throw new ExceptionStop('Something wrong #2');
        }, EventManager::MID);

        is(0, $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    public function testPriority2(): void
    {
        $result   = [];
        $eManager = new EventManager();

        $eManager
            ->on('foo', static function () use (&$result): void {
                $result[] = 'b';
            }, EventManager::HIGH)
            ->on('foo', static function () use (&$result): void {
                $result[] = 'f';
            }, -1000)
            ->on('foo', static function () use (&$result): void {
                $result[] = 'c';
            }, EventManager::MID)
            ->on('foo', static function () use (&$result): void {
                $result[] = 'a';
            }, EventManager::HIGHEST)
            ->on('foo', static function () use (&$result): void {
                $result[] = 'e';
            }, EventManager::LOWEST)
            ->on('foo', static function () use (&$result): void {
                $result[] = 'd';
            }, EventManager::LOW)
            ->trigger('foo');

        is(['a', 'b', 'c', 'd', 'e', 'f'], $result);
    }

    public function testRemoveListener(): void
    {
        $result   = false;
        $eManager = new EventManager();

        $callback = static function () use (&$result): void {
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

    public function testRemoveUnknownListener(): void
    {
        $result = false;

        $callback = static function () use (&$result): void {
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

    public function testRemoveListenerTwice(): void
    {
        $result = false;

        $callback = static function () use (&$result): void {
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

    public function testRemoveAllListeners(): void
    {
        $result   = false;
        $callback = static function () use (&$result): void {
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

    public function testRemoveAllListenersNoArg(): void
    {
        $result   = false;
        $callback = static function () use (&$result): void {
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

    public function testOnce(): void
    {
        $result = 0;

        $eManager = new EventManager();
        $eManager->once('foo', static function () use (&$result): void {
            $result++;
        });

        $eManager->trigger('foo');
        $eManager->trigger('foo');

        is(1, $result);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriorityOnce(): void
    {
        $argResult = 0;

        $eManager = new EventManager();
        $eManager
            ->once('foo', static function () use (&$argResult): void {
                $argResult = 1;
                throw new ExceptionStop('Something wrong #1');
            }, EventManager::HIGH)
            ->once('foo', static function () use (&$argResult): void {
                $argResult = 2;
                throw new ExceptionStop('Something wrong #2');
            }, EventManager::HIGHEST);

        is(0, $eManager->trigger('foo', ['bar']));
        is(2, $argResult);
    }

    public function testContinueCallBack(): void
    {
        $testVar = 0;

        $eManager = new EventManager();
        $eManager
            ->on('foo', static function (): void {
                // noop
            })
            ->on('foo', static function (): void {
                // noop
            });

        // Set true with $continueCallBack
        $eManager->trigger('foo', [], static function () use (&$testVar) {
            $testVar = 1;

            return true;
        });

        is(1, $testVar);
    }

    public function testCallbackFail(): void
    {
        $testVar  = 0;
        $eManager = new EventManager();

        $eManager
            ->on('foo', static function () use (&$testVar): void {
                $testVar += 2;
            })
            ->on('foo', static function () use (&$testVar): void {
                $testVar += 4;
            })
            ->trigger('foo', [], static function () {
                return false; // force fail after first action
            });

        is(2, $testVar);

        $eManager->trigger('foo', [], static function () {
            return true; // force after first action
        });
        is(8, $testVar);

        $eManager->trigger('foo', [], static function (): void {
        });

        is(10, $testVar);

        $eManager->trigger('foo');
        is(16, $testVar);
    }

    public function testStopViaContinueCallback(): void
    {
        $testVar = 0;

        $eManager = new EventManager();
        $eManager
            ->on('foo', static function () use (&$testVar): void {
                $testVar += 2;
            }, 3)
            ->on('foo', static function () use (&$testVar): void {
                $testVar += 4;
                throw new ExceptionStop('Something wrong');
            }, 2)
            ->on('foo', static function () use (&$testVar): void {
                $testVar += 8;
            }, 1);

        is(0, $testVar);

        $eManager->trigger('foo', [], static function (): void {
            // noop
        });

        is(2, $testVar);
    }

    public function testEventNameCleaner(): void
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
        // is('foo', $eManager->cleanEventName(' . FOO . '));
        // is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO . bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO .. bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO ... bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO .... bar'));
        // is('foo.bar', $eManager->cleanEventName('FOO .. . . bar'));
        // is('foo', $eManager->cleanEventName('FOO.#$%^&()'));
    }

    public function testEmptyTrigger(): void
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->trigger(' ');
    }

    public function testListenersEmpty(): void
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->getList(' ');
    }

    public function testRunAll(): void
    {
        $testVar = 0;

        $eManager = new EventManager();

        $eManager
            ->on('*', static function () use (&$testVar): void {
                $testVar += 2;
            })
            ->on('*.*', static function () use (&$testVar): void {
                $testVar += 4;
            })
            ->on('foo.qwerty', static function () use (&$testVar): void {
                $testVar += 8;
            });

        $eManager->trigger('foo.qwerty');
        isSame(12, $testVar);

        $eManager->trigger('foo');
        isSame(14, $testVar);
    }

    public function testGetList(): void
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
            'foo.bar' => 1,
        ], $eManager->getSummeryInfo());
    }

    public function testEmptyEventNameOn(): void
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->on(' ', static function (): void {
        });
    }

    public function testEmptyEventNameTrigger(): void
    {
        $this->expectException(\JBZoo\Event\Exception::class);

        $eManager = new EventManager();
        $eManager->trigger(' ');
    }
}
