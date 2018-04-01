XML Mapping
===========

This chapter gives a brief overview of the XML mapping by example. In general,
the attributes correspond to their annotation counterparts with the difference that
the attribute names are slugified as opposed to being camelCase
(``referring-document`` instead of ``referringDocument``). See :doc:`annotations-mapping`.

The following example implements all of the possible XML mapping elements:
    
.. code-block:: xml

    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://raw.github.com/doctrine/doctrine2/master/doctrine-mapping.xsd">

        <document name="Acme\Document\Example" referenceable="true" translator="attribute" versionable="simple">
            <!-- Identification -->
            <uuid name="uuid" />
            <id name="path" />
            <nodename name="name" />

            <!-- Hierarchy -->
            <parent-document name="parent" />
            <children name="children" />
            <child name="block" node-name="block" />
            <depth name="depth" />

            <!-- PHPCR -->
            <node name="phpcrNode" />

            <!-- Translation -->
            <locale name="locale" />

            <!-- Field mappings -->
            <field name="title" type="string" translated="true" />
            <field name="resourceLocator" property="resource-locator" type="string" translated="true" />
            <field name="creator" type="long" translated="true" nullable="true" />

            <!-- References -->
            <reference-one name="anyDocumentReference"/>
            <reference-one name="user" target-document="Acme\Document\User"/>
            <reference-many name="articles" target-document="Acme\Document\Article"/>
            <referrers name="tags" referring-document="Acme\Document\Tag" />
            <mixed-referrers name="allReferrers" />

            <!-- Versioning -->
            <version-name name="versionName" />
            <version-created name="versionCreated" />

        </document>

    </document>

Mapped super-classes can be mapped as follows:

.. code-block:: xml

    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://raw.github.com/doctrine/doctrine2/master/doctrine-mapping.xsd">

        <mapped-superclass name="Acme\Document\Example" referenceable="true" translator="attribute" versionable="simple">
            <!-- ... -->
        </mapped-superclass>

    </doctrine-mapping>
