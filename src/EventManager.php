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
    protected $_listeners = [];

    /**
     * Subscribe to an event.
     *
     * @param string   $eventName
     * @param callable $callBack
     * @param int      $priority
     * @return $this
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function on($eventName, callable $callBack, $priority = self::MID)
    {
        $eventName = $this->_cleanEventName($eventName);
        if (!$eventName) {
            throw new Exception('Event name is empty!');
        }

        if (!isset($this->_listeners[$eventName])) {
            $this->_listeners[$eventName] = [
                true,  // If there's only one item, it's sorted
                [$priority],
                [$callBack],
            ];

        } else {
            $this->_listeners[$eventName][0]   = false; // marked as unsorted
            $this->_listeners[$eventName][1][] = $priority;
            $this->_listeners[$eventName][2][] = $callBack;
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
     */
    public function once($eventName, callable $callBack, $priority = 100)
    {
        $eventName = $this->_cleanEventName($eventName);

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
     * @return bool
     * @throws Exception
     */
    public function trigger($eventName, array $arguments = [], callable $continueCallback = null)
    {
        $eventName = $this->_cleanEventName($eventName);
        if (!$eventName) {
            throw new Exception('Event name is empty!');
        }

        if (is_null($continueCallback)) {
            foreach ($this->listeners($eventName) as $listener) {
                try {
                    call_user_func_array($listener, $arguments);
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }

        } else {
            $listeners = $this->listeners($eventName);
            $counter   = count($listeners);

            foreach ($listeners as $listener) {
                $counter--;

                try {
                    call_user_func_array($listener, $arguments);
                } catch (Exception $e) {
                    return $e->getMessage();
                }

                if ($counter > 0 && false === $continueCallback()) {
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @param string $eventName
     * @return callable[]
     */
    public function listeners($eventName)
    {
        if (!isset($this->_listeners[$eventName])) {
            return [];
        }

        // The list is not sorted
        if (!$this->_listeners[$eventName][0]) {
            // Sorting
            array_multisort($this->_listeners[$eventName][1], SORT_NUMERIC, $this->_listeners[$eventName][2]);

            // Marking the listeners as sorted
            $this->_listeners[$eventName][0] = true;
        }

        return $this->_listeners[$eventName][2];

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
     */
    public function removeListener($eventName, callable $listener)
    {
        if (!isset($this->_listeners[$eventName])) {
            return false;
        }

        foreach ($this->_listeners[$eventName][2] as $index => $check) {

            if ($check === $listener) {
                unset($this->_listeners[$eventName][1][$index]);
                unset($this->_listeners[$eventName][2][$index]);

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
    public function removeAllListeners($eventName = null)
    {
        if (!is_null($eventName)) {
            unset($this->_listeners[$eventName]);
        } else {
            $this->_listeners = [];
        }
    }

    /**
     * @param $eventName
     * @return string
     */
    protected function _cleanEventName($eventName)
    {
        $eventName = strtolower($eventName);
        $eventName = trim($eventName);
        return $eventName;
    }

}
