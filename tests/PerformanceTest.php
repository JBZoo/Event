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
 * @coversNothing
 */
class PerformanceTest extends PHPUnit
{
    public function testOneCallBack()
    {
        $eManager = new EventManager();
        $eManager->on('foo', function () {
            // noop
        });

        runBench(array(
            'One' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ), array('name' => 'One callback', 'count' => 10000));
    }

    public function testManyCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            });
        }

        runBench(array(
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ), array('name' => 'Many callback', 'count' => 1000));
    }

    public function testManyPrioritizedCallBacks()
    {
        $eManager = new EventManager();

        for ($i = 0; $i < 100; $i++) {
            $eManager->on('foo', function () {
                // noop
            }, 1000 - $i);
        }

        runBench(array(
            'Many' => function () use ($eManager) {
                $eManager->trigger('foo');
            },
        ), array('name' => 'Many Prioritized CallBacks', 'count' => 1000));
    }

    public function testComplexRandom()
    {
        $eManager = new EventManager();

        $parts = array('foo', 'bar', 'woo', 'bazz', '*', '*', '*');

        for ($i = 0; $i < 100; $i++) {

            shuffle($parts);
            $partsRand = implode('.', array_slice($parts, 0, mt_rand(1, count($parts))));

            if ($partsRand === '*') {
                $partsRand .= '.foo';
            }

            $eManager->on($partsRand, function () {
                // noop
            }, mt_rand(0, $i));
        }

        runBench(array(
            'Many' => function () use ($eManager) {

                $parts = array('foo', 'bar', 'woo', 'bazz');
                shuffle($parts);
                $partsRand = implode('.', array_slice($parts, 0, mt_rand(1, count($parts))));

                $eManager->trigger($partsRand);
            },
        ), array('name' => 'Complex random', 'count' => 1000));
    }
}
