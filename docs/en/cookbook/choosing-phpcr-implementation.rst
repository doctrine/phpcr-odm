.. index::
    single: PHPCR Implementations

Choosing a PHPCR Implementation
===============================

To use PHPCR-ODM, you need to decide on the PHPCR implementation you want to
use. The implementation is a special kind of database that is responsible for
storing all the content you want to persist. While every content repository
can have very different requirements and performance characteristics, the API
is the same for all of them.

Furthermore, since the API defines an export/import format, you can always
switch to a different content repository implementation later on.

.. tip::

    If you are just getting started with the CMF, it is best to choose a
    content repository based on a storage engine that you are already familiar
    with. For example, **Jackalope with Doctrine DBAL** will work with your
    existing RDBMS and does not require you to install Java Once you have a
    working application it should be easy to switch to another option.

Jackalope with Jackrabbit
-------------------------

The most feature complete and performant implementation available today.
Jackrabbit can persist into the file system, a database and other storage
layers.

The main drawback is that Jackrabbit requires the Java runtime.

See :doc:`running-jackrabbit` for instructions how to install and run jackrabbit.

Jackalope with Doctrine DBAL
----------------------------

A solid and tested implementation that is fine for small to medium sized
projects. It can run on just a relational database (currently tested with
MySQL, PostgreSQL and SQLite).
