Attributes Mapping
==================

In this chapter a reference of every PHPCR-ODM attribute is listed. with short
explanations on their context and usage.

Note on usage
-------------

Note that the code examples are given without their namespaces, however it is
normally necessary to import the attribute namespace into your class, and to
prefix each attribute with the namespace as demonstrated in the following example::

    namespace MyProject\Bundle\BlogBundle\Document;
    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document]
    class Post
    {
        #[PHPCR\Id]
        private string $id;

        #[PHPCR\ParentDocument]
        private object $parent;

        #[PHPCR\Nodename]
        private string $title;
    }

Document
--------

.. _attref_document:

#[Document]
~~~~~~~~~~~

Optional parameters:

- **nodeType**: PHPCR type for this node, default ``nt:unstructured``.
- **uniqueNodeType**: If this document has a unique node type, set to ``true``
  in order to support outer joins correctly. See
  :ref:`left outer join <_qbref_method_querybuilder_addjoinleftouter>` and
  :ref:`right outer join <_qbref_method_querybuilder_addjoinrightouter>`.
  To register a custom node type, use the ``phpcr:node-type:register`` console
  command (use ``help phpcr:node-type:register`` for the syntax; see :doc:`Tools <tools>`
  for more information). To verify that documents claiming to have unique node types
  are truly unique, use the ``doctrine:phpcr:mapping:verify-unique-node-types`` command.
- **repositoryClass**: Name of the repository to use for this document.
- **versionable**: *(string)* Set to ``simple`` or ``full`` to enable versioning
  (respectively simple or full level), ``false`` to disable versioning
  inheritance. Implies *referenceable*. Note that not every PHPCR implementation
  support this feature. See :doc:`Versioning <versioning>`.
- **referenceable**: Set to true to allow this node to be referenced.
- **translator**: Determines how translations are stored, one of ``attribute``
  or ``child``. See :ref:`langauge mapping <multilang_mapping>`
- **mixins**: Optional list of PHPCR mixins that will be added to the node on
  creation. Note that if this field is present, it overwrites the same field
  from the anchestor documents so you have to repeat mixins you want to keep
  if you add a mixins field.
- **childClasses**: List of valid child classes (if empty any classes are
  permitted).
- **isLeaf**: If the document should act as a leaf (i.e. it can have no
  children). Mutually exclusive with ``childClasses``.

Minimal example::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document]
    class User
    {
        // ...
    }

Full example::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document(
        repositoryClass: UserRepository::class,
        versionable: 'full',
        referenceable: true,
        translator: 'child',
        mixins: ['mix:created', 'mix:lastModified'],
        childClasses: [Article::class, Page::class]
    )]
    class Article
    {
        // ...
    }

.. note::

   The ``uniqueNodeType`` attribute is not supported with the sqlite database.

.. _attref_mappedsuperclass:

#[MappedSuperclass]
~~~~~~~~~~~~~~~~~~~

A mapped superclass is an abstract or concrete class that provides
persistent document state and mapping information for its subclasses
but which is not itself a document.

.. note::

    Contrary to ORM, the PHPCR-ODM with its NoSQL nature can handle documents
    that extend each other just like any other document, so you only need mapped
    superclasses in special situations. See also :doc:`Inheritance Mapping <inheritance-mapping>`.


Optional parameters:

-  **nodeType**: PHPCR type for this node. Default ``nt:unstructured``.
-  **repositoryClass**: Fully qualified name of the repository to use for
   documents extending this superclass.
-  **translator**: Determines how translations are stored, one of ``attribute``
   or ``child``. See :ref:`language mapping <multilang_mapping>`.

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\MappedSuperclass]
    class MappedSuperclassBase
    {
        // ... fields and methods
    }

    #[PHPCR\Document]
    class DocumentSubClassFoo extends MappedSuperclassBase
    {
        // ... fields and methods
    }


Mapping Fields
--------------

You can attribute an instance variable with the ``#[Field]`` attributeto make it
"persistent".

.. _attref_field:


#[Field]
~~~~~~~~

parameters:

- **property**: The PHPCR property name to which this field is stored.
  Defaults to the field name.
- **assoc**: Specify that this attribute should be an associative array. The value should
  be a string which will be used by the PHPCR node. Set to an empty string to automatically
  use the name of the property with that attribute appended by "Keys".
- **multivalue**: ``true`` to specify that this property should be treated as a simple array.
  See :ref:`Mapping multivalue properties <basicmapping_mappingmultivalueproperties>`.
- **translated**: ``true`` to specify that the property should be translatable, requires the
  ``translator`` attribute to be specified in :ref:`#[Document]<annref_document>`.
- **nullable**: ``true`` to specifiy that this property doesn't have a required value, used
  when loading a translation, to allow loading a node with a missing translated property.
- **type**: Type of the field, see table below.

Types:

- **binary**: Sets the type of the property to binary.
- **boolean**: Sets the type of the property to boolean.
- **date**: Sets the type of the property to DateTime.
- **decimal**: Sets the type of the property to decimal,
  the decimal field uses the BCMath library which supports numbers of any size
  or precision.
- **double**: Sets the type of the property to double. The PHP type will be **float**.
- **long**: Sets the type of the property to long. The PHP type will be **integer**.
- **name**: The property must be a valid XML CNAME value
  and can be used to store a valid node name.
- **path**: The property must be a valid PHPCR node path
  and can be used to store an arbitrary reference to another node.
- **string**: Sets the type of the property to string.
- **uri**: The property will be validated as an URI.

Examples::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Field(type: 'string')]
    private string $author;

    #[PHPCR\Field(type: 'string', translated: true)]
    private string $title;

    #[PHPCR\Field(type: 'string', translated: true, nullable: true)]
    private ?string $subTitle;

    #[PHPCR\Field(type: 'boolean')]
    private bool $enabled;

    #[PHPCR\Field(type: 'string', multivalue: true)]
    private array $keywords; // e.g. ['dog', 'cat', 'mouse']

    #[PHPCR\Field(type: 'double', assoc: '')]
    private array $exchangeRates; // e.g. ['GBP' => 0.810709, 'EUR' => 1, 'USD' => 1.307460]

Hierarchy
---------

These mappings mark the properties to contain instances of Documents
above or below the current Document in the document hierarchy, or information
about the state of the document within the hierarchy. They need to be
specified inside the instance variables associated PHP DocBlock comment.

.. _attref_child:

#[Child]
~~~~~~~~

The property will be populated with the named document
directly below the instance variables document class in the document hierarchy.

Required parameters:

- **nodeName**: PHPCR Node name of the child document to map, this should be a string.

Optional parameters:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\Child(name: 'Preferences')]
   private object $preferences;

.. _attref_children:

#[Children]
~~~~~~~~~~~

The property will be populated with Documents directly below the
instance variables document class in the document hierarchy.

Optional parameters:

- **filter**: Child name filter; only return children whose names match the given filter.
- **fetchDepth**: Performance optimisation, number of levels to pre-fetch and cache,
  this should be an integer.
- **ignoreUntranslated**: Set to false to *not* throw exceptions on untranslated child
  documents.
- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
    use Doctrine\ODM\PHPCR\ChildrenCollection;

    #[PHPCR\Children(filter: 'a*', fetchDepth: 3)]
    private ChildrenCollection $children;

.. _attref_depth:

#[Depth]
~~~~~~~~

The property will be populated with an integer value
representing the depth of the document within the document hierarchy::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Depth]
    private int $depth;

.. _attref_parentdocument:

#[ParentDocument]
~~~~~~~~~~~~~~~~~

Optional parameters:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

The property will contain the nodes parent document. Assigning
a different parent will result in a move operation::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\ParentDocument]
   private object $parent;

Identification
--------------

These mappings help to manage the identification of the document class.

.. _attref_id:

#[Id]
~~~~~

The property will be marked with the documents
identifier. The ID is the **full path** to the document in the document hierarchy.
See :ref:`identifiers <basicmapping_identifiers>`.

Required parameters:

- **strategy**: How to generate IDs, one of ``NONE``, ``REPOSITORY``, ``ASSIGNED`` or ``PARENT``, default
  is ``PARENT`` See :ref:`generation strategies <basicmapping_identifier_generation_strategies>`.

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\Id]
   private string $id; // e.g. /path/to/mydocument

.. _attref_nodename:

#[Nodename]
~~~~~~~~~~~

Mark the property as representing the name of the node. The name
of the node is the last part of the :ref:`ID <annref_id>`. Changing the marked variable will update
the nodes ID::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\Id]
   private string $id; // e.g. /path/to/mydocument

   #[PHPCR\Nodename]
   private string $nodeName; // e.g. mydocument

.. _attref_uuid:

#[Uuid]
~~~~~~~

The property will be populated with a UUID
(Universally Unique Identifier). The UUID is immutable. For
this field to be reliably populated the document should be
*referenceable*::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\Uuid]
   private string $uuid; // e.g. 508d6621-0c20-4972-bf0e-0278ccabe6e5

Lifcycle callbacks
------------------

These attributes are used to map information on methods of a document.
The method is called automatically by the ODM on the
:ref:`lifecycle event <events_lifecyclecallbacks>` corresponding to the attribute.

.. note::

   Unlike the Doctrine ORM it is **not** necessary to specify a ``#[HasLifecycleCallbacks]``
   attribute.

.. _attref_postload:

#[PostLoad]
~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postLoad``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PostLoad]
    public function doSomethingOnPostLoad(): void
    {
       // ... do something after the Document has been loaded
    }

.. _attref_postpersist:

#[PostPersist]
~~~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postPersist``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PostPersist]
    public function doSomethingOnPostPersist(): void
    {
      // ... do something after the document has been persisted
    }

.. _attref_postremove:

#[PostRemove]
~~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postRemove``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PostRemove]
    public function doSomethingOnPostRemove(): void
    {
      // ... do something after the document has been removed
    }

.. _attref_postupdate:

#[PostUpdate]
~~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postUpdate``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PostUpdate]
    public function doSomethingOnPostUpdate(): void
    {
      // ... do something after the document has been updated
    }

.. _attref_prepersist:

#[PrePersist]
~~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``prePersist``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PrePersist]
    public function doSomethingOnPrePersist(): void
    {
      // ... do something before the document has been persisted
    }

.. _attref_preremove:

#[PreRemove]
~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``preRemove``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PreRemove]
    public function doSomethingOnPreRemove(): void
    {
      // ... do something before the document has been removed
    }

.. _attref_preupdate:

#[PreUpdate]
~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``preUpdate``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\PreUpdate]
    public function doSomethingOnPreUpdate(): void
    {
      // ... do something before the document has been updated
    }

PHPCR
-----

.. _attref_node:

#[Node]
~~~~~~~

The property will be populated with the underlying
PHPCR node. See :ref:`node field mapping <phpcraccess_nodefieldmapping>`.

References
----------

.. _attref_referencemany:

#[ReferenceMany]
~~~~~~~~~~~~~~~~

Optional parameters:

-  **targetDocument**: Specify type of target document class. Note that this
   is an optional parameter and by default you can associate *any* document.
-  **strategy**: One of ``weak``, ``hard`` or ``path``. See :ref:`reference other documents <associationmapping_referenceotherdocuments>`.

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
    use Doctrine\ODM\PHPCR\ReferenceManyCollection;

    #[PHPCR\ReferenceMany(targetDocument: PhoneNumber::class, strategy: 'hard')]
    private ReferenceManyCollection $phoneNumbers;

.. _attref_referenceone:
.. _attref_reference:

#[ReferenceOne]
~~~~~~~~~~~~~~~

Optional parameters:

-  **targetDocument**: Specify type of target document class. Note that this
   is an optional parameter and by default you can associate *any* document.
-  **strategy**: One of `weak`, `hard` or `path`. See :ref:`reference other documents <associationmapping_referenceotherdocuments>`.
- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

   #[PHPCR\ReferenceOne(targetDocument: Contact::class, strategy: 'hard')]
   private object $contact;

.. _attref_referrers:

#[Referrers]
~~~~~~~~~~~~

Mark the property to contain a collection of the documents
of the given document class which refer to this document.

Required parameters:

- **referringDocument**: Full class name of referring document, the instances
  of which should be collected in the property.
- **referencedBy**: Name of the property from the referring document class
  which refers to this document class.

Optional parameters:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
    use Doctrine\ODM\PHPCR\ReferrersCollection;

    #[PHPCR\Referrers(referringDocument: Address::class, referencedBy: 'addressbook')]
    private ReferrersCollection $addresses;

#[MixedReferrers]
~~~~~~~~~~~~~~~~~

Mark the property to hold a collection of *all* documents
which refer to this document, regardless of document class.

Optional parameters:

-  **referenceType**: One of ``weak`` or ``hard``.

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
    use Doctrine\ODM\PHPCR\ReferrersCollection;

    #[PHPCR\MixedReferrers]
    private ReferrersCollection $referrers;

Translation
-----------

These attributes only apply to documents where the ``translator`` attribute is
specified in :ref:`#[Document]<annref_document>`.

Example::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document(translator: 'attribute')]
    class MyDocument
    {
       #[PHPCR\Locale]
       private string $locale;

       #[PHPCR\Field(type: 'string', translated: true)]
       private string $title;
    }

.. _attref_locale:

#[Locale]
~~~~~~~~~

Identifies the property as the field in which to store
the documents current locale.

Versioning
----------

These attributes only apply to documents where the ``versionable`` attribute is
specified in :ref:`#[Document]<annref_document>`.

See :ref:`versioning mappings <versioning_mappings>`.

Example::

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document(versionable: 'simple')]
    class MyPersistentClass
    {
        #[PHPCR\VersionName]
        private string $versionName;

        #[PHPCR\VersionCreated]
        private \DateTimeInterface $versionCreated;
    }

.. _attref_versioncreated:

#[VersionCreated]
~~~~~~~~~~~~~~~~~

The property will be populated with the date
that the current document version was created. Applies only to
documents with the versionable attribute.

.. _attref_versionname:

#[VersionName]
~~~~~~~~~~~~~~

The property will be populated with the name
of the current version as given by PHPCR.

.. |cascade_definition| replace:: One of ``persist``, ``remove``, ``merge``, ``detach``, ``refresh``, ``translation`` or ``all``.
