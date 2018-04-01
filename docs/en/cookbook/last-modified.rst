Last modification timestamp
===========================

PHPCR provides mixin types with special meaning. This cookbook is about
`mix:created <http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.7.11.7%20mix:created>`_
and `mix:lastModified <http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.7.11.8%20mix:lastModified>`_.

Using the build-in support
--------------------------

If the PHPCR implementation you use supports the mixins automatically,
you can get timestamps on your documents by simply adding the mixins:

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Document(
         *   mixins={"mix:created", "mix:lastModified"}
         * )
         */
        class SomeDocument
        {
            /** @PHPCR\Field(type="date", property="jcr:created") */
            private $created;

            /** @PHPCR\Field(type="string", property="jcr:createdBy") */
            private $createdBy;

            /** @PHPCR\Field(type="date", property="jcr:lastModified") */
            private $lastModified;

            /** @PHPCR\Field(type="string", property="jcr:lastModifiedBy") */
            private $lastModifiedBy;
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document name="SomeDocument">
                <field fieldName="created" property="jcr:created" />
                <field fieldName="createdBy" property="jcr:createdBy" />
                <field fieldName="lastModified" type="jcr:lastModified" />
                <field fieldName="lastModifiedBy" type="jcr:lastModifiedBy" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        SomeDocument:
            fields:
                created:
                    property: "jcr:created"
                createdBy:
                    property: "jcr:createdBy"
                lastModified:
                    property: "jcr:lastModified"
                lastModifiedBy:
                    property: "jcr:lastModifiedBy"

After flushing this document, these fields will be populated. The creation
date of course will never change, but the last modified date will be updated
whenever you change properties on the document.

The createdBy and lastModifiedBy fields contain the user name used for the
PHPCR backend connection. This is most likely a static name coming from your
configuration.

You can also set lastModified or lastModifiedBy manually with your update
to get your custom values.

.. note::

    Support for automatically updating mix:lastModified documents was added to
    Jackalope in version 1.1. If you are using 1.0, you need to use the manual
    method explained in the next section to get automated last modification
    updates.

A lastModified listener for custom behaviour
--------------------------------------------

To customize the lastModified logic, you can build a
:doc:`listener <../reference/events>` to manually set the properties
as needed. If you do this, you want to disable automated lastModified
support in your PHPCR implementation.

For Jackalope, you can set the ``jackalope.auto_lastmodified`` option to false
in the parameters to ``RepositoryFactory::getRepository``. This will only
affect mix:lastModified but not mix:created.

The document looks exactly the same as above. To update the modification
date, write an event listener as follows and register it with the EventManager::

    use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
    use Doctrine\ODM\PHPCR\DocumentManager;

    /**
     * This listener ensures that the jcr:lastModified property is updated
     * on prePersist and preUpdate events.
     *
     * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
     */
    class LastModified
    {
        /**
         * @param LifecycleEventArgs $e
         */
        public function prePersist(LifecycleEventArgs $e)
        {
            $this->updateLastModifiedProperty($e);
        }

        /**
         * @param LifecycleEventArgs $e
         */
        public function preUpdate(LifecycleEventArgs $e)
        {
            $this->updateLastModifiedProperty($e);
        }

        /**
         * If the document has the mixin mix:lastModified then update the field
         * that is mapped to jcr:lastModified.
         *
         * @param LifecycleEventArgs $e
         */
        protected function updateLastModifiedProperty(LifecycleEventArgs $e)
        {
            $document = $e->getObject();

            /**
             * @var DocumentManager $dm
             */
            $dm = $e->getObjectManager();

            $metadata = $dm->getClassMetadata(get_class($document));
            $mixins = $metadata->getMixins();

            if (!in_array('mix:lastModified', $mixins)) {
                return;
            }

            // custom logic to determine if we need to update the lastModified date goes here.
            // ...

            // look for the field mapped to jcr:lastModified and update
            foreach ($metadata->getFieldNames() as $fieldName) {
                $field = $metadata->getField($fieldName);
                if ('jcr:lastModified' == $field['property']) {
                    $metadata->setFieldValue($document, $fieldName, new \DateTime());
                    break;
                }
            }
        }
    }

If you need to update lastModifiedBy, follow the same pattern.
