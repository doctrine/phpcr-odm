Refactoring Multilanguage Documents
===================================

Documents that are :doc:`multilanguage <../reference/multilang>` store a copy
of their translated fields for each locale. When you refactor documents and
to change fields to become translated or no longer translated, the data in PHPCR
needs to be migrated.

A command line tool and a class are provided to help with this task.

.. versionadded:: 1.3
    The command and helper class where introduced in PHPCR-ODM 1.3.

Procedure
---------

.. warning::

    As always with data migrations, create a backup before you attempt to
    migrate the live data. If there is a bug or you make a mistake, data
    could be destroyed otherwise.

The first step is to update the translation metadata (add or remove the
translated attribute of a field mapping, or change the translation strategy
declaration on the class mapping). The conversion tool converts to the current
state. Sometimes it can guess the previous state, sometimes you will need to
provide that information.

Once this is done, use the command line
tool ``doctrine:phpcr:document:convert-translation``. If you need additional
logic, you can also write your own PHP code that instantiates
``Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter`` and calls its
``convert`` method. If you do the later, make sure to read the phpdoc on the
``convert`` method.

Converting Untranslated Fields to be Translated
-----------------------------------------------

First, add the ``translated`` mapping attribute for the fields that should
become translated. If the document had no translated fields previously, you
also need to define a translator strategy on the class mapping, as explained
in :doc:`multilanguage support <../reference/multilang>`.

Now you can run the command to make fields translated. It will copy the
current untranslated value of the fields into all locales specified in the
``--locales`` option. From this, editors can adjust translations in specific
languages.

Assuming you have a ``Acme\Document\Article`` with the fields ``title`` and
``body`` that was previously not translated at all, the command looks as
follows:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:document:convert-translation "Acme\\Document\\Article" --locales=en

If a document is already translated but new fields become translated, you can
limit which fields to convert using the ``fields`` option to the command.

To continue with the previous example, lets assume you had a field
``furtherInfo`` that you did not translate but now realize needs being
translated as well. Add the translated attribute and then run:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:document:convert-translation "Acme\\Document\\Article" --locales=en --fields=furtherInfo

Removing Translation from a Field
---------------------------------

To remove translation from a field, adjust the field mapping to not provide the
translated attribute anymore. As long as the class mapping specifies the
translation strategy, you can just specify what field to update. Assume you
have a field ``furtherInfo`` that you changed from translated to untranslated:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:document:convert-translation "Acme\\Document\\Article" --fields=furtherInfo

The command will request the translation of the field in the current locale,
with fallback order as configured.

If your whole document should no longer be translated, you can remove the
document translator configuration and the translated attribute from all fields.
You then need to specify all fields that need to be converted back, as well as
the translation strategy that was used:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:document:convert-translation "Acme\\Document\\Article" --fields=furtherInfo,title,body --previous-strategy=attribute

Changing the Translation Strategy
---------------------------------

To convert from one translation strategy to another, update the document to
specify the new strategy, and pass the old strategy on the command line:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:document:convert-translation "Acme\\Document\\Article" --locales=en --previous-strategy=attribute
