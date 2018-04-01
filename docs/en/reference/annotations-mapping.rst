Annotation Mapping
==================

In this chapter a reference of every PHPCR-ODM annotation is given with short
explanations on their context and usage.

Note on usage
-------------

Note that the code examples are given without their namespaces, however it is
normally necessary to import the annotation namespace into your class, and to
prefix each annotation with the namespace as demonstrated in the following example::

    namespace MyProject\Bundle\BlogBundle\Document;
    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document()
     */
    class Post
    {
        /**
         * @PHPCR\Id()
         */
        protected $id;

        /**
         * @PHPCR\ParentDocument()
         */
        protected $parent;

        /**
         * @PHPCR\Nodename
         */
        protected $title;
    }

Document
--------

.. _annref_document:

@Document
~~~~~~~~~

Optional attributes:

-  **nodeType**: PHPCR type for this node, default ``nt:unstructured``.
-  **uniqueNodeType**: If this document has a unique node type, set to ``true``
   in order to support outer joins correctly. See
   :ref:`left outer join <_qbref_method_querybuilder_addjoinleftouter>` and
   :ref:`right outer join <_qbref_method_querybuilder_addjoinrightouter>`.
   To register a custom node type, use the ``phpcr:node-type:register`` console
   command (use ``help phpcr:node-type:register`` for the syntax; see :doc:`Tools <tools>`
   for more information). To verify that documents claiming to have unique node types
   are truly unique, use the ``doctrine:phpcr:mapping:verify-unique-node-types`` command.
-  **repositoryClass**: Name of the repository to use for this document.
-  **versionable**: *(string)* Set to ``simple`` or ``full`` to enable versioning
   (respectively simple or full level), ``false`` to disable versioning
   inheritance. Implies *referenceable*. Note that not every PHPCR implementation
   support this feature. See :doc:`Versioning <versioning>`.
-  **referenceable**: Set to true to allow this node to be referenced.
-  **translator**: Determines how translations are stored, one of ``attribute``
   or ``child``. See :ref:`langauge mapping <multilang_mapping>`
-  **mixins**: Optional list of PHPCR mixins that will be added to the node on
   creation. Note that if this field is present, it overwrites the same field
   from the anchestor documents so you have to repeat mixins you want to keep
   if you add a mixins field.

Minimal example::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document()
     */
    class User
    {
        // ...
    }

Full example::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document(
     *   repositoryClass="MyProject\UserRepository",
     *   versionable="full",
     *   referenceable=true,
     *   translator="child",
     *   mixins={"mix:created", "mix:lastModified"}
     * )
     */
    class SomeDocument
    {
        // ...
    }

.. note::

   The ``uniqueNodeType`` attribute is not supported with the sqlite database.

.. _annref_mappedsuperclass:

@MappedSuperclass
~~~~~~~~~~~~~~~~~

A mapped superclass is an abstract or concrete class that provides
persistent document state and mapping information for its subclasses
but which is not itself a document.

.. note::

    Contrary to ORM, the PHPCR-ODM with its NoSQL nature can handle documents
    that extend each other just like any other document, so you only need mapped
    superclasses in special situations. See also :doc:`Inheritance Mapping <inheritance-mapping>`.


Optional attributes:

-  **nodeType**: PHPCR type for this node. Default ``nt:unstructured``.
-  **repositoryClass**: Fully qualified name of the repository to use for
   documents extending this superclass.
-  **translator**: Determines how translations are stored, one of ``attribute``
   or ``child``. See :ref:`language mapping <multilang_mapping>`.

.. code-block:: php

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\MappedSuperclass()
     */
    class MappedSuperclassBase
    {
        // ... fields and methods
    }

    /**
     * @PHPCR\Document()
     */
    class DocumentSubClassFoo extends MappedSuperclassBase
    {
        // ... fields and methods
    }


Mapping Fields
--------------

You can annotate an instance variable with the ``@Field`` anotation to make it
"persistent".

.. note::

    Until PHPCR-ODM 1.2, the recommended way to map fields with annotations was using type specific
    annotations like ``@Binary``, ``@Boolean``, ``@Date``, ``@Decimal``, ``@Double``, ``@Float``,
    ``@Int``, ``@Long``, ``@Name``, ``@Path``, ``@String`` and ``@Uri``. These were deprecated in
    the 1.3 release in favor of the newly added ``@Field(type="...")`` annotation to fix
    incompatibilities with PHP 7. In 2.0, the old annotations have been removed.

.. _annref_field:


@Field
~~~~~~

Attributes:

- **property**: The PHPCR property name to which this field is stored.
  Defaults to the field name.
- **assoc**: Specify that this attribute should be an associative array. The value should
  be a string which will be used by the PHPCR node. Set to an empty string to automatically
  use the name of the annotated variable appended by "Keys".
- **multivalue**: ``true`` to specify that this property should be treated as a simple array.
  See :ref:`Mapping multivalue properties <basicmapping_mappingmultivalueproperties>`.
- **translated**: ``true`` to specify that the property should be translatable, requires the
  ``translator`` attribute to be specified in :ref:`@Document<annref_document>`.
- **nullable**: ``true`` to specifiy that this property doesn't have a required value, used
  when loading a translation, to allow loading a node with a missing translated property.
- **type**: Type of the field, see table below.

Types:

- **binary**: Sets the type of the annotated instance variable to binary.
- **boolean**: Sets the type of the annotated instance variable to boolean.
- **date**: Sets the type of the annotated instance variable to DateTime.
- **decimal**: Sets the type of the annotated instance variable to decimal,
  the decimal field uses the BCMath library which supports numbers of any size
  or precision.
- **double**: Sets the type of the annotated instance variable to double. The PHP type will be **float**.
- **long**: Sets the type of the annotated instance variable to long. The PHP type will be **integer**.
- **name**: The annotated instance variable must be a valid XML CNAME value
  and can be used to store a valid node name.
- **path**: The annotated instance variable must be a valid PHPCR node path
  and can be used to store an arbitrary reference to another node.
- **string**: Sets the type of the annotated instance variable to string.
- **uri**: The annotated instance variable will be validated as an URI.

Examples::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Field(type="string")
     */
    protected $author;

    /**
     * @PHPCR\Field(type="string", translated=true)
     */
    protected $title;

    /**
     * @PHPCR\Field(type="string", translated=true, nullable=true)
     */
    protected $subTitle;

    /**
     * @PHPCR\Field(type="boolean")
     */
    protected $enabled;

    /**
     * @PHPCR\Field(type="string", multivalue=true)
     */
    protected $keywords; // e.g. array('dog', 'cat', 'mouse')

    /**
     * @PHPCR\Field(type="double", assoc="")
     */
    protected $exchangeRates; // e.g. array('GBP' => 0.810709, 'EUR' => 1, 'USD' => 1.307460)

Hierarchy
---------

These mappings mark the annotated instance variables to contain instances of Documents
above or below the current Document in the document hierarchy, or information
about the state of the document within the hierarchy. They need to be
specified inside the instance variables associated PHP DocBlock comment.

.. _annref_child:

@Child
~~~~~~

The annotated instance variable will be populated with the named document
directly below the instance variables document class in the document hierarchy.

Required attributes:

- **nodeName**: PHPCR Node name of the child document to map, this should be a string.

Optional attributes:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\Child(name="Preferences")
    */
   protected $preferences;

.. _annref_children:

@Children
~~~~~~~~~

The annotated instance variable will be populated with Documents directly below the
instance variables document class in the document hierarchy.

Optional attributes:

- **filter**: Child name filter; only return children whose names match the given filter.
- **fetchDepth**: Performance optimisation, number of levels to pre-fetch and cache,
  this should be an integer.
- **ignoreUntranslated**: Set to false to *not* throw exceptions on untranslated child
  documents.
- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Children(filter="a*", fetchDepth=3)
     */
    private $children;

.. _annref_depth:

@Depth
~~~~~~

The annotated instance variable will be populated with an integer value
representing the depth of the document within the document hierarchy::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Depth()
     */
    private $depth;

.. _annref_parentdocument:

@ParentDocument
~~~~~~~~~~~~~~~

Optional attributes:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

The annotated instance variable will contain the nodes parent document. Assigning
a different parent will result in a move operation::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\ParentDocument
    */
   private $parent;

Identification
--------------

These mappings help to manage the identification of the document class.

.. _annref_id:

@Id
~~~

The annotated instance variable will be marked with the documents
identifier. The ID is the **full path** to the document in the document hierarchy.
See :ref:`identifiers <basicmapping_identifiers>`.

Required attributes:

- **strategy**: How to generate IDs, one of ``NONE``, ``REPOSITORY``, ``ASSIGNED`` or ``PARENT``, default
  is ``PARENT`` See :ref:`generation strategies <basicmapping_identifier_generation_strategies>`.

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\Id()
    */
   protected $id; // e.g. /path/to/mydocument

.. _annref_nodename:

@Nodename
~~~~~~~~~

Mark the annotated instance variable as representing the name of the node. The name
of the node is the last part of the :ref:`ID <annref_id>`. Changing the marked variable will update
the nodes ID::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\Id()
    */
   protected $id; // e.g. /path/to/mydocument

   /**
    * @PHPCR\Nodename()
    */
   protected $nodeName; // e.g. mydocument

.. _annref_uuid:

@Uuid
~~~~~

The annotated instance variable will be populated with a UUID
(Universally Unique Identifier). The UUID is immutable. For
this field to be reliably populated the document should be
*referenceable*::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\Uuid()
    */
   protected $uuid; // e.g. 508d6621-0c20-4972-bf0e-0278ccabe6e5

Lifcycle callbacks
------------------

These annotations, applied to a method, will cause the method to be called automatically
by the ODM on the :ref:`lifecycle event <events_lifecyclecallbacks>` corresponding to the name
of the annotation.

.. note::

   Unlike the Doctrine ORM it is **not** necessary to specify a ``@HasLifecycleCallbacks``
   annotation.

.. _annref_postload:

@PostLoad
~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postLoad``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PostLoad
     */
    public function doSomethingOnPostLoad()
    {
       // ... do something after the Document has been loaded
    }

.. _annref_postpersist:

@PostPersist
~~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postPersist``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PostPersist
     */
    public function doSomethingOnPostPersist()
    {
      // ... do something after the document has been persisted
    }

.. _annref_postremove:

@PostRemove
~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postRemove``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PostRemove
     */
    public function doSomethingOnPostRemove()
    {
      // ... do something after the document has been removed
    }

.. _annref_postupdate:

@PostUpdate
~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``postUpdate``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PostUpdate
     */
    public function doSomethingOnPostUpdate()
    {
      // ... do something after the document has been updated
    }

.. _annref_prepersist:

@PrePersist
~~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``prePersist``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PrePersist
     */
    public function doSomethingOnPrePersist()
    {
      // ... do something before the document has been persisted
    }

.. _annref_preremove:

@PreRemove
~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``preRemove``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PreRemove
     */
    public function doSomethingOnPreRemove()
    {
      // ... do something before the document has been removed
    }

.. _annref_preupdate:

@PreUpdate
~~~~~~~~~~

Life cycle callback. The marked method will be called automatically on the ``preUpdate``
event. See :ref:`lifecycle callbacks <events_lifecyclecallbacks>` for further explanations::

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\PreUpdate
     */
    public function doSomethingOnPreUpdate()
    {
      // ... do something before the document has been updated
    }

PHPCR
-----

.. _annref_node:

@Node
~~~~~

The annotated instance variable will be populated with the underlying
PHPCR node. See :ref:`node field mapping <phpcraccess_nodefieldmapping>`.

References
----------

.. _annref_referencemany:

@ReferenceMany
~~~~~~~~~~~~~~

Optional attributes:

-  **targetDocument**: Specify type of target document class. Note that this
   is an optional parameter and by default you can associate *any* document.
-  **strategy**: One of ``weak``, ``hard`` or ``path``. See :ref:`reference other documents <associationmapping_referenceotherdocuments>`.

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\ReferenceMany(targetDocument="Phonenumber", strategy="hard")
    */
    protected $phonenumbers;

.. _annref_referenceone:
.. _annref_reference:

@ReferenceOne
~~~~~~~~~~~~~

Optional attributes:

-  **targetDocument**: Specify type of target document class. Note that this
   is an optional parameter and by default you can associate *any* document.
-  **strategy**: One of `weak`, `hard` or `path`. See :ref:`reference other documents <associationmapping_referenceotherdocuments>`.
- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\ReferenceOne(targetDocument="Contact", strategy="hard")
    */
    protected $contact;

.. _annref_referrers:

@Referrers
~~~~~~~~~~

Mark the annotated instance variable to contain a collection of the documents
of the given document class which refer to this document.

Required attributes:

- **referringDocument**: Full class name of referring document, the instances
  of which should be collected in the annotated property.
- **referencedBy**: Name of the property from the referring document class
  which refers to this document class.

Optional attributes:

- **cascade**: |cascade_definition| See :ref:`assocmap_cascading`

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\Referrers(referringDocument="Address", referencedBy="addressbook")
    */
   protected $addresses;

@MixedReferrers
~~~~~~~~~~~~~~~

Mark the annotated instance variable to hold a collection of *all* documents
which refer to this document, regardless of document class.

Optional attributes:

-  **referenceType**: One of ``weak`` or ``hard``.

.. code-block:: php

   use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

   /**
    * @PHPCR\MixedReferrers()
    */
   protected $referrers;

Translation
-----------

These annotations only apply to documents where the ``translator`` attribute is
specified in :ref:`@Document<annref_document>`.

Example::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document(translator="attribute")
     */
    class MyDocument
    {
       /**
        * @PHPCR\Locale
        */
       protected $locale;

       /**
        * @PHPCR\Field(type="string", translated=true)
        */
       protected $title;
    }

.. _annref_locale:

@Locale
~~~~~~~

Identifies the annotated instance variable as the field in which to store
the documents current locale.

Versioning
----------

These annotations only apply to documents where the ``versionable`` attribute is
specified in :ref:`@Document<annref_document>`.

See :ref:`versioning mappings <versioning_mappings>`.

Example::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document(versionable="simple")
     */
    class MyPersistentClass
    {
        /**
         * @PHPCR\VersionName
         */
        private $versionName;

        /**
         * @PHPCR\VersionCreated
         */
        private $versionCreated;
    }

.. _annref_versioncreated:

@VersionCreated
~~~~~~~~~~~~~~~

The annotated instance variable will be populated with the date
that the current document version was created. Applies only to
documents with the versionable attribute.

.. _annref_versionname:

@VersionName
~~~~~~~~~~~~

The annotated instance variable will be populated with the name
of the current version as given by PHPCR.

.. |cascade_definition| replace:: One of ``persist``, ``remove``, ``merge``, ``detach``, ``refresh``, ``translation`` or ``all``.
