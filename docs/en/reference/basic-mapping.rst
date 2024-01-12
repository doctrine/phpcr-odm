Basic Mapping
=============

This chapter explains the basic mapping of objects and properties.
Mapping of hierarchy associations and arbitrary references will be covered in the next chapter.

Mapping Drivers
---------------

Doctrine provides several different ways for specifying
object-document mapping metadata:

- PHP Attributes
- XML
- YAML

This manual usually mentions PHP attributes in all the examples
that are spread throughout all chapters, however for many examples
alternative YAML and XML examples are given as well. There are dedicated
reference chapters for XML and YAML mapping, respectively that explain them
in more detail. There is also a PHP Attributes reference chapter.

.. note::

    If you're wondering which mapping driver gives the best
    performance, the answer is: They all give exactly the same performance.
    Once the metadata of a class has
    been read from the source (attributes, xml or yaml) it is stored
    in an instance of the ``Doctrine\ODM\PHPCR\Mapping\ClassMetadata`` class
    and these instances are stored in the metadata cache. Therefore at
    the end of the day all drivers perform equally well. If you're not
    using a metadata cache (not recommended!) then the XML driver might
    have a slight edge in performance due to the powerful native XML
    support in PHP.


Introduction to PHP Attributes
------------------------------

PHP attributes are an official language replacement for the informal
docblock annotations. They allow to embed metadata next to the code.

The Doctrine PHPCR-ODM defines its own set of attributes to supply
object-document mapping metadata.

Persistent classes
------------------

In order to mark a class for object-document persistence it needs
to be designated as an document. This can be done through the
``#[Document]`` marker attribute.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Document]
        class MyPersistentClass
        {
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <!-- ... -->
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            # ...

There is a couple of parameters you can specify for the document mapping.
Some of them are explained here, the rest in the chapters on :ref:`References <association-mapping_referenceable>`,
:doc:`Multilanguage <multilang>` and :doc:`Versioning <versioning>`.


Specify a node type
~~~~~~~~~~~~~~~~~~~

The ``nodeType`` attribute allows to specify a PHPCR node type to use for this document,
instead of the default permissive nt:unstructured.

Specify a repository class
~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``repositoryClass`` attribute allows to specify a custom repository instead of the default
repository implementation.

Doctrine Mapping Types
----------------------

A Doctrine Mapping Type defines the mapping between a PHP type and
a PHPCR property type. PHPCR defines a finite set of allowed types for properties.

For example, the Doctrine Mapping Type ``string`` defines the
mapping from a PHP string to a ``PHPCR\\PropertyType::STRING``.
Here is a quick overview of the built-in mapping types:

See `PHPCR\\PropertyType <http://phpcr.github.io/doc/html/files/phpcr.src.PHPCR.PropertyType.html>`_ for details about the types.

- ``String``: Arbitrary length strings
- ``Binary``: Binary stream using PHP streams
- ``Long``: Integer number (alias Int for convenience), limited by PHP_MAX_INT
- ``Decimal``: Arbitrary length number value (PHP string type for use with ``bcmath``)
- ``Double``: Floating point number (alias Float for convenience)
- ``Date``: \DateTime object
- ``Boolean``: Boolean value
- ``Name``: A valid PHPCR name
- ``Path``: A valid PHPCR path
- ``Uri``: A valid URI, for example a URL

Each document can have a unique identifier for referencing it. While the uuid is
also exposed as a read-only string property, the proper mapping for it is mapping
it as UUID. See :ref:`References <association-mapping_referenceable>` for more
information.

.. note::

    DateTime types are compared by reference, not by value. Doctrine updates these values
    if the reference changes and therefore behaves as if these objects are immutable value objects.

.. warning::

    All Date types assume that you are exclusively using the default timezone
    set by `date_default_timezone_set() <http://docs.php.net/manual/en/function.date-default-timezone-set.php>`_
    or by the php.ini configuration ``date.timezone``. Working with
    different timezones will cause troubles and unexpected behavior.

    If you need specific timezone handling you have to handle this
    in your domain, converting all the values back and forth from UTC.
    There is also a `cookbook entry in the ORM documentation <http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/working-with-datetime.html>`_
    on working with datetimes that gives hints for implementing
    multi timezone applications.


Property Mapping
----------------

After a class has been marked as a document it can specify mappings
for its instance fields. Properties are only simple fields
that hold scalar values like strings, numbers, etc, or arrays thereof.
Although references are also stored as properties in PHPCR, they have
their own mappings - see the chapter "Association Mapping".

To mark a property for relational persistence the ``#[Field]`` attribute
is used. This attribute requires at least the ``type`` parameter to be set.
The ``type`` parameter specifies the Doctrine Mapping Type to use for the
field. If the type is not specified, PHPCR-ODM will try to let the PHPCR
implementation determine a suitable type.

Example:

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Document]
        class MyPersistentClass
        {
            #[PHPCR\Field(type: 'long')]
            private int $count;

            #[PHPCR\Field(type: 'string')]
            private string $name; // type defaults to string
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <field fieldName="count" type="long" />
                <field fieldName="name" type="string" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            fields:
                count:
                    type: long
                name:
                    type: string

In that example we mapped the field ``count`` to the property ``count``
using the mapping type ``long`` and the field ``name`` is mapped
to the property ``name`` with the mapping type ``string``. As
you can see, by default the column names are assumed to be the same
as the field names.

Mapping to a differently named PHPCR property
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To specify a different name for the column, you can use the ``property``
parameter of the Column attribute follows:

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Field(property: 'db_name'"')]
        private string $myField;

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <field fieldName="myField" property="db_name" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            type: document
            fields:
                myField:
                    property: db_name


.. _basicmapping_mappingmultivalueproperties:

Mapping multivalue properties
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

PHPCR handles multivalue (array) data natively. The PHPCR-ODM exposes this feature through the
``multivalue`` attribute of properties and adds support for hashmaps (storing the keys as well).
Unless specified as true, properties are considered single value.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Field(type: 'string', multivalue: true)]
        private array $names;

    .. code-block:: xml

        <doctrine-mapping>
          <document name="MyPersistentClass">
            <field fieldName="names" multivalue="true" />
          </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            type: document
            fields:
                names:
                    multivalue: true

This mapping expects the field $names to contain an array of strings. When reading from the database,
a multivalue property is expected and the field will be set to the array of strings.

The multivalue mapping will lose the keys of the array. To store hashmaps with keys, use the assoc
attribute. This attribute implies multivalue so you don't need to repeat multivalue=true. The following
configuration will result in the PHPCR property namesKeys for the names array and listArraykeys for
the list keys.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Field(type: 'string', assoc: '')]
        private array $names;

        #[PHPCR\Field(type: 'string', assoc: 'listArraykeys')]
        private array $list;

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <field fieldName="names" assoc="" />
                <field fieldName="list" assoc="listArraykeys" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            type: document
            fields:
                names:
                    assoc: ""
                list:
                    assoc: "listArraykeys"

Summary
~~~~~~~

These are all parameters of the property mapping. The ORM knows quite a few validation parameters
because they are used to generate the database schema. As PHPCR-ODM does not (yet) generate PHPCR
node type definitions, there is no need for validation.

If you need to validate your documents, take a look at validator components like the Symfony validator.

Again a short list for the overview:


-  ``type``: (optional, autodetected if not specified) The mapping type to
   use for the property.
-  ``name``: (optional, defaults to field name) The name of the
   property in the repository.
-  ``multivalue``: (optional, defaults to false) If this is set to true, the
   property is an array of the specified type.
-  ``assoc``: (optional, defaults to false) If set to a string, the value is
   considered multivalue and the keys are stored in the PHPCR property given
   for the assoc property. If the value of assoc is empty, the name for the
   key field is the normal field name with ``Keys`` appended.

.. _basicmapping_identifiers:

Identifiers
-----------

Every document has an identifier. The id in PHPCR-ODM is the PHPCR path.

.. note::

    The id being the path, it is not totally immutable. When the document is
    moved either explicitly with ``DocumentManager::move()`` or by assignment
    of a different ``#[Field(type: 'name')]`` or ``#[ParentDocument]``, the id
    will change. This was discussed thoroughly and is considered the best solution.

    If you need to reference a document reliably even when moving, look at the
    ``#[ReferenceOne]`` and the ``#[Uuid]`` attributes explained in the
    :doc:`next chapter <attributes-mapping>`.

While you can manually assign the id, this is not recommended. When manually
assigning, you need to ensure that the parent document defined in the assigned
path exists. The recommended way is to use the ``#[ParentDocument]`` and
``#[Nodename]`` attributes to place the document in the tree. When using that
strategy, you need not have a property with the ``#[Id]`` attribute - though if
you need access to the path for something, you can also map the id.

.. _basicmapping_identifier_generation_strategies:

Identifier Generation Strategies
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Every document needs a unique id. PHPCR-ODM provides a couple of id strategies.
You can specify one of them explicitly on the id mapping, or let the PHPCR-ODM
pick a fitting one. The order is:

- Explicitly specified strategy on the ``id`` mapping, for example
  ``#[PHPCR\Id(strategy: 'repository')]``
- If the document has a ``#[ParentDocument]`` and a ``#[Nodename]`` field, the
  ``parent`` is used to determine the id from this information. This
  is the most failsave strategy as it will ensure that there is a PHPCR parent
  existing for the document;
- If only an ``#[ParentDocument]`` field is present, the ``auto`` takes
  the path from the ``#[ParentDocument]`` as the parent id generator does, but
  generates the node name automatically using the PHPCR ``addNodeAutoNamed``
  method;
- If there is only an id field, the ``assigned`` is used. It expects
  you to assign the repository path to the id field. You will have to make sure
  yourself that the parent exists.

Another strategy that is never chosen automatically but that you can assign
explicitly is the ``RepositoryIdGenerator``. For this you need to configure a
custom repository implementing ``RepositoryIdInterface``. This way you can
implement any logic you might need.

Parent and name strategy (recommended)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This strategy uses the ``#[Nodename]`` (name of this node) and
``#[ParentDocument]`` (PHPCR-ODM document that is the parent). The id is generated
as the id of the parent concatenated with '/' and the Nodename.

If you supply a ParentDocument attribute, the strategy is automatically set to
parent. This strategy will check the parent and the name and will fall back to
the assigned id if either is missing.


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\ParentDocument]
        private object $parent;

        #[PHPCR\Nodename]
        private string $nodename;

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <parentdocument name="parent" />
                <nodename name="nodename" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            parentdocument: parent
            nodename: nodename


To create a new document, you do something like this::

    $doc = new Document();
    $doc->setParent($dm->find(null, '/test'));
    $doc->setNodename('mynode');
    // document is persisted with id /test/mynode

Assigned Id
^^^^^^^^^^^

This is the default but very unsafe strategy. You need to manually assign the
path to the id field.
A document is not allowed to have no parent, so you need to make sure that the
parent of that path already exists. (It can be a plain PHPCR node not
representing any PHPCR-ODM document, though.)


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Id]
        private string $id;

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <id name="id" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            id: ~


To create a new document, you do something like this::

    $doc = new Document();
    $doc->setId('/test/mynode');
    // document is persisted with id /test/mynode


Repository strategy
^^^^^^^^^^^^^^^^^^^

If you need custom logic to determine the id, you can explicitly set the
strategy to "repository". You need to define the repositoryClass in your Document mapping which will
handle the task of generating the id from the information in the document.
This gives you full control how you want to build the id path.


.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

        #[PHPCR\Id(strategy: 'repository')]
        private string $id;

    .. code-block:: xml

        <doctrine-mapping>
            <document name="MyPersistentClass">
                <id name="id" type="id">
                    <generator strategy="repository" />
                </id>
            </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
            id:
                generator:
                    strategy: repository

The document code could look like this::

    namespace Demo;

    use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

    #[PHPCR\Document(repositoryClass: DocumentRepository::class)]
    class Document
    {
        #[PHPCR\Id(strategy: 'repository')]
        private string $id;

        #[PHPCR\Field(type: 'string')]
        private string $title;
        //...
    }

And the corresponding repository like this::

    namespace Demo;

    use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
    use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;

    class DocumentRepository extends BaseDocumentRepository implements RepositoryIdInterface
    {
        public function generateId(Document $document, object $parent = null): string
        {
            return '/functional/'.$document->getTitle();
        }
    }

Symfony bundle
---------------

If you are using the `Symfony DoctrinePHPCRBundle <https://github.com/doctrine/DoctrinePHPCRBundle>`_, you can use the ``ValidPhpcrOdm`` validator to validate your documents.
