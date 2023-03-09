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

use JBZoo\Event\EventManager;

/**
 * @BeforeMethods({"init"})
 * @Revs(100000)
 * @Iterations(10)
 */
class OneCallback
{
    /** @var EventManager */
    private $eManager;

    public function init(): void
    {
        $this->eManager = new EventManager();
        $this->eManager
            ->on('foo', static function (): void {
                // noop
            })
            ->on('foo.bar', static function (): void {
                // noop
            });
    }

    /**
     * @Groups({"foo"})
     */
    public function benchOneSimple(): void
    {
        $this->eManager->trigger('foo');
    }

    /**
     * @Groups({"foo.bar"})
     */
    public function benchOneNested(): void
    {
        $this->eManager->trigger('foo.bar');
    }

    /**
     * @Groups({"foo.*"})
     */
    public function benchOneWithStarEnd(): void
    {
        $this->eManager->trigger('foo.*');
    }

    /**
     * @Groups({"*.bar"})
     */
    public function benchOneWithStarBegin(): void
    {
        $this->eManager->trigger('*.bar');
    }

    /**
     * @Groups({"*.*"})
     */
    public function benchOneNestedStarAll(): void
    {
        $this->eManager->trigger('*.*');
    }

    /**
     * @Groups({"undefined"})
     */
    public function benchOneUndefined(): void
    {
        $this->eManager->trigger('undefined');
    }
}
