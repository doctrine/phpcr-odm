Association Mapping
===================

This chapter introduces association mappings. PHPCR has a tree based model of the content.
In addition to references to arbitrary other documents, every document has a parent and may
have children.

We will start with the hierarchy associations and then go into the references, which work
slightly differently from what you are used from an RDBMS as well.

.. _hierarchy-mappings:

Hierarchy mappings
------------------

We have already seen the ``ParentDocument`` in the previous chapter in the section about
identifier generation. The field with this annotation maps the parent document of this document
(``PHPCR\NodeInterface::getParent()``). If the repository can determine the document class of the
parent, it will use it, otherwise ``Doctrine\ODM\PHPCR\Document\Generic`` is used.

To map children, you have two options:

- You can map a single child with a specific name.
- Or you can map a :ref:`collection <collections>` of children, with the possibility to filter on the document name.

A single Child will always load only one document. If no explicit name is specified, the field
name is used to retrieve the child document. The name is the last part of that document's id,
resp. its Nodename mapping.

.. warning::

    When persisting such a document, the name of the child document set on that
    field must be empty or match the configured name. If it is not the expected
    name, you will get an exception.

    PHPCR-ODM 1.0 silently changed the name of the child to the name of the
    mapping, which could lead to unexpected behaviour.

To map a collection of children, use ``Children``. This will always be a collection, regardless of
the number of found children. You can limit the children to a subset of all children by specifying
a ``filter`` that acts on the node name. See `PHPCR\NodeInterface::getNodes() <http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getNodes()>`_
for details on allowed filters.

To tweak performance, you can also specify a ``fetchDepth`` if you know that you will always access children
of the mapped children. This can improve performance when you need to load a more complicated structure.
(See also :doc:`Tuning the node preloading <fetch-depth>`).

Some sample mappings:

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Parentdocument
         */
        private $parent;

        /**
         * @PHPCR\Child
         */
        private $mychild;

        /**
         * @PHPCR\Children(filter="a*", fetchDepth=3)
         */
        private $children;

    .. code-block:: xml

        <doctrine-mapping>
          <document name="MyPersistentClass">
            <parentdocument name="parent" />
            <child fieldName="mychild" />
            <children fieldName="children" filter="a*" fetchDepth="3" />
          </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          parentdocument: parent
          child:
            mychild: ~
          children:
            some:
             filter: "a*"
             fetchDepth: 3

Child restriction
~~~~~~~~~~~~~~~~~

You may either specify which classes may be children of a document or that a
document is not allowed to have children (i.e. that it is a leaf node).

.. configuration-block::

    .. code-block:: php

        <?php
        /**
         * @Document(childClasses={"App\Documents\Article", "App\Documents\Page"})
         */
        class ContentFolder
        {
            // ...
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document class="ContentFolder">
                <child-class>Article</child-class>
                <child-class>Page</child-class>
                <!-- ... -->
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        ContentFolder:
            # ...
            child_classes: [ "Article", "Page" ]

To specify that a document can have no children:

.. configuration-block::

    .. code-block:: php

        <?php
        /**
         * @Document(isLeaf=true)
         */
        class LeafDocument
        {
            // ...
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document class="LeafDocument" is-leaf="true">
                <!-- ... -->
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            # ...
            is_leaf: true

References
----------

In PHPCR any referenceable node can be referenced by any other node, regardless of their types.

References are always directional, but thanks to the Referrers, you can model the back reference as well.

References use universally unique identifiers automatically generated on documents if they
are set to be referenceable. That way, a reference will stay intact even if documents are moved.
A field can reference one or many documents, and it can enforce referencial integrity or create
a weak reference that does not ensure integrity, depending on your use case.


.. _association-mapping_referenceable:

Referenceable documents
~~~~~~~~~~~~~~~~~~~~~~~

To be allowed to reference a document, it needs to be referenceable. To achieve this, this fact needs
to be specified in the Document mapping. Having a referenceable document also allows you to
map its uuid to a field.

The Uuid is read only, autogenerated on the first flush of the document. It follows the universally unique
id standard and is guaranteed to be unique for the whole PHPCR repository (all workspaces).


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Document(referenceable=true)
         */
        class MyPersistentClass
        {
            /**
             * @PHPCR\Uuid
             **/
            private $uuid;

        }

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass" referenceable="true">
                <uuid fieldName="uuid" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          referenceable: true
          fields:
            uuid:
              uuid: true

.. note::

    PHPCR-ODM has no migrations (yet). If you change a document class to become referenceable,
    you need to load and save every document of that type to make the stored documents note the change.


.. _associationmapping_referenceotherdocuments:

Reference other documents
~~~~~~~~~~~~~~~~~~~~~~~~~

As noted above, the target document needs to be referenceable. Apart from that, there is
no limitation on the type of the target document, giving you great flexibility.

There are two mappings, ReferenceOne and ReferenceMany to reference one or several
target documents. ReferenceMany is using ``doctrine/common``'s collections.


You can specify for each reference if it should ensure referencial integrity or just
be a weak reference. By default, a weak reference is created, allowing you to delete
the referenced target document. Alternatively you can also tell PHPCR-ODM to reference by path,
which is interesting to create references to non-referenceable documents and when using relative paths.
A path reference will never ensure referential integrity.
(TODO: solve the open issue of how we can make paths relative and document here)

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\ReferenceOne(strategy="weak")
         */
        private $weakTarget;

        /**
         * @PHPCR\ReferenceOne(strategy="hard")
         */
        private $hardTarget;

        /**
         * @PHPCR\ReferenceOne(strategy="path")
         */
        private $pathTarget;

        /**
         * @PHPCR\ReferenceMany(strategy="weak")
         */
        private $weakGroup;

        /**
         * @PHPCR\ReferenceMany(strategy="hard")
         */
        private $hardGroup;

        /**
         * @PHPCR\ReferenceMany(strategy="path")
         */
        private $pathGroup;

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass">
                <reference-one fieldName="weakTarget" strategy="weak" />
                <reference-one fieldName="hardTarget" strategy="hard" />
                <reference-one fieldName="pathTarget" strategy="path" />
                <reference-many fieldName="weakGroup" strategy="weak" />
                <reference-many fieldName="hardGroup" strategy="hard" />
                <reference-many fieldName="pathGroup" strategy="path" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            referenceOne:
                weakTarget:
                  strategy: weak
                hardTarget:
                  strategy: hard
                pathTarget:
                  strategy: path
            referenceMany:
                weakGroup:
                  strategy: weak
                hardGroup:
                  strategy: hard
                pathGroup:
                  strategy: path

``ReferenceMany`` documents will always be handled as collections to allow for lazy loading,
regardless of the strategy chosen.

All types of reference support the optional argument ``targetDocument``.
This can be used to tell what the expected document type for the reference target is.
If you only reference documents of one specific type, you can use this as sanity check,
additionally path references will be faster this way.

If you do not set the targetDocument, you can reference documents of any type.
In ReferenceMany collections, you can even have documents of mixed types.


.. note::

    If your repository supports programmatically setting the uuid property at node creation,
    you can just persist your main document and the referenced documents will be persisted
    automatically.

    Otherwise you first need to flush the document manager for the reference targets before
    you can reference them in your document.


.. warning::

    When using hard references in combination with versioning, old versions of
    your documents may still have target documents that become null if the
    target has been deleted since the version has been created. This is due to
    PHPCR not ensuring referential integrity for old versions as otherwise you
    could never delete a document once it has been referenced and the reference
    versioned, even if the reference is deleted later. When working with
    versions, you thus always need to check if a referenced document actually
    exists.


Referrers to inverse the reference relation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

PHPCR-ODM is able to tell which documents reference a specific document, if the ``hard`` or
``weak`` strategy is used. The ``Referrers`` mapping is a collection of documents that have
a reference to this document.

In ORM terms, the Reference is the owning side of the association, while the
Referrer is the inverse side. Contrary to the ORM, the PHPCR references really
are directional, they are always stored in the property of the document with
the ReferenceOne or ReferenceMany field. Referrer is a purely virtual information
that is not explicitly stored in the PHPCR database but determined at runtime.

You need to specify the ``referringDocument`` to specify the (base) class of the
document that has the reference, and ``referencedBy`` to tell which field of the
referencing document contains the reference. After flushing, the reference property
will contain the referenced document.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Referrers(referringDocument="FQN\Class\Name", referencedBy="otherFieldName")
         */
        private $specificReferrers;

        /**
         * @PHPCR\Referrers(referringDocument="Other\Class\Name", referencedBy="someFieldName", cascade="persist, remove")
         */
        private $cascadedReferrers;

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass">
                <referrers fieldName="specificReferrers" referring-document="FQN\Class\Name" referenced-by="otherFieldName" />
                <referrers fieldName="cascadedReferrers" referring-document="Other\Class\Name" referenced-by="someFieldName" cascade="persist, remove" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            referrers:
                specificReferrers:
                    referringDocument: FQN\Class\Name
                    referencedBy: otherFieldName
                cascadedReferrers:
                    referringDocument: Other\Class\Name
                    referencedBy: someFieldName
                    cascade: persist, remove


Referrers can cascade like the other association mappings to persist or delete their
referrers if desired.

.. note::

    The main use case to persist cascade or deletion of the referrer mapping
    is to build a form where it is possible to add documents that should reference
    this content. However, it is not allowed to modify both the reference collection
    and the referrer collection of interlinked content, as this would be ambiguous.

.. tip::

    There is also the ``DocumentManager::getReferrers()`` method that allows you
    to get more fine grained control on what referencing documents are returned,
    if ``Referrers`` is too limited and ``MixedReferrers`` too broad.


MixedReferrers
~~~~~~~~~~~~~~

The mixed referrers is a much simpler but read only mapping to get a collection
of *all* documents that have a reference to this document. The only possible option
of mixed referrers is `referenceType` to limit the referrers to only hard resp. weak
references. If left out, you get both types of references.

Mixed referrers can even be mapped on a document that is not referenceable, as you
might do it on a base document of which some extending documents are referenceable.
An example for this is the `Generic` document provided by phpcr-odm itself.


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\MixedReferrers
         */
        private $allReferrers;

        /**
         * @PHPCR\MixedReferrers(referenceType="hard")
         */
        private $hardReferrers;

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass">
                <mixed-referrers fieldName="allReferrers" />
                <mixed-referrers fieldName="hardReferrers" reference-type="hard" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            mixedReferrers:
                allReferrers: ~
                hardReferrers:
                    referenceType: hard

.. _assocmap_cascading:
.. _assocmap_transpers:

Transitive persistence / Cascade Operations
-------------------------------------------

Persisting, removing, detaching and merging individual documents can
become pretty cumbersome, especially when a highly interweaved object graph
is involved. PHPCR-ODM provides cascading with the same concepts as
Doctrine2 ORM does.

Each association to another document or a collection of documents can be
configured to automatically cascade certain operations. For the ``Children`` mapping,
cascading persist and remove are implicit and cannot be disabled. A PHPCR node
always must have a parent, removing the parent removes its children.
The child removal happens on PHPCR level and does not trigger additional
lifecycle events.

For References and Referrers, no operations are cascaded by default, they
can be configured specifically.

The following cascade options exist:

-  **persist**: Cascades persist operations to the associated documents.
-  **remove**: Cascades remove operations to the associated documents.
-  **merge**: Cascades merge operations to the associated documents.
-  **detach**: Cascades detach operations to the associated documents.
-  **refresh**: Also refresh the associated documents when refreshing this document.
-  **translation**: Cascade the current translation locale to associated documents.
-  **all**: Cascades persist, remove, merge, detach, refresh and translation
   operations to associated documents.

.. note::

    Cascade operations are performed in memory. That means collections and related documents
    are fetched into memory, even if they are still marked as lazy when
    the cascade operation is about to be performed. This approach allows
    document lifecycle events to be performed for each of these operations.

    However, pulling a large object graph into memory on cascade can cause considerable performance
    overhead, especially when cascading collections are large. Makes sure
    to weigh the benefits and downsides of each cascade operation that you define.

Even though automatic cascading is convenient it should be used
with care. Do not blindly apply ``cascade=all`` to all associations as
it will unnecessarily degrade the performance of your application.
For each cascade operation that gets activated Doctrine also
applies that operation to the association, be it single or
collection valued.

Persistence by Reachability: Cascade Persist
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There are additional semantics that apply to the Cascade Persist
operation. During each flush() operation Doctrine detects if there
are new documents in any collection and three possible cases can
happen:

1. New documents in a collection marked as cascade persist will be
   directly persisted by Doctrine.
2. New documents in a collection not marked as cascade persist will
   produce an Exception and rollback the flush() operation.
3. Collections without new documents are skipped.

This concept is called "Persistence by Reachability". New documents
that are found on already managed documents are automatically
persisted as long as the association is defined as cascade
persist.


.. _collections:

Collections
-----------

All many-valued associations of PHPCR-ODM use implementations of the ``Collection``
interface. They are more powerful than plain arrays. Read sections 8.2 to 8.5 in
the ORM documentation `Working with associations <http://docs.doctrine-project.org/en/latest/reference/working-with-associations.html>`_
if you are not familiar with associations.

Your domain models need to use those classes, but they are defined in a
specific doctrine collections repository and thus not specific to any
persistence implementation.
For a discussion of this topic, see the `Collections section <http://docs.doctrine-project.org/en/latest/reference/association-mapping.html#collections>`_
in the ORM documentation.

Initializing Collections
~~~~~~~~~~~~~~~~~~~~~~~~

You have to be careful when using document fields that contain a
collection of related documents. Say we have a User document that
contains a collection of groups::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document
     */
    class User
    {
        /**
         * @PHPCR\ReferenceMany
         */
        private $groups;

        public function getGroups()
        {
            return $this->groups;
        }
    }

With this code alone the ``$groups`` field only contains an
instance of ``Doctrine\Common\Collections\Collection`` if the user
is retrieved from Doctrine, however not after you instantiated a
fresh instance of the User. When your user document is still new
``$groups`` will obviously be null.

This is why we recommend to initialize all collection fields to an
empty ``ArrayCollection`` in your documents constructor::

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document
     */
    class User
    {
        /**
         * @PHPCR\ReferenceMany
         */
        private $groups;

        public function __construct()
        {
            $this->groups = new ArrayCollection();
        }

        public function getGroups()
        {
            return $this->groups;
        }
    }

Now the following code will be working even if the Document hasn't
been associated with a DocumentManager yet::

    $group = $documentManager->find(null, $groupId);
    $user = new User();
    $user->getGroups()->add($group);

New Collections after Flushing
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

On flushing, Doctrine replaces all collection fields (children, reference many,
referrers, mixed referrers) that contain plain arrays or ArrayCollection with
the appropriate persistent collection class for the field type.

When flushing a new document, their collections are *not* synchronized with the
database, though cascading happens as explained above. The collections thus
only show the documents that where explicitly added. If other documents are
added directly (e.g. a child with assigned id), you will only see them after
calling ``$dm->refresh()``, or in subsequent requests.

On an existing document, setting a field to an array or new collection
overwrites all existing documents that previously where in that field, leading
to the deletion of the previous documents.
