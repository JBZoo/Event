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

class EventNamespacesTest extends PHPUnit
{
    /** @var \Closure */
    protected $noop;

    protected function setUp(): void
    {
        $this->noop = static function (): void {
        };
    }

    public function testSimple(): void
    {
        $eManager = new EventManager();

        $eManager->on('save', $this->noop);

        is(1, $eManager->trigger('save'));
    }

    public function testSimpleParts(): void
    {
        $eManager = new EventManager();

        $eManager->on('item', $this->noop);
        $eManager->on('item.save', $this->noop);
        $eManager->on('item.save.before', $this->noop);
        $eManager->on('item.save.after', $this->noop);

        is(1, $eManager->trigger('item'));
        is(1, $eManager->trigger('item.save'));
        is(1, $eManager->trigger('item.save.before'));
        is(1, $eManager->trigger('item.save.after'));

        is(0, $eManager->trigger('undefined'));
        is(0, $eManager->trigger('save.after'));
        is(0, $eManager->trigger('item.save.undefined'));
        is(0, $eManager->trigger('item.saved'));
    }

    public function testAnyPart(): void
    {
        $eManager = new EventManager();

        $eManager->on('item.*', $this->noop);

        is(1, $eManager->trigger('item.save'));

        is(0, $eManager->trigger('item.save.before'));
        is(0, $eManager->trigger('item.save.after'));
        is(0, $eManager->trigger('item.save.after.really.deep'));
    }

    public function testAnyPart2(): void
    {
        $eManager = new EventManager();

        $eManager->on('item.*.after', $this->noop);

        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.init.after'));

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));
        is(0, $eManager->trigger('item.save.after.really.deep'));
    }

    public function testAnyPart3(): void
    {
        $eManager = new EventManager();

        $eManager->on('*.save.after', $this->noop);

        is(1, $eManager->trigger('item.save.after'));

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));
        is(0, $eManager->trigger('item.save.after.really.deep'));
    }

    public function testAnyPart4(): void
    {
        $eManager = new EventManager();

        $eManager->on('*.save.*', $this->noop);

        is(1, $eManager->trigger('item.save.before'));
        is(1, $eManager->trigger('item.save.after'));

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.after.really.deep'));
    }

    public function testAnyPart5(): void
    {
        $eManager = new EventManager();

        $eManager->on('*.*.after', $this->noop);

        is(1, $eManager->trigger('category.init.after'));
        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.load.after'));

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));
        is(0, $eManager->trigger('item.save.after.really.deep.name'));
        is(0, $eManager->trigger('item.load.after.really.deep.name'));
    }

    public function testComplex(): void
    {
        $eManager = new EventManager();

        $eManager->on('item.*', static function (): void {
        });
        $eManager->on('*.init', static function (): void {
        });
        $eManager->on('*.save', static function (): void {
        });
        $eManager->on('*.save.after', static function (): void {
        });
        $eManager->on(['tag.*.*', 'item.*.*'], static function (): void {
        });

        is(1, $eManager->trigger('tag.init'));
        is(1, $eManager->trigger('tag.save.before'));
        is(1, $eManager->trigger('tag.save'));
        is(2, $eManager->trigger('tag.save.after'));

        is(2, $eManager->trigger('item.init'));
        is(1, $eManager->trigger('item.save.before'));
        is(2, $eManager->trigger('item.save'));
        is(2, $eManager->trigger('item.save.after'));
    }

    public function testComplex2(): void
    {
        $eManager = new EventManager();

        $eManager->on('*.save', $this->noop);
        $eManager->on('*.save.*', $this->noop);
        $eManager->on('*.save.*', $this->noop);
        $eManager->on('*.save.*', $this->noop);

        $eManager->on('*.save.*.*', $this->noop);
        $eManager->on('*.save.before', $this->noop);

        $eManager->on('item.save.before', $this->noop);
        $eManager->on('category.save.before', $this->noop);
        $eManager->on('category.load.before', $this->noop);
        $eManager->on('category', $this->noop);

        is(0, $eManager->trigger('item.load'));
        is(0, $eManager->trigger('item.load.before'));
        is(0, $eManager->trigger('save.before'));
        is(0, $eManager->trigger('item.save.before.really.deep.name'));
        is(0, $eManager->trigger('category.save.before.really.deep.name'));

        is(1, $eManager->trigger('item.save'));
        is(1, $eManager->trigger('category'));
        is(1, $eManager->trigger('item.save.after.deep'));
        is(3, $eManager->trigger('item.save.after'));
        is(5, $eManager->trigger('item.save.before'));
    }
}
