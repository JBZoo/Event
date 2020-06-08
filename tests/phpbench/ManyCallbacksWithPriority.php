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
 * Class ManyCallbacksWithPriority
 * @BeforeMethods({"init"})
 * @Revs(100000)
 * @Iterations(10)
 */
class ManyCallbacksWithPriority
{
    /**
     * @var EventManager
     */
    private $eManager;

    public function init()
    {
        $this->eManager = new EventManager();

        for ($i = 0; $i < 5; $i++) {
            $this->eManager
                ->on('foo', function () {
                    // noop
                }, 5 - $i)
                ->on('foo.bar', function () {
                    // noop
                }, 5 - $i)
                ->on('foo', function () {
                    // noop
                }, 5 + $i)
                ->on('foo.bar', function () {
                    // noop
                }, 5 + $i);
        }
    }

    /**
     * @Groups({"foo"})
     */
    public function benchOneSimple()
    {
        $this->eManager->trigger('foo');
    }

    /**
     * @Groups({"foo.bar"})
     */
    public function benchOneNested()
    {
        $this->eManager->trigger('foo.bar');
    }

    /**
     * @Groups({"foo.*"})
     */
    public function benchOneWithStarEnd()
    {
        $this->eManager->trigger('foo.*');
    }

    /**
     * @Groups({"*.bar"})
     */
    public function benchOneWithStarBegin()
    {
        $this->eManager->trigger('*.bar');
    }

    /**
     * @Groups({"*.*"})
     */
    public function benchOneNestedStarAll()
    {
        $this->eManager->trigger('*.*');
    }

    /**
     * @Groups({"undefined"})
     */
    public function benchOneUndefined()
    {
        $this->eManager->trigger('undefined');
    }
}