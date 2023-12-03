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

    If you use the PHPCR-ODM bundle in Symfony, all commands will be prefixed
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

Adding your own commands
------------------------

You can also add your own commands on-top of the Doctrine supported
tools by adding them to your binary.

To include a new command in the console, either build your own console file
or copy ``bin/phpcrodm.php`` into your project and add things as needed.

Read more on the `Symfony Console Component`_ in the official symfony
documentation.

.. _`Symfony Console Component`: http://symfony.com/doc/current/components/console/index.html
