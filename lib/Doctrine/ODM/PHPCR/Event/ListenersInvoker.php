<?php

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * A method invoker based on document lifecycle.
 *
 * @since  1.1
 *
 * @author Fabio B. Silva      <fabio.bat.silva@gmail.com>
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ListenersInvoker
{
    public const INVOKE_NONE = 0;

    // TODO: implement INVOKE_LISTENERS

    public const INVOKE_LISTENERS = 1;

    public const INVOKE_CALLBACKS = 2;

    public const INVOKE_MANAGER = 4;

    private EventManager $eventManager;

    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Get the subscribed event systems.
     *
     * @param string $eventName the entity lifecycle event
     *
     * @return int bitmask of subscribed event systems
     */
    public function getSubscribedSystems(ClassMetadata $metadata, string $eventName): int
    {
        $invoke = self::INVOKE_NONE;

        if (array_key_exists($eventName, $metadata->lifecycleCallbacks)) {
            $invoke |= self::INVOKE_CALLBACKS;
        }

        /*
         * Not implemented for phpcr-odm at the moment.
         *
        if (array_key_exists($eventName, $metadata->documentListeners)) {
            $invoke |= self::INVOKE_LISTENERS;
        }
        */

        if ($this->eventManager->hasListeners($eventName)) {
            $invoke |= self::INVOKE_MANAGER;
        }

        return $invoke;
    }

    /**
     * Dispatches the lifecycle event of the given document.
     *
     * @param object $document the document on which the event occurred
     */
    public function invoke(ClassMetadata $metadata, string $eventName, object $document, EventArgs $event, int $invoke): void
    {
        if ($invoke & self::INVOKE_CALLBACKS) {
            foreach ($metadata->lifecycleCallbacks[$eventName] as $callback) {
                $document->$callback($event);
            }
        }

        /*
         * Not implemented for phpcr-odm at the moment.
         *
        if ($invoke & self::INVOKE_LISTENERS) {
            foreach ($metadata->documentListeners[$eventName] as $listener) {
                $class      = $listener['class'];
                $method     = $listener['method'];
                $instance   = $this->resolver->resolve($class);

                $instance->$method($document, $event);
            }
        }
        */

        if ($invoke & self::INVOKE_MANAGER) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }
}
