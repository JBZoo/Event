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
    protected $_list = array();

    /**
     * Subscribe to an event.
     *
     * @param string|array $eventNames
     * @param callable     $callback
     * @param int          $priority
     * @return $this
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function on($eventNames, $callback, $priority = self::MID)
    {
        $eventNames = (array)$eventNames;

        foreach ($eventNames as $oneEventName) {
            $oneEventName = $this->cleanEventName($oneEventName);

            if (!array_key_exists($oneEventName, $this->_list)) {
                $this->_list[$oneEventName] = array();
            }

            $this->_list[$oneEventName][] = array((int)$priority, $callback, $oneEventName);
        }

        return $this;
    }

    /**
     * Subscribe to an event exactly once.
     *
     * @param string   $eventName
     * @param callable $callBack
     * @param int      $priority
     * @return $this
     * @throws Exception
     */
    public function once($eventName, $callBack, $priority = 100)
    {
        $eventName = $this->cleanEventName($eventName);
        $eManager  = $this;

        $wrapper = null;
        $wrapper = function () use ($eventName, $callBack, &$wrapper, $eManager) {

            $eManager->removeListener($eventName, $wrapper);
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
    public function trigger($eventName, array $arguments = array(), $continueCallback = null)
    {
        $eventName = $this->cleanEventName($eventName);

        if (strpos($eventName, '*') !== false) {
            throw new Exception('Event name "' . $eventName . '" shouldn\'t contain symbol "*"');
        }

        $listeners = $this->getList($eventName, false);

        if (null === $continueCallback) {
            $execCount = $this->_callListeners($listeners, $arguments);
        } else {
            $execCount = $this->_callListenersWithCallback($listeners, $arguments, $continueCallback);
        }

        return $execCount;
    }

    /**
     * Call list of listeners
     *
     * @param array $listeners
     * @param array $arguments
     * @return int|string
     */
    protected function _callListeners($listeners, array $arguments)
    {
        $execCount = 0;

        foreach ($listeners as $listener) {
            if ($result = $this->_callListener($listener, $arguments)) {
                return $result;
            }
            $execCount++;
        }

        return $execCount;
    }

    /**
     * Call list of listeners with continue callback function
     *
     * @param array    $listeners
     * @param array    $arguments
     * @param callable $continueCallback
     * @return int|string
     */
    protected function _callListenersWithCallback($listeners, array $arguments, $continueCallback)
    {
        $counter   = count($listeners);
        $execCount = 0;

        foreach ($listeners as $listener) {
            $counter--;

            if ($result = $this->_callListener($listener, $arguments)) {
                return $result;
            }
            $execCount++;

            if ($counter > 0 && false === $continueCallback()) {
                break;
            }
        }

        return $execCount;
    }

    /**
     * Call list of listeners
     *
     * @param mixed $listener
     * @param array $arguments
     * @return int|string
     */
    protected function _callListener($listener, array $arguments)
    {
        try {
            call_user_func_array($listener, $arguments);
        } catch (ExceptionStop $e) {
            return $e->getMessage();
        }

        return false;
    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @param string $eventName
     * @param bool   $cleanup
     * @return callable[]
     * @throws Exception
     */
    public function getList($eventName, $cleanup = true)
    {
        if ($cleanup) {
            $eventName = $this->cleanEventName($eventName);
        }

        if ($eventName === '*') {
            throw new Exception('Unsafe event name!');
        }

        $result = array();
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

        return array();
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
        // Length of parts is equels
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
     * @param string        $eventName
     * @param callable|null $listener
     * @return bool
     *
     * @throws Exception
     */
    public function removeListener($eventName, $listener = null)
    {
        $eventName = $this->cleanEventName($eventName);

        if (!array_key_exists($eventName, $this->_list)) {
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
     * @throws Exception
     */
    public function removeListeners($eventName = null)
    {
        if (null !== $eventName) {
            $eventName = $this->cleanEventName($eventName);
        }

        if (array_key_exists($eventName, $this->_list)) {
            unset($this->_list[$eventName]);
        } else {
            $this->_list = array();
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
        $eventName = str_replace('..', '.', $eventName);
        $eventName = trim($eventName, '.');
        $eventName = trim($eventName);

        if (!$eventName) {
            throw new Exception('Event name is empty!');
        }

        return $eventName;
    }
}
