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

namespace JBZoo\Event;

final class EventManager
{
    public const LOWEST  = 0;
    public const LOW     = 50;
    public const MID     = 100; // Default
    public const HIGH    = 500;
    public const HIGHEST = 1000;

    private static ?EventManager $defaultManager = null;

    /** The list of listeners. */
    private array $list = [];

    /**
     * Subscribe to an event.
     * @param string|string[] $eventNames
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function on(array|string $eventNames, callable $callback, int $priority = self::MID): self
    {
        $eventNames = (array)$eventNames;

        foreach ($eventNames as $oneEventName) {
            $oneEventName = self::cleanEventName($oneEventName);

            if (!\array_key_exists($oneEventName, $this->list)) {
                $this->list[$oneEventName] = [];
            }

            $this->list[$oneEventName][] = [$priority, $callback, $oneEventName];
        }

        return $this;
    }

    /**
     * Subscribe to an event only once.
     */
    public function once(string $eventName, callable $callback, int $priority = self::MID): self
    {
        $eventName = self::cleanEventName($eventName);

        $wrapper = null;

        /** @psalm-suppress MissingClosureReturnType */
        $wrapper = function () use ($eventName, $callback, &$wrapper) {
            $this->removeListener($eventName, $wrapper);

            return $callback(...\func_get_args());
        };

        return $this->on($eventName, $wrapper, $priority);
    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were successful
     * handled. false is returned if one of the events broke the event chain.
     *
     * If the continueCallBack is specified, this callback will be called every
     * time before the next event handler is called.
     *
     * If the continueCallback returns false, event propagation stops. This
     * allows you to use the eventEmitter as a means for listeners to implement
     * functionality in your application, and break the event loop as soon as
     * some condition is fulfilled.
     *
     * Note that returning false from an event subscriber breaks propagation
     * and returns false, but if the continue-callback stops propagation, this
     * is still considered a 'successful' operation and returns true.
     *
     * Lastly, if there are 5 event handlers for an event. The continueCallback
     * will be called at most 4 times.
     */
    public function trigger(string $eventName, array $arguments = [], ?callable $continueCallback = null): int
    {
        $listeners   = $this->getList($eventName);
        $arguments[] = self::cleanEventName($eventName);

        return self::callListenersWithCallback($listeners, $arguments, $continueCallback);
    }

    /**
     * Returns the list of listeners for an event.
     * The list is returned as an array, and the list of events are sorted by their priority.
     * @return callable[]
     */
    public function getList(string $eventName): array
    {
        $eventName = self::cleanEventName($eventName);

        $result = [];
        $ePaths = \explode('.', $eventName);

        foreach ($this->list as $eName => $eData) {
            $eName = (string)$eName;
            if ($eName === $eventName) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = \array_merge($result, $eData);
            } elseif (\str_contains($eName, '*')) {
                $eNameParts = \explode('.', $eName);
                if (self::isContainPart($eNameParts, $ePaths)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $result = \array_merge($result, $eData);
                }
            }
        }

        if (\count($result) > 0) {
            /**
             * @param  array $item1
             * @param  array $item2
             * @return int
             */
            $sortFunc = static fn (array $item1, array $item2): int => (int)$item2[0] - (int)$item1[0];
            \usort($result, $sortFunc); // Sorting by priority

            /**
             * @param  array    $item
             * @return callable
             */
            $mapFunc = static fn (array $item): callable => $item[1];

            return \array_map($mapFunc, $result);
        }

        return [];
    }

    /**
     * Removes a specific listener from an event.
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     */
    public function removeListener(string $eventName, ?callable $listener = null): bool
    {
        $eventName = self::cleanEventName($eventName);

        if (!\array_key_exists($eventName, $this->list)) {
            return false;
        }

        foreach ($this->list[$eventName] as $index => $eventData) {
            if ($eventData[1] === $listener) {
                unset($this->list[$eventName][$index]);

                return true;
            }
        }

        return false;
    }

    /**
     * Removes all listeners.
     * If the eventName argument is specified, all listeners for that event are
     * removed. If it is not specified, every listener for every event is removed.
     */
    public function removeListeners(?string $eventName = null): void
    {
        if ($eventName !== null) {
            $eventName = self::cleanEventName($eventName);
        }

        if ($eventName !== null && $eventName !== '') {
            unset($this->list[$eventName]);
        } else {
            $this->list = [];
        }
    }

    public function getSummeryInfo(): array
    {
        $result = [];

        foreach ($this->list as $eventName => $callbacks) {
            $result[$eventName] = \count($callbacks);
        }

        \ksort($result);

        return $result;
    }

    /**
     * Prepare event name before using.
     */
    public static function cleanEventName(string $eventName): string
    {
        $eventName = \strtolower($eventName);
        $eventName = \str_replace('..', '.', $eventName);
        $eventName = \trim($eventName, '.');
        $eventName = \trim($eventName);

        if ($eventName === '') {
            throw new Exception('Event name is empty!');
        }

        return $eventName;
    }

    public static function setDefault(self $eManager): void
    {
        self::$defaultManager = $eManager;
    }

    public static function getDefault(): ?self
    {
        return self::$defaultManager;
    }

    /**
     * Call list of listeners with continue callback function.
     * @param callable[] $listeners
     */
    private static function callListenersWithCallback(
        array $listeners,
        array $arguments = [],
        ?callable $continueCallback = null,
    ): int {
        $counter     = \count($listeners);
        $execCounter = 0;

        foreach ($listeners as $listener) {
            $counter--;

            $result = self::callOneListener($listener, $arguments);
            if (!$result) {
                return $execCounter;
            }

            $execCounter++;

            if ($continueCallback !== null && $counter > 0 && !$continueCallback()) {
                break;
            }
        }

        return $execCounter;
    }

    /**
     * Call list of listeners.
     */
    private static function callOneListener(callable $listener, array $arguments = []): bool
    {
        try {
            $listener(...$arguments);
        } catch (ExceptionStop) {
            return false;
        }

        return true;
    }

    /**
     * Check is one part name contain another one.
     */
    private static function isContainPart(array $eNameParts, array $ePaths): bool
    {
        // Length of parts is equals
        if (\count($eNameParts) !== \count($ePaths)) {
            return false;
        }

        $isFound = true;

        foreach ($eNameParts as $pos => $eNamePart) {
            if ($eNamePart !== '*' && \array_key_exists($pos, $ePaths) && $ePaths[$pos] !== $eNamePart) {
                $isFound = false;
                break;
            }
        }

        return $isFound;
    }
}
