Tools
=====

Doctrine Console
----------------

The Doctrine Console is a Command Line Interface tool for
simplifying common tasks during the development of a project that
uses Doctrine PHPCR-ODM. It is built on the `Symfony Console Component`_

If you have not set up the console yet, take a look at the
:ref:`Console Setup Section <installation_configuration_console>`.

Command Overview
~~~~~~~~~~~~~~~~

There are many commands, for example to import and export data, modify data in
the repository, query or dump data from the repository or work with PHPCR
workspaces.

Run the console without any arguments to see a list of all commands. The
commands are self documenting. See the next section how to get help.

.. Note::

    PHPCR-ODM specific commands start with ``doctrine:``. The commands that
    start with only ``phpcr:`` come from the phpcr-utils and are not specific
    to Doctrine PHPCR-ODM.

    If you use the PHPCR-ODM bundle in Symfony2, all commands will be prefixed
    with ``doctrine:phpcr``.

Getting documentation of a command
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Type ``./vendor/bin/phpcrodm`` on the command line and you should see an
overview of the available commands or use the --help flag to get
information on the available commands. If you want to know more
about the use of the register command for example, call:

.. code-block:: bash

    ./vendor/bin/phpcrodm help doctrine:phpcr:register-system-node-types

PHPCR implementation specific commands
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Jackrabbit specific commands
""""""""""""""""""""""""""""

If you are using jackalope-jackrabbit, you also have a command to start and stop the
jackrabbit server:

-  ``jackalope:run:jackrabbit``  Start and stop the Jackrabbit server


Doctrine DBAL specific commands
"""""""""""""""""""""""""""""""

If you are using jackalope-doctrine-dbal, you have a command to initialize the
database:

- ``jackalope:init:dbal``   Prepare the database for Jackalope Doctrine DBAL


Register system node types
--------------------------

This command needs to be run once on a new repository to prepare it for use with the PHPCR-ODM.
Failing to do so will throw you errors when you try to store a document that uses a node type
different from nt:unstructured, like a file or folder.

..
    TODO: would be nice to provide this as well

    Convert Mapping Information
    ---------------------------

    Convert mapping information between supported formats.

    This is an **execute one-time** command. It should not be necessary for
    you to call this method multiple times, especially when using the ``--from-database``
    flag.

    Converting an existing database schema into mapping files only solves about 70-80%
    of the necessary mapping information. Additionally the detection from an existing
    database cannot detect inverse associations, inheritance types,
    entities with foreign keys as primary keys and many of the
    semantical operations on associations such as cascade.

    .. note::

        There is no need to convert YAML or XML mapping files to annotations
        every time you make changes. All mapping drivers are first class citizens
        in Doctrine 2 and can be used as runtime mapping for the ORM. See the
        docs on XML and YAML Mapping for an example how to register this metadata
        drivers as primary mapping source.

    To convert some mapping information between the various supported
    formats you can use the ``ClassMetadataExporter`` to get exporter
    instances for the different formats::

        $cme = new \Doctrine\ORM\Tools\Export\ClassMetadataExporter();

    Once you have a instance you can use it to get an exporter. For
    example, the yml exporter::

        $exporter = $cme->getExporter('yml', '/path/to/export/yml');

    Now you can export some ``ClassMetadata`` instances::

        $classes = array(
          $em->getClassMetadata('Entities\User'),
          $em->getClassMetadata('Entities\Profile')
        );
        $exporter->setMetadata($classes);
        $exporter->export();

    This functionality is also available from the command line to
    convert your loaded mapping information to another format. The
    ``orm:convert-mapping`` command accepts two arguments, the type to
    convert to and the path to generate it:

    .. code-block:: bash

        $ php doctrine orm:convert-mapping xml /path/to/mapping-path-converted-to-xml


Adding your own commands
------------------------

You can also add your own commands on-top of the Doctrine supported
tools by adding them to your binary.

To include a new command in the console, either build your own console file
or copy ``bin/phpcrodm.php`` into your project and add things as needed.

Read more on the `Symfony Console Component`_ in the official symfony
documentation.

.. _`Symfony Console Component`: http://symfony.com/doc/current/components/console/index.html
