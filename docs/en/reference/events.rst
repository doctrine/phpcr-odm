Events
======

Doctrine PHPCR-ODM features a lightweight event system that is part of the
Common package.

For a general introduction, see the corresponding chapter in the `Doctrine ORM documentation <http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html>`_


Lifecycle Events
----------------

The DocumentManager and PHPCR-ODM UnitOfWork trigger a bunch of events during
the life-time of their registered documents.

- prePersist - occurs before a new document is created in the repository;
- postPersist - occurs after a document has been created in repository.
  Generated fields will be available in this state;
- preUpdate - occurs before an existing document is updated in the repository,
  during the flush operation;
- postUpdate - occurs after an existing document has successfully been updated
  in the repository;
- postLoad - occurs after the document has been loaded from the repository;
- preMove - occurs before a document move operation is persisted to the PHPCR
  session during flush;
- postMove - occurs after a document move operation has been persisted to the
  PHPCR session during flush;
- preRemove - occurs before a document is removed from the repository;
- postRemove - occurs after the document has been successfully removed from the
  repository;
- preFlush - occurs at the very beginning of a flush operation. This event is
  not a lifecycle callback;
- onFlush - occurs after the change-sets of all managed documents have been
  computed. This event is not a lifecycle callback;
- postFlush - occurs after the flush has been committed, but before the UOW
  state has been reset. This event is not a lifecycle callback;
- endFlush - occurs after the flush has been comitted *and* the UOW has been
  reset. It is safe to call `flush()` again from this event. This event is not
  a lifecycle callback.
- onClear - occurs when the DocumentManager#clear() operation is invoked, after
  all references to documents have been removed from the unit of work. This
  event is not a lifecycle callback;
- loadClassMetadata - occurs after mapping metadata for a class has been loaded
  from a mapping source (annotations/xml/yaml). This event is not a lifecycle
  callback.
- postLoadTranslation - occurs when a translation of a document has been loaded
  from the repository.
- preBindTranslation - occurs before binding a translation, but after persist
  (you need to persist before binding a translation)
- postBindTranslation - occurs after a translation has been created in the
  repository.
- preRemoveTranslation - occurs before a document's translation is removed
  from repository.
- postRemoveTranslation - occurs after a document's translation was successfully
  removed.

.. note::

    If you use PHPCR-ODM inside Symfony2, you can use the tag
    doctrine_phpcr.event_listener to register a service as event listener.
    See the `Documentation of DoctrinePHPCRBundle <http://github.com/doctrine/DoctrinePHPCRBundle>`_
    for more information.

.. warning::

    Note that the postLoad event occurs for a document
    before any associations have been initialized. Therefore it is not
    safe to access associations in a postLoad callback or event
    handler.


You can access the Event constants from the ``Event`` class in the
PHPCR-ODM package::

    use Doctrine\ODM\PHPCR\Event;

    echo Event::preUpdate;

Event order when moving
-----------------------

During the flush() operation of a modified document, the events get triggered in the following order:

* 1. preFlush
* 2. onFlush
* 3. preUpdate
* 4. postUpdate
* 5. preMove
* 6. postMove
* 7. postFlush


As the move event is triggered after the changeset has been calculated,
modifications to the document are not taken into account anymore.

Event order when handling with translations
-------------------------------------------

When binding/removing a translation or load it from repository, the events get
triggered in the following order:

* 1. prePersist (you need to persist before calling ``bindTranlation()``)
* 2. preBindTranslation
* 3. postBindTranslation

Load a document by its translation

* 4. postLoadTranslation

Remove a translation

* 5. preRemoveTranslation
* 6. postRemoveTranslation


Listening to events
-------------------

These can be hooked into by two different types of event
listeners:


-  Lifecycle Callbacks are methods on the document classes that are
   called when the event is triggered. They receive absolutely no
   arguments and are specifically designed to allow changes inside the
   document classes state.
-  Lifecycle Event Listeners are classes with specific callback
   methods that receives some kind of ``EventArgs`` instance which
   give access to the entity, EntityManager or other relevant data.

.. _events_lifecyclecallbacks:

Lifecycle Callbacks
-------------------

A lifecycle event is a regular event with the additional feature of
providing a mechanism to register direct callbacks inside the
corresponding document classes that are executed when the lifecycle
event occurs.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\PrePersist
         */
        public function doStuffOnPrePersist()
        {
            $this->createdAt = date('Y-m-d H:m:s');
        }

        /**
         * @PHPCR\PrePersist
         */
        public function doOtherStuffOnPrePersist()
        {
            $this->value = 'changed from prePersist callback!';
        }

        /**
         * @PHPCR\PostPersist
         */
        public function doStuffOnPostPersist()
        {
            $this->value = 'changed from postPersist callback!';
        }

        /**
         * @PHPCR\PostLoad
         */
        public function doStuffOnPostLoad()
        {
            $this->value = 'changed from postLoad callback!';
        }

        /**
         * @PHPCR\PreUpdate
         */
        public function doStuffOnPreUpdate()
        {
            $this->value = 'changed from preUpdate callback!';
        }

        /**
         * @PHPCR\PreBindTranslation
         */
        public function doStuffOnPreBindTranslation()
        {
            $this->value = 'changed from preBindTranslation callback!';
        }

        /**
         * @PHPCR\PostBindTranslation
         */
        public function doStuffOnPostBindTranslation()
        {
            $this->value = 'changed from postBindTranslation callback!';
        }

        /**
         * @PHPCR\postLoadTranslation
         */
        public function doStuffOnPostLoadTranslation()
        {
            $this->value = 'changed from postLoadTranslation callback!';
        }
        /**
         * @PHPCR\PreRemoveTranslation
         */
        public function doStuffOnPreRemoveTranslation()
        {
            $this->value = 'changed from preRemoveTranslation callback!';
        }
        /**
         * @PHPCR\PostRemoveTranslation
         */
        public function doStuffOnPostRemoveTranslation()
        {
            $this->value = 'changed from postRemoveTranslation callback!';
        }

    .. code-block:: yaml

        MyPersistentClass:
          lifecycleCallbacks:
            prePersist: [ doStuffOnPrePersist, doOtherStuffOnPrePersistToo ]
            postPersist: [ doStuffOnPostPersist ]

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <lifecycle-callbacks>
                    <lifecycle-callback type="prePersist" method="doStuffOnPrePersist"/>
                    <lifecycle-callback type="postPersist" method="doStuffOnPostPersist"/>
                </lifecycle-callbacks>
            </document>
        </doctrine-mapping>

The methods mapped to the callbacks in xml or yml need to be public methods of your document.

The ``key`` of the lifecycleCallbacks is the name of the method and
the value is the event type. The allowed event types are the ones
listed in the previous Lifecycle Events section.


.. note::

    Contrary to the ORM, PHPCR-ODM does **not** use the @HasLifecycleCallbacks marker.


Listening to Lifecycle Events
-----------------------------

This works exactly the same as with the `ORM events <http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#listening-to-lifecycle-events>`_.
