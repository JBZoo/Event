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

use JBZoo\Event\EventManager;

/**
 * Class Random
 * @BeforeMethods({"init"})
 * @Revs(10000)
 */
class Random
{
    /**
     * @var EventManager
     */
    private $eManager;

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function init()
    {
        $this->eManager = new EventManager();
        $parts = ['foo', 'bar', 'woo', 'bazz', '*', '*', '*'];

        for ($i = 0; $i < 100; $i++) {
            shuffle($parts);
            $partsRand = implode('.', array_slice($parts, 0, random_int(1, count($parts))));

            if ($partsRand === '*') {
                $partsRand .= '.foo';
            }

            $this->eManager->on($partsRand, function () {
                // noop
            }, random_int(0, $i));
        }
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function benchOneSimple()
    {
        $parts = ['foo', 'bar', 'woo', 'bazz'];
        shuffle($parts);
        $partsRand = implode('.', array_slice($parts, 0, random_int(1, count($parts))));

        $this->eManager->trigger($partsRand);
    }
}