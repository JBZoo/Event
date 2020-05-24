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

namespace JBZoo\PHPUnit;

use JBZoo\Event\EventManager;
use JBZoo\Profiler\Benchmark;
use JBZoo\Utils\Sys;

/**
 * Class EventPerformanceTest
 * @package JBZoo\Event
 * @coversNothing
 */
class EventPerformanceTest extends PHPUnit
{
    const ITERATIONS = 100000;

    protected function setUp(): void
    {
        if (Sys::hasXdebug()) {
            skip('xDebug is enabled. Test is skipped.');
        }

        parent::setUp();
    }

    public function testOneCallBack()
    {
        $eManager = new EventManager();
        $eManager->on('foo', function () {
            // noop
        });

        Benchmark::compare([
            'One' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'One callback', 'count' => self::ITERATIONS]);

        isTrue(true);
    }

    public function testManyCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            });
        }

        Benchmark::compare([
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'Many callback', 'count' => self::ITERATIONS]);

        isTrue(true);
    }

    public function testManyPrioritizedCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            }, 1000 - $i);
        }

        Benchmark::compare([
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'Many Prioritized CallBacks', 'count' => self::ITERATIONS]);

        isTrue(true);
    }

    public function testComplexRandom()
    {
        $eManager = new EventManager();

        $parts = ['foo', 'bar', 'woo', 'bazz', '*', '*', '*'];

        for ($i = 0; $i < 100; $i++) {
            shuffle($parts);
            $partsRand = implode('.', array_slice($parts, 0, random_int(1, count($parts))));

            if ($partsRand === '*') {
                $partsRand .= '.foo';
            }

            $eManager->on($partsRand, function () {
                // noop
            }, random_int(0, $i));
        }

        Benchmark::compare([
            'Many' => function () use ($eManager) {

                $parts = ['foo', 'bar', 'woo', 'bazz'];
                shuffle($parts);
                $partsRand = implode('.', array_slice($parts, 0, random_int(1, count($parts))));

                $eManager->trigger($partsRand);
            },
        ], ['name' => 'Complex random', 'count' => self::ITERATIONS]);

        isTrue(true);
    }
}
