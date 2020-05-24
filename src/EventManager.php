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

namespace JBZoo\Event;

use Closure;

/**
 * Class Event
 * @package JBZoo\Event
 */
class EventManager
{
    public const LOWEST  = 0;
    public const LOW     = 50;
    public const MID     = 100; // Default
    public const HIGH    = 500;
    public const HIGHEST = 1000;

    /**
     * @var EventManager|null
     */
    protected static $defaultManager;

    /**
     * The list of listeners
     * @var array
     */
    protected $list = [];

    /**
     * Subscribe to an event.
     *
     * @param string|string[] $eventNames
     * @param Closure         $callback
     * @param int             $priority
     * @return $this
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function on($eventNames, $callback, int $priority = self::MID)
    {
        $eventNames = (array)$eventNames;

        foreach ($eventNames as $oneEventName) {
            $oneEventName = $this->cleanEventName($oneEventName);

            if (!array_key_exists($oneEventName, $this->list)) {
                $this->list[$oneEventName] = [];
            }

            $this->list[$oneEventName][] = [$priority, $callback, $oneEventName];
        }

        return $this;
    }

    /**
     * Subscribe to an event only once.
     *
     * @param string  $eventName
     * @param Closure $callback
     * @param int     $priority
     * @return $this
     * @throws Exception
     */
    public function once($eventName, Closure $callback, int $priority = self::MID)
    {
        $eventName = $this->cleanEventName($eventName);

        $wrapper = null;

        /** @psalm-suppress MissingClosureReturnType */
        $wrapper = function () use ($eventName, $callback, &$wrapper) {
            $this->removeListener($eventName, $wrapper);
            return call_user_func_array($callback, func_get_args());
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
     *
     * @param string  $eventName
     * @param mixed[] $arguments
     * @param Closure $continueCallback
     * @return int|string
     * @throws Exception
     */
    public function trigger($eventName, array $arguments = [], $continueCallback = null)
    {
        $listeners = $this->getList($eventName);
        $arguments[] = $this->cleanEventName($eventName);

        return $this->callListenersWithCallback($listeners, $arguments, $continueCallback);
    }

    /**
     * Call list of listeners with continue callback function
     *
     * @param Closure[]    $listeners
     * @param mixed[]      $arguments
     * @param Closure|null $continueCallback
     * @return int|string
     */
    protected function callListenersWithCallback(array $listeners, array $arguments = [], $continueCallback = null)
    {
        $counter = count($listeners);
        $execCounter = 0;

        foreach ($listeners as $listener) {
            $counter--;

            $result = $this->callOneListener($listener, $arguments);
            if (null !== $result) {
                return $result;
            }

            $execCounter++;

            if (null !== $continueCallback && $counter > 0 && !$continueCallback()) {
                break;
            }
        }

        return $execCounter;
    }

    /**
     * Call list of listeners
     *
     * @param Closure $listener
     * @param array   $arguments
     * @return string|null
     */
    protected function callOneListener($listener, array $arguments = []): ?string
    {
        try {
            call_user_func_array($listener, $arguments);
        } catch (ExceptionStop $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @param string $eventName
     * @return Closure[]
     * @throws Exception
     */
    public function getList($eventName): array
    {
        $eventName = $this->cleanEventName($eventName);

        $result = [];
        $ePaths = explode('.', $eventName);

        foreach ($this->list as $eName => $eData) {
            if ($eName === $eventName) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = array_merge($result, $eData);
            } elseif (strpos($eName, '*') !== false) {
                $eNameParts = explode('.', $eName);
                if ($this->isContainPart($eNameParts, $ePaths)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $result = array_merge($result, $eData);
                }
            }
        }

        if (count($result) > 0) {
            // Sorting by priority
            usort($result, /** @psalm-suppress MissingClosureReturnType */ function (array $item1, array $item2) {
                return $item2[0] - $item1[0];
            });

            return array_map(/** @psalm-suppress MissingClosureReturnType */ function (array $item) {
                return $item[1];
            }, $result);
        }

        return [];
    }

    /**
     * Check is one part name contain another one
     *
     * @param array $eNameParts
     * @param array $ePaths
     * @return bool
     */
    protected function isContainPart($eNameParts, $ePaths)
    {
        // Length of parts is equals
        if (count($eNameParts) !== count($ePaths)) {
            return false;
        }

        $isFound = true;

        foreach ($eNameParts as $pos => $eNamePart) {
            if ('*' !== $eNamePart && array_key_exists($pos, $ePaths) && $ePaths[$pos] !== $eNamePart) {
                $isFound = false;
                break;
            }
        }

        return $isFound;
    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     *
     * @param string       $eventName
     * @param Closure|null $listener
     * @return bool
     *
     * @throws Exception
     */
    public function removeListener($eventName, $listener = null)
    {
        $eventName = $this->cleanEventName($eventName);

        if (!array_key_exists($eventName, $this->list)) {
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
     *
     * If the eventName argument is specified, all listeners for that event are
     * removed. If it is not specified, every listener for every event is
     * removed.
     *
     * @param string $eventName
     * @return void
     * @throws Exception
     */
    public function removeListeners($eventName = null)
    {
        if (null !== $eventName) {
            $eventName = $this->cleanEventName($eventName);
        }

        if ($eventName) {
            unset($this->list[$eventName]);
        } else {
            $this->list = [];
        }
    }

    /**
     * Prepare event name before using
     *
     * @param string $eventName
     * @return string
     * @throws Exception
     */
    public function cleanEventName($eventName)
    {
        $eventName = strtolower($eventName);
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $eventName = str_replace('..', '.', $eventName);
        $eventName = trim($eventName, '.');
        $eventName = trim($eventName);

        if (!$eventName) {
            throw new Exception('Event name is empty!');
        }

        return $eventName;
    }

    /**
     * @param EventManager $eManager
     */
    public static function setDefault(EventManager $eManager): void
    {
        self::$defaultManager = $eManager;
    }

    /**
     * @return EventManager|null
     */
    public static function getDefault()
    {
        return self::$defaultManager;
    }

    /**
     * @return array
     */
    public function getSummeryInfo()
    {
        $result = [];
        foreach ($this->list as $eventName => $callbacks) {
            $result[$eventName] = count($callbacks);
        }

        ksort($result);

        return $result;
    }
}
