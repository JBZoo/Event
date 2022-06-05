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

declare(strict_types=1);

namespace JBZoo\PHPUnit;

/**
 * Class EventReadmeTest
 *
 * @package JBZoo\PHPUnit
 */
class EventReadmeTest extends AbstractReadmeTest
{
    protected string $packageName = 'Event';

    protected function setUp(): void
    {
        parent::setUp();

        $this->params['strict_types'] = true;
        $this->params['travis'] = false;
    }
}
