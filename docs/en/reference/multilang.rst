Multilanguage support
=====================

PHPCR-ODM has multilanguage support built in. This is an additional feature not supported by
PHPCR, but modeled on top of it.


Philosophy
----------

You can mark any properties as being translatable and have the document manager store and load
the correct language for you. Note that translation always happens on a document level, not on
the individual translatable fields. The non-translated fields however are not duplicated to
avoid redundancy.

The multilanguage support is built in a way to allow you to not explicitly handle language.
You can tell the DocumentManager the current language and it will be used as default language
when calling ``find`` methods and when persisting new documents.

When a document is read, its current language is stored with the document, to make sure changes go
to the correct language.

Because every document may only exist once, and translations are considered the same document, you can not have multiple languages loaded at the same time.

For required fields (fields not having nullable=true), the behaviour is the
same as with normal fields: On saving, an error is thrown when a required field
is null. On loading, missing fields trigger no error. A translation exists as
soon as at least one field in that locale exists.

.. _multilang_mapping:

Mapping
-------

To make a document translated, you need to define the ``translator`` attribute on the document
configuration, and you need to map the ``locale`` field. Then you can use the ``translated``
attribute on all fields that should be different depending on the locale.

.. configuration-block::

    .. code-block:: php

        use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

        /**
         * @PHPCR\Document(translator="attribute")
         */
        class MyPersistentClass
        {
            /**
             * The language this document currently is in
             * @Locale
             */
            private $locale;

            /**
             * Untranslated property
             * @PHPCR\Field(type="date")
             */
            private $publishDate;

            /**
             * Translated property
             * @PHPCR\Field(type="string", translated=true)
             */
            private $topic;

            /**
             * Language specific image
             * @PHPCR\Field(type="binary", translated=true)
             */
            private $image;
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document class="MyPersistentClass" translator="attribute">
                <locale fieldName="locale" />
                <field fieldName="publishDate" type="date" />
                <field fieldName="topic" type="string" translated="true" />
                <field fieldName="image" type="binary" translated="true" />
            </document>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          translator: attribute
          locale: locale
          fields:
            publishDate:
                type: date
            topic:
                type: string
                translated: true
            image:
                type: binary
                translated: true

The translation strategy is telling the document manager which strategy to use to store and load
translations for this document. The ``Locale`` field holds the current locale of the document.
It is populated when finding the document, updated whenever you call bindTranslation and also
taken into account when you flush the document, to save the correct translation.
When you manually change the Locale after loading a document, it will be saved as the newly assigned language.

You can set any type of property as translatable, but should only set those that are actually language
specific. All other properties should not have that annotation, then they are the same in all languages.
However, you can not set any association annotations to translatable and translations will not propagate
through associations (see the section "Limitations" for an explanation).

Having at least one property marked as translatable will require the whole document to
have a translator strategy and a Locale field.

.. note::

    You need to be careful when refactoring documents that have existing data.
    When you change fields to be translated or no longer translated, or change
    the translation strategy, you need to migrate the data.

    See :doc:`../cookbook/refactoring-multilang` for more information on the
    tools to do the data migration.

Interacting with translations
-----------------------------

When reading, ``DocumentManager::find()`` uses the default locale (see below how to set that). This means
your reading code does not need to be aware of content translations happening.

If you need to access a document with an explicit locale that might be different from the default locale,
you can use ``DocumentManager::findTranslation()``.

.. warning::

    When loading a document with findTranslation that was already loaded with this DocumentManager session,
    the DocumentManager will not create a copy of the document but change the fields of the existing document.
    This means you can not have two languages of the same document in memory at the same time.

    The reason for this is that otherwise we could run into inconsistencies if any of the non-translatable
    fields is changed in one of the two document instances that are the same document.


To get a list of all available locales for a document, use ``DocumentManager::getLocalesFor``.

When writing, you can use ``DocumentManager::persist()`` as normal. Persist will respect the locale
set in the Locale field, and fall back to the default locale if that field is empty.

During ``DocumentManager::flush()``, if you edited a document, the current value of the Locale
field is respected as well. If you want to flush more than one language in one go, you can use
``DocumentManager::bindTranslation()`` repeatedly and update the translated fields of your document
before each call to bindTranslation. (See the example below).


Choosing the right translation strategy
---------------------------------------

A translation strategy needs to be selected by adding the ``translator`` parameter to the document mapping.
The translation strategy is responsible to actually persist the translated properties.

There are two default translation strategies implemented and automatically available:

* ``attribute`` stores the translations in attributes of the node containing the translatable properties
* ``child`` stores the translations in a child node of the node containing the translatable properties

Thus, if you do not have many fields, the attribute strategy puts less load on the content repository.
On the other hand, if you have a lot of fields on your document, you may want to use the child strategy.

If needed, it is possible to implement other strategies to persist the translations.

Implementing your own translation strategy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You may want to implement your own translation strategy to persist the translatable properties of a node.
For example if you want all the translations to be stored in a separate subtree of you content repository.

To do so you need to implement the ``Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface``.

Then you have to register your translation strategy with the document manager during the bootstrap::

    use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;

    class MyTranslationStrategy implements TranslationStrategyInterface
    {
        // ...
    }

    $dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
    $dm->setTranslationStrategy('my_strategy_name', new MyTranslationStrategy());

``my_strategy_name`` would be the value for the translator attribute to use your custom strategy.


.. _multilang_chooser:

Configure the locale chooser strategy
-------------------------------------

The language chooser is used when loading translated documents. If no language is specified,
it provides the default language. If the requested language is not available for this document,
the strategy is asked for a fallback order of other languages to try in order to find the best
available translation.

On reading, PHPCR-ODM tries to find a translation with each of the languages in that
list and throws a not found exception if none of the languages exists.

The default language chooser strategy ``Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser`` returns
a configurable list of languages based on the requested language. On instantiation, you specify
the default locale. This should be your application default locale. It is used to get the default locale order
which usually should not vary based on the current locale.
Based on your HTTP request or whatever criteria you have, you can use setLocale() to have the document manager load
your document in the right language.

When you bootstrap the document manager, you need to set the language chooser strategy if you have
any translatable documents::

    use Doctrine\ODM\PHPCR\DocumentManager;

    $localePrefs = array(
        'en' => array('de', 'fr'),
        'fr' => array('de', 'en'),
        'it' => array('de', 'en'),
    );

    $dm = new DocumentManager($session, $config);
    $dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));

The above says: When ``en`` is requested but you do not find it, then try ``de`` and finally ``fr``.

You can write your own strategy by implementing ``Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface``.
This is useful to determine the default language based on some logic, or provide fallback orders based on user preferences.


Full Example
------------

.. code-block:: php

    use Doctrine\ODM\PHPCR\DocumentManager;

    // bootstrap the DocumentManager as required (see above)

    $localePrefs = array(
        'en' => array('fr'),
        'fr' => array('en'),
    );

    $dm = new DocumentManager($session, $config);
    $dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));

    // then to use translations:

    $doc = new Article();
    $doc->id = '/my_test_node';
    $doc->author = 'John Doe';
    $doc->topic = 'An interesting subject';
    $doc->text = 'Lorem ipsum...';

    // Persist the document in English
    $dm->persist($doc);
    $dm->bindTranslation($doc, 'en');

    // Change the content and persist the document in French
    $doc->topic = 'Un sujet intéressant';
    $dm->bindTranslation($doc, 'fr');

    // locale is updated automatically if there is such an annotation
    echo $doc->locale; // fr

    // Flush to write the changes to the phpcr backend
    $dm->flush();

    // Get the document in default language
    // (English if you bootstrapped as in the example)
    $doc = $dm->find(null, '/my_test_node');

    // Get the document in French
    $doc = $dm->findTranslation(null, '/my_test_node', 'fr');
    $doc->title = 'nouveau';
    $dm->flush(); // french is updated as the language is tracked by the dm


Querying Translated Properties
------------------------------

The translation strategy will store translated strings into specific
properties. When using the PHPCR SQL2 queries, you will need to look
into implementation details to make them work.

When using the PHPCR-ODM query builder, it will detect translated fields
and adjust the query accordingly. By default, the current locale will be
used, but you can manually call ``$qb->setLocale($locale)`` if you need
a different locale.

Read more in the :ref:`query builder documentation <qb-translation>`.


Limitations
-----------


Translations and references / hierarchy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For now, Child, Children, Parent, ReferenceMany, ReferenceOne and Referrers will all fall back to the default language.
The reason for this is that there can be only one tracked instance of a document per session. (Otherwise what should happen
if both copies where modified?...).

For more details, see the `wiki page <https://github.com/doctrine/phpcr-odm/wiki/Multilanguage>`_.
