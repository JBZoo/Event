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
 * Class PerformanceTest
 * @package JBZoo\Event
 */
class PerformanceTest extends PHPUnit
{
    public function testOneCallBack()
    {
        $eManager = new EventManager();
        $eManager->on('foo', function () {
            // noop
        });

        runBench([
            'One' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'One callback', 'count' => 1000]);
    }

    public function testManyCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            });
        }

        runBench([
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'Many callback', 'count' => 1000]);
    }

    public function testManyPrioritizedCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            }, 1000 - $i);
        }

        runBench([
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ], ['name' => 'Many Prioritized CallBacks', 'count' => 1000]);
    }
}
