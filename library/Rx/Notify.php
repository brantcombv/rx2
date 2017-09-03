<?php

class Rx_Notify
{
    /**#@+
     * Supported types of subscriptions
     */
    const TYPE_EVENT = 'event';
    const TYPE_OBJECT = 'object';
    /**#@-*/

    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Notify $_instance
     */
    protected static $_instance = null;
    /**
     * Notification subscriptions
     *
     * @var array $_subscriptions
     */
    protected $_subscriptions = array();

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Notify
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Subscribe given object to one or multiple events
     *
     * @param Rx_Notify_Observer $obj       Object instance to subscribe
     * @param string|object|array $events   One of following items to subscribe to:
     *                                      - Event Id
     *                                      - Object instance
     *                                      - Class name
     *                                      - Array of any items listed above
     * @param int|boolean $priority         OPTIONAL Event handling priority for this object.
     *                                      Possible values are:
     *                                      - true - object must handle event as soon as possible, before all other objects
     *                                      - false - object must handle event as late as possible, after all other objects
     *                                      - 0..100 - object's handling priority (less = earlier)
     *                                      - null - object's handling priority is irrelevant (default)
     * @throws Rx_Notify_Exception
     * @return void
     */
    public static function subscribe($obj, $events, $priority = null)
    {
        if (!in_array('Rx_Notify_Observer', class_implements($obj))) {
            throw new Rx_Notify_Exception('Object must implement Rx_Notify_Observer interface to be able to subscribe to notifications');
        }
        $instance = self::getInstance();
        $events = $instance->_parseEvents($events);
        foreach ($events as $event) {
            if (!array_key_exists($event, $instance->_subscriptions)) {
                $instance->_subscriptions[$event] = array(
                    'before' => array(),
                    'normal' => array(),
                    'after'  => array(),
                );
            }
            if ($priority === true) {
                $instance->_subscriptions[$event]['before'][] = $obj;
            } elseif ($priority === false) {
                $instance->_subscriptions[$event]['after'][] = $obj;
            } elseif ($priority === null) {
                $instance->_subscriptions[$event]['normal'][] = $obj;
            } else {
                $p = $priority;
                while (array_key_exists($p, $instance->_subscriptions[$event]['normal'])) {
                    $p++;
                }
                $instance->_subscriptions[$event]['normal'][$p] = $obj;
            }
        }
    }

    /**
     * Unsubscribe given object from given list of events
     *
     * @param object $obj       Object instance to unsubscribe
     * @param mixed $events     One of following items:
     *                          - Event Id
     *                          - Object instance
     *                          - Class name
     *                          - Array of any items listed above
     *                          - true to unsubscribe from all events (default)
     * @return void
     */
    public static function unsubscribe($obj, $events = true)
    {
        $instance = self::getInstance();
        if ($events !== true) {
            $events = $instance->_parseEvents($events);
        }
        foreach ($instance->_subscriptions as $eventId => $subscriptions) {
            if (($events !== true) && (!in_array($eventId, $events))) {
                continue;
            }
            foreach ($subscriptions as $phase => $objects) {
                foreach ($objects as $p => $object) {
                    if ($obj === $object) {
                        unset($instance->_subscriptions[$eventId][$phase][$p]);
                    }
                }
            }
        }
    }

    /**
     * Notify subscribed objects about some event
     *
     * @param string|Rx_Notify_Event $type Either notification event object or notification event type
     * @param array $data                  OPTIONAL Additional event data (used only if no ready object is passed as first argument)
     * @param object $sender               OPTIONAL Instance of object that creates event (used only if no ready object is passed as first argument)
     * @throws Rx_Notify_Exception
     * @return void
     */
    public static function notify($type, $data = null, $sender = null)
    {
        if (is_object($type)) {
            if ($type instanceof Rx_Notify_Event) {
                $event = $type;
            } else {
                throw new Rx_Notify_Exception('Notification event object must be inherited from Rx_Notify_Event');
            }
        } else {
            $event = new Rx_Notify_Event($type, $data, $sender);
        }
        $instance = self::getInstance();
        // Prepare list of notifications to look for
        $notifications = $instance->_parseEvents(array(
            $event->getType(), // We will look for subscribers on this notification type
            $event->getSender(), // and for subscribers on object that was fired this notification
        ));
        // Collect information about objects that are subscribed to this notification
        $subscribers = array(
            'before' => array(),
            'normal' => array(),
            'after'  => array(),
        );
        foreach ($notifications as $notification) {
            if (!array_key_exists($notification, $instance->_subscriptions)) {
                continue;
            }
            foreach ($subscribers as $phase => $objects) {
                foreach ($instance->_subscriptions[$notification][$phase] as $subscriber) {
                    if (!in_array($subscriber, $subscribers[$phase], true)) {
                        $subscribers[$phase][] = $subscriber;
                    }
                }
            }
        }
        foreach ($subscribers as $phase => $objects) {
            /** @var $object Rx_Notify_Observer */
            foreach ($objects as $object) {
                // Event object should be cloned before passing to notification handler
                // to avoid unnecessary distribution of changes into event object
                // among notification subscribers
                $ev = clone($event);
                $object->handleNotify($ev);
            }
        }
    }

    /**
     * Parse given list of events
     *
     * @param string|object|array $events   One of following items:
     *                                      - Event Id
     *                                      - Object instance
     *                                      - Class name
     *                                      - Array of any items listed above
     * @return array
     */
    protected function _parseEvents($events)
    {
        if (!is_array($events)) {
            $events = array($events);
        }
        $_events = array();
        foreach ($events as $event) {
            if (is_object($event)) {
                $reflection = new ReflectionObject($event);
                do {
                    $_events[] = self::TYPE_OBJECT . '|' . $reflection->getName();
                    $reflection = $reflection->getParentClass();
                } while (is_object($reflection));
            } elseif (is_string($event)) {
                if (class_exists($event, true)) {
                    $reflection = new ReflectionClass($event);
                    do {
                        $_events[] = self::TYPE_OBJECT . '|' . $reflection->getName();
                        $reflection = $reflection->getParentClass();
                    } while (is_object($reflection));
                } else {
                    $_events[] = self::TYPE_EVENT . '|' . $event;
                }
            }
        }
        return ($_events);
    }

}
