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
 * Class OneCallback
 * @BeforeMethods({"init"})
 * @Revs(10000)
 */
class OneCallback
{
    /**
     * @var EventManager
     */
    private $eManager;

    public function init()
    {
        $this->eManager = new EventManager();
        $this->eManager
            ->on('foo', function () {
                // noop
            })
            ->on('foo.bar', function () {
                // noop
            });
    }

    public function benchOneSimple()
    {
        $this->eManager->trigger('foo');
    }

    public function benchOneNested()
    {
        $this->eManager->trigger('foo.bar');
    }

    public function benchOneNestedStar1()
    {
        $this->eManager->trigger('foo.*');
    }

    public function benchOneNestedStar2()
    {
        $this->eManager->trigger('*.bar');
    }

    public function benchOneNestedStarAll()
    {
        $this->eManager->trigger('*.*');
    }

    public function benchOneUndefined()
    {
        $this->eManager->trigger('undefined');
    }
}