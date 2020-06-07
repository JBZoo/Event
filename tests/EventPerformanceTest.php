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
use JBZoo\Utils\Sys;

/**
 * Class EventPerformanceTest
 * @package JBZoo\Event
 * @coversNothing
 */
class EventPerformanceTest extends PHPUnit
{
    const ITERATIONS = 1000000;

    protected function setUp(): void
    {
        skip('Legacy');

        if (Sys::hasXdebug()) {
            skip('xDebug is enabled. Test is skipped.');
        }

        parent::setUp();
    }

    public function testComplexRandom()
    {
        $eManager = new EventManager();



        Benchmark::compare([
            'Many' => function () use ($eManager) {


            },
        ], ['name' => 'Complex random', 'count' => self::ITERATIONS]);

        isTrue(true);
    }
}
