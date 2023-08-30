Accessing the underlying PHPCR session
======================================

PHPCR-ODM builds on top of the PHPCR api. You can access this to do operations
not provided by the DocumentManager. However, if you do any data manipulations,
you risk to get the DocumentManager out of sync. If you do not know exactly what
you do, it is recommended to flush before accessing the PHPCR layer, and then not
use the DocumentManager any longer. To flush the operations you did on PHPCR layer,
you can call ``SessionInterface::save()``


Getting the PHPCR Session
-------------------------

The DocumentManager provides access to the PHPCR session::

    $session = $documentManager->getPhpcrSession();
    // do stuff
    $session->save();

.. _phpcraccess_nodefieldmapping:

The Node field mapping
----------------------

Using the node mapping, you can set the ``PHPCR\NodeInterface`` to a field of your document.
This field is populated on find, and as soon as you register the document with the manager using persist().


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Document
         */
        class MyPersistentClass
        {
            /**
             * @PHPCR\Node
             */
            private $node;
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass">
                <node fieldName="node"/>
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            node: node
