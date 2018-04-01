Doctrine PHP Content Repository ODM documentation
=================================================

The Doctrine PHPCR ODM documentation is comprised of tutorials, a reference section and
cookbook articles that explain different parts of the PHP Content Repository Object Document mapper.

Getting Help
------------

If this documentation is not helping to answer questions you have about
Doctrine PHPCR ODM don't panic. You can get help from different sources:

-  The `Doctrine Mailing List <http://groups.google.com/group/doctrine-user>`_
-  Internet Relay Chat (IRC) in `#doctrine on Freenode <irc://irc.freenode.net/doctrine>`_
-  Report a bug on `JIRA <http://www.doctrine-project.org/jira>`_.

Getting Started
---------------

* **Introduction**:
  :doc:`Introduction by code <reference/introduction>` |
  :doc:`Architecture <reference/architecture>`

* **Setup**:
  :doc:`Installation and configuration <reference/installation-configuration>` |
  :doc:`Tools <reference/tools>`

Mapping Objects onto a Document Repository
------------------------------------------

* **Basic Reference**:
  :doc:`Objects and Fields <reference/basic-mapping>` |
  :doc:`Hierarchies and References <reference/association-mapping>` |
  :doc:`Inheritance <reference/inheritance-mapping>`

* **Mapping Driver References**:
  :doc:`Docblock Annotations <reference/annotations-mapping>` |
  :doc:`XML <reference/xml-mapping>` |
  :doc:`YAML <reference/yml-mapping>` |
  :doc:`Metadata Drivers <reference/metadata-drivers>`

Working with Objects
--------------------

* **Basic Reference**:
  :doc:`Documents <reference/working-with-objects>` |
  :doc:`Events <reference/events>`

* **Query Reference**:
  :doc:`Query Builder Guide <reference/query-builder>` |
  :doc:`Query Builder Reference <reference/query-builder-reference>` |
  :doc:`The Query Object <reference/query>`

Advanced Topics
---------------

* **PHPCR Session**:
  :doc:`Accessing the underlying PHPCR session <reference/phpcr-access>`

* **Multilanguage**:
  :doc:`Working with Multilanguage Documents <reference/multilang>`

* **Versioning**:
  :doc:`Versioning Documents <reference/versioning>`

* **Transactions**:
  :doc:`Transactions <reference/transactions>`

* **Performance**:
  :doc:`Fetch Depth <reference/fetch-depth>`

.. TODO? * **Logging**: :doc:`Logging <reference/logging>`

Cookbook
--------

* **Tricks**:
  :doc:`Last modification timestamp <cookbook/last-modified>` |
  :doc:`Custom Document Class Mapper <cookbook/custom_documentclass_mapper>` |
  :doc:`Convert documents between translated and untranslated <cookbook/refactoring-multilang>`

.. TODO: write these
  |
  :doc:`Blending ORM and PHPCR-ODM <cookbook/blending-orm-and-phpcr-odm>` |
  :doc:`Mapping classes to ORM and PHPCR-ODM <cookbook/mapping-classes-to-orm-and-phpcr-odm>` |

Also have a look at the `Doctrine ORM cookbook <http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/#cookbook>`_,
notably the entries in the *Implementation* section apply to PHPCR-ODM as well.
