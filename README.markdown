PHPCR ODM for Doctrine2
=======================

Current Status
--------------

* (very) basic CRUD is implemented
* metadata reading implemented for annotations

Todo
----

* fix the tests that fail
* implement metadata reading for xml/yml/php
* figure out how we can do relations in a sane way
* add metadata "node" to allow injecting the jackalope node object into documents
* implement Sf2 bundle

Notes
-----

* The type of the document is stored in each node (stored as _doctrine_alias for the moment)
