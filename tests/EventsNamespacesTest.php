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
use JBZoo\Event\ExceptionStop;

/**
 * Class EventsNamespacesTest
 * @package JBZoo\PHPUnit
 */
class EventsNamespacesTest extends PHPUnit
{
    /**
     * @var \Closure
     */
    protected $noop;

    protected function setUp()
    {
        $this->noop = function () {
        };
    }

    public function testSimple()
    {
        $eManager = new EventManager();

        $eManager->on('save', $this->noop);

        is(1, $eManager->trigger('save'));
    }

    public function testSimpleParts()
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

        is(0, $eManager->trigger('undefind'));
        is(0, $eManager->trigger('save.after'));
        is(0, $eManager->trigger('item.save.undefind'));
        is(0, $eManager->trigger('item.saved'));
    }

    public function testAnyPart()
    {
        $eManager = new EventManager();

        $eManager->on('item.*', $this->noop);

        is(1, $eManager->trigger('item.save'));
        is(1, $eManager->trigger('item.save.before'));
        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.realy.deep'));
    }

    public function testAnyPart2()
    {
        $eManager = new EventManager();

        $eManager->on('item.*.after', $this->noop);

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));

        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.realy.deep'));
    }

    public function testAnyPart3()
    {
        $eManager = new EventManager();

        $eManager->on('*.save.after', $this->noop);

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));

        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.realy.deep'));
    }

    public function testAnyPart4()
    {
        $eManager = new EventManager();

        $eManager->on('*.save.*', $this->noop);

        is(0, $eManager->trigger('item.save'));

        is(1, $eManager->trigger('item.save.before'));
        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.realy.deep'));
    }

    public function testAnyPart5()
    {
        $eManager = new EventManager();

        $eManager->on('*.*.after', $this->noop);

        is(0, $eManager->trigger('item.save'));
        is(0, $eManager->trigger('item.save.before'));

        is(1, $eManager->trigger('category.init.after'));
        is(1, $eManager->trigger('item.save.after'));
        is(1, $eManager->trigger('item.save.after.realy.deep.name'));
        is(1, $eManager->trigger('item.load.after'));
        is(1, $eManager->trigger('item.load.after.realy.deep.name'));
    }

    public function testComplex()
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

        is(1, $eManager->trigger('item.save'));
        is(1, $eManager->trigger('category'));
        is(6, $eManager->trigger('item.save.before'));
        is(6, $eManager->trigger('item.save.before.realy.deep.name'));
        is(6, $eManager->trigger('category.save.before.realy.deep.name'));
        is(4, $eManager->trigger('item.save.after'));
        is(5, $eManager->trigger('item.save.after.deep'));
    }
}
