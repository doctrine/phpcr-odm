Architecture
============

The architecture of the PHPCR-ODM is similar to that of Doctrine 2 ORM. Please read
the ORM architecture chapter (TODO: link) to get a basic understanding of the Doctrine
architecture. We will focus on some notable differences here.

Doctrine PHPCR-ODM Packages
---------------------------

PHPCR-ODM is built on top of Doctrine Common. However, it does not need a custom database
abstraction layer (DBAL). The PHPCR standard already is an implementation independent.
PHPCR-ODM does not rely on implementation specific features of the content repository but
fully operate through the standard API.

The stack thus looks like this

-  PHPCR (and an implementation like Jackalope or Midgard2 - like you need pdo_mysql or
   similar with DBAL)
-  Common
-  PHPCR-ODM

This manual mainly covers the PHPCR-ODM package, sometimes touching parts
of the underlying PHPCR and Common packages.


Content tree, not tables
~~~~~~~~~~~~~~~~~~~~~~~~

The main difference between a relational database and the PHP content repository is
that in PHPCR, content is stored in one tree, not split over tables. You can think of
this like a file system, or of an XML document with elements and attributes.

As long as you do not use special node types, documents can be put anywhere into the tree,
regardless of the document class, like in a NoSQL database. The difference is that the
tree must be complete, meaning you always need a parent to add child documents to. This
is bootstrapped by adding documents to the root document.

If you are not yet familiar with PHPCR, you can just go on with PHPCR-ODM, but you will
get better understanding when you also take a look at the  `PHPCR documentation <http://phpcr.github.com>`_.


Additional core features
~~~~~~~~~~~~~~~~~~~~~~~~

As PHPCR provides native support for versioning, PHPCR-ODM also exposes this feature.
See the advanced chapter :doc:`Versioning <versioning>` for information about this.

Additionally, we implemented :doc:`Multilanguage  <multilang>` support into PHPCR-ODM.


TODO: any other differences compared to ORM?