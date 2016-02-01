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

namespace JBZoo\Event;

/**
 * Class Event
 * @package JBZoo\Event
 */
class EventManager
{
    const LOWEST  = 0;
    const LOW     = 50;
    const MID     = 100; // Default
    const HIGH    = 500;
    const HIGHEST = 1000;

    /**
     * The list of listeners
     * @var array
     */
    protected $_list = [];

    /**
     * Subscribe to an event.
     *
     * @param string   $eventName
     * @param callable $callback
     * @param int      $priority
     * @return $this
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function on($eventName, callable $callback, $priority = self::MID)
    {
        $eventName = $this->cleanEventName($eventName);

        if (!isset($this->_list[$eventName])) {
            $this->_list[$eventName] = [];
        }

        $this->_list[$eventName][] = [$priority, $callback, $eventName];

        return $this;
    }

    /**
     * Subscribe to an event exactly once.
     *
     * @param string   $eventName
     * @param callable $callBack
     * @param int      $priority
     * @return $this
     */
    public function once($eventName, callable $callBack, $priority = 100)
    {
        $eventName = $this->cleanEventName($eventName);

        $wrapper = null;
        $wrapper = function () use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            return call_user_func_array($callBack, func_get_args());

        };

        return $this->on($eventName, $wrapper, $priority);
    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were succesfully
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
     * @param string   $eventName
     * @param array    $arguments
     * @param callback $continueCallback
     * @return int|string
     * @throws Exception
     */
    public function trigger($eventName, array $arguments = [], callable $continueCallback = null)
    {
        $eventName = $this->cleanEventName($eventName);

        if (strpos($eventName, '*') !== false) {
            throw new ExceptionStop('Event contains "*"');
        }

        $execCount = 0;
        $listeners = $this->listeners($eventName);

        if (is_null($continueCallback)) {

            foreach ($listeners as $listener) {
                try {
                    call_user_func_array($listener, $arguments);
                    $execCount++;
                } catch (ExceptionStop $e) {
                    return $e->getMessage();
                }
            }

        } else {
            $counter = count($listeners);

            foreach ($listeners as $listener) {
                $counter--;

                try {
                    call_user_func_array($listener, $arguments);
                    $execCount++;
                } catch (ExceptionStop $e) {
                    return $e->getMessage();
                }

                if ($counter > 0 && false === $continueCallback()) {
                    break;
                }
            }
        }

        return $execCount;
    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @param string $eventName
     * @return callable[]
     * @throws Exception
     */
    public function listeners($eventName)
    {
        $eventName = $this->cleanEventName($eventName);

        if ($eventName === '*') {
            throw new Exception('Unsafe event name!');
        }

        $result = [];
        $ePaths = explode('.', $eventName);

        foreach ($this->_list as $eName => $eData) {

            if ($eName === $eventName) {
                $result = array_merge($result, $eData);

            } elseif (strpos($eName, '*') !== false) {
                $eNameParts = explode('.', $eName);

                if ($this->_isContainPart($eNameParts, $ePaths)) {
                    $result = array_merge($result, $eData);
                }
            }
        }

        if (count($result) > 0) {
            // Sorting
            usort($result, function ($item1, $item2) {
                return $item2[0] - $item1[0];
            });

            return array_map(function ($item) {
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
    protected function _isContainPart($eNameParts, $ePaths)
    {
        if (count($eNameParts) === count($ePaths)) {
            $isFound = true;

            foreach ($eNameParts as $pos => $eNamePart) {
                if ((isset($ePaths[$pos]) && $ePaths[$pos] !== $eNamePart) && '*' !== $eNamePart) {
                    $isFound = false;
                    break;
                }
            }

            return $isFound;
        }
    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     *
     * @param string        $eventName
     * @param callable|null $listener
     * @return bool
     *
     * @throws Exception
     */
    public function removeListener($eventName, callable $listener = null)
    {
        $eventName = $this->cleanEventName($eventName);

        if (!isset($this->_list[$eventName])) {
            return false;
        }

        foreach ($this->_list[$eventName] as $index => $eventData) {

            if ($eventData[1] === $listener) {
                unset($this->_list[$eventName][$index]);
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
     */
    public function removeListeners($eventName = null)
    {
        if (null !== $eventName) {
            $eventName = $this->cleanEventName($eventName);
        }

        if (isset($this->_list[$eventName])) {
            unset($this->_list[$eventName]);
        } else {
            $this->_list = [];
        }
    }

    /**
     * Prepare event namebefore using
     *
     * @param string $eventName
     * @return string
     * @throws Exception
     */
    public function cleanEventName($eventName)
    {
        $eventName = strtolower($eventName);
        $eventName = preg_replace('#[^[:alnum:]\*\.]#', '', $eventName);
        $eventName = preg_replace('#\.{2,}#', '.', $eventName);
        $eventName = trim($eventName, '.');

        if (!$eventName) {
            throw new Exception('Event name is empty!');
        }

        return $eventName;
    }
}
