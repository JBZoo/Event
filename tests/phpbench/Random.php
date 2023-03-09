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
class Random
{
    /** @var EventManager */
    private $eManager;

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function init(): void
    {
        $this->eManager = new EventManager();
        $parts          = ['foo', 'bar', 'woo', 'bazz', '*', '*', '*'];

        for ($i = 0; $i < 10; $i++) {
            \shuffle($parts);
            $partsRand = \implode('.', \array_slice($parts, 0, \random_int(1, \count($parts))));

            if ($partsRand === '*') {
                $partsRand .= '.foo';
            }

            $this->eManager->on($partsRand, static function (): void {
                // noop
            }, \random_int(0, $i));
        }
    }

    /**
     * @Groups({"random.*.triggers"})
     */
    public function benchOneSimple(): void
    {
        $parts = ['foo', 'bar', 'woo', 'bazz'];
        \shuffle($parts);
        $partsRand = \implode('.', \array_slice($parts, 0, \random_int(1, \count($parts))));

        $this->eManager->trigger($partsRand);
    }
}
