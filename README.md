PHPCR ODM for Doctrine2
=======================

# Current Status

* most key features implemented
* alpha stage
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/PHPCR)
* [![Build Status](https://secure.travis-ci.org/doctrine/phpcr-odm.png)](http://travis-ci.org/doctrine/phpcr-odm)


# TODO

* write documentation [PHPCR-21](http://www.doctrine-project.org/jira/browse/PHPCR-21)
* expand test suite
* translations
    * provide a method to get a detached translated document so the relations can be translated automatically

# Preconditions

* php >= 5.3
* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))
* phpunit >= 3.6 (if you want to run the tests)
* [composer](http://getcomposer.org/)


# Installation

If you use the Doctrine PHPCR ODM **Symfony Bundle**, please look into the
 [Tutorial to install the DoctrinePHPCRBundle](https://github.com/symfony-cmf/symfony-cmf-docs/blob/master/tutorials/installing-configuring-doctrine-phpcr-odm.rst).
This documentation explains how to use PHPCR ODM outside of symfony, which requires some
manual initialization.


## Clone the repository and initialize all dependencies (submodules)

If you do not yet have composer, install it like this

    curl -s http://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin

Install phpcr-odm

    git clone git://github.com/doctrine/phpcr-odm.git
    cd phpcr-odm
    php /usr/local/bin/composer.phar install --dev


## Install a PHPCR provider

PHPCR ODM uses the [PHP Content Repository API](http://phpcr.github.com/) for
storage. You can force one of the available providers in your projects
composer.json file "require" section by specifying one of the "suggest"
libraries in the phpcr-odm composer.json

Each of the providers requires some additional setup.


### Install Jackalope-Jackrabbit PHCPR provider

Jackalope-Jackrabbit uses the Java backend jackrabbit.jar for storage.

Follow [Running Jackrabbit Server](https://github.com/jackalope/jackalope/wiki/Running-a-jackrabbit-server)
from the Jackalope wiki to have the storage backend.


### Install Jackalope Doctrine DBAL PHPCR provider

Jackalope Doctrine DBAL maps PHPCR to relational databases. It supports all
databases that Doctrine DBAL can support.

Create the database as described in the README of
[Jackalope Doctrine DBAL](http://github.com/jackalope/jackalope-doctrine-dbal).


### Install Midgard2 PHPCR provider

[Midgard2](https://github.com/midgardproject/phpcr-midgard2) is a PHP extension
that persists PHPCR into relational databases like SQLite and MySQL.

Midgard2 needs [a PHP extension](https://github.com/midgardproject/midgard-php5)
to run. On typical Linux setups getting the extension is as easy as:

    sudo apt-get install php5-midgard2


## Enable the console

The console provides a bunch of useful commands. Copy the cli-config dist file
with the implementation name of your choice to ``cli-config.php`` and adjust if
needed.

Now running ``php bin/phpcr`` will show you a list of the available commands.
``php bin/phpcr help <cmd>`` displays additional information for that command.


## Register the phpcr:managed node type

PHPCR ODM uses a [custom node type](https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr%3Amanaged)
to track meta information without interfering with your content.
We provide a command that makes it trivial to register this type and the phpcr
namespace.

    php bin/phpcr doctrine:phpcr:register-system-node-types


## Running the tests

This examples shows how to run the tests for jackrabbit. You can run the tests
for the other backends. Just replace jackrabbit with the name of the backend
you want to run.

1. Make sure you have installed the dependencies
2. Run this command to download jackrabbit and launch it (requires wget)

    ./tests/travis_jackrabbit.sh

3. Run the tests:

    phpunit -c tests/phpunit_jackrabbit.xml.dist


# Bootstrapping

## Set up autoloading

Composer provides an autoloader configured for phpcr-odm and all dependencies
in ``vendor/.composer/autoload.php``.


## Define a mapping driver

You can choose between the drivers for annotations, xml and yml configuration files.

### Annotation mappings

```php
<?php
use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader(function($class) use ($autoload) {
    $autoload->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/lib/Doctrine/ODM/PHPCR/Mapping/Annotations/DoctrineAnnotations.php');

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader, array('/path/to/your/document/classes'));
```

### XML mappings
```php
<?php
$driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver(array('/path/to/your/mapping/files'));
```

### YML mappings

This needs the suggested symfony/yaml dependency to be installed

```php
<?php
$driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver(array('/path/to/your/mapping/files'));
```


## Bootstrap the PHPCR session

With the jackalope-jackrabbit provider, the PHPCR ODM connection can be configured with:

```php
<?php
$repository = \Jackalope\RepositoryFactoryJackrabbit::getRepository(
                    array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server'));
$credentials = new \PHPCR\SimpleCredentials('user', 'pass');
$session = $repository->login($credentials, 'your_workspace');
```

With the jackalope-doctrine-dbal provider, set up the connection like this:

```php
<?php
$driver   = 'pdo_mysql';
$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'jackalope';
$workspace  = 'default';

// Bootstrap Doctrine
$dbConn = \Doctrine\DBAL\DriverManager::getConnection(array(
    'driver'    => $driver,
    'host'      => $host,
    'user'      => $user,
    'password'  => $pass,
    'dbname'    => $database,
));

$repository = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository(
    array('jackalope.doctrine_dbal_connection' => $dbConn)
);
// dummy credentials to comply with the API
$credentials = new \PHPCR\SimpleCredentials(null, null);
$session = $repository->login($credentials, $workspace);
```

With Midgard2, the connection configuration (using MySQL as an example) would be something like:

```php
<?php
$repository = \Midgard\PHPCR\RepositoryFactory::getRepository(
    array(
        'midgard2.configuration.db.type' => 'MySQL',
        'midgard2.configuration.db.name' => 'phpcr',
        'midgard2.configuration.db.host' => 'localhost',
        'midgard2.configuration.db.username' => 'midgard',
        'midgard2.configuration.db.password' => 'midgard',
        'midgard2.configuration.blobdir' => '/some/path/for/blobs',
        'midgard2.configuration.db.init' => true
    )
);
$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$session = $repository->login($credentials, 'your_workspace');
```

Note that the `midgard2.configuration.db.init` setting should only be used the
first time you connect to the Midgard2 repository. After that the database is
ready and this setting should be removed for better performance.

## Initialize the DocumentManager

```php
<?php
$config = new \Doctrine\ODM\PHPCR\Configuration();
$config->setMetadataDriverImpl($driver);

$dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
```

Now you are ready to use the PHPCR ODM


# Example usage

```php
<?php
// fetching a document by PHPCR path (id in PHPCR ODM lingo)
$user = $dm->getRepository('Namespace\For\Document\User')->find('/bob');
//or let the odm find the document class for you
$user = $dm->find('/bob');

// create a new document
$newUser = new \Namespace\For\Document\User();
$newUser->username = 'Timmy';
$newUser->email = 'foo@example.com';
$newUser->path = '/timmy';
// make the document manager know this document
// this will create the node in phpcr but not read the fields or commit
// the changes yet.
$dm->persist($newUser);

// store all changes, insertions, etc. with the storage backend
$dm->flush();

// move/rename a document in the tree
$dm->move($newUser, '/tommy');
$dm->flush();

// make sure there is no documents left thinking $newUser at the old path
$dm->clear();

// run a query
$qb = $dm->createQueryBuilder();

// SELECT * FROM nt:unstructured WHERE name NOT IS NULL
$factory = $qb->getQOMFactory();
$qb->select($factory->selector('nt:unstructured'))
    ->where($factory->propertyExistance('name'))
    ->setFirstResult(10)
    ->setMaxResults(10)
    ->execute();
$result = $dm->getDocumentsByQuery($qb->getQuery());
foreach ($result as $document) {
    echo $document->getId();
}

// remove a document - and all documents in paths under that one!
$dm->remove($newUser);
$dm->flush();
```

# Document Classes

You write your own document classes that will be mapped to and from the phpcr
database by doctrine. The documents are usually simple

```php
<?php
namespace Acme\SampleBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class MyDocument
{
    /**
     * @PHPCRODM\Id()
     */
    public $path;
    /**
     * @PHPCRODM\String()
     */
    public $title;

    /**
     * @PHPCRODM\String()
     */
    public $content;
}
```

Note that there are basic Document classes for the standard PHPCR node types
``nt:file``, ``nt:folder`` and ``nt:resource``. See lib/Doctrine/ODM/PHPCR/Document/


## Storing documents in the repository: Id Generator Strategy

Every document needs an ``id``. This is used to later retrieve the document
from storage again. The id is the path in the content repository to the node
storing the document.

It is possible to choose the generator strategy.
Currently, there are 3 strategies available:

* With the default "assigned id" you need to assign a path to your id field and
    have to make sure yourself that the parent exists.
* The "parent and name" strategy determines the path from the @ParentDocument
    and the @Nodename fields. This is the most failsave strategy.
* The repository strategy lets your custom repository determine an id so you
    can implement any logic you might need.


### Assigned Id

This is the default but very unsafe strategy. You need to manually assign the
path to the id field.
A document is not allowed to have no parent, so you need to make sure that the
parent of that path already exists. (It can be a plain PHPCR node not
representing any PHPCR-ODM document, though.)

```php
<?php
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class Document
{
    /** @PHPCRODM\Id */
    public $id;
}

$doc = new Document();
$doc->id = '/test';
```

### Parent and name strategy (recommended)

This strategy uses the @Nodename (desired name of this node) and
@ParentDocument (PHPCR-ODM document that is the parent). The id is generated
as the id of the parent concatenated with '/' and the Nodename.

If you supply a ParentDocument annotation, the strategy is automatically set to
parent. This strategy will check the parent and the name and will fall back to
the assigned id if either is missing.


```php
<?php
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class Document
{
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Nodename */
    public $nodename;
}

$doc = new Document();
$doc->parent = $dm->find('/test');
$doc->nodename = 'mynode';
// => /test/mynode
```

### Repository strategy

If you need custom logic to determine the id, you can explicitly set the
strategy to "repository". You need to define the repositoryClass which will
handle the task of generating the id from the information in the document.
This gives you full control how you want to build the path.

```php
<?php
namespace Acme\SampleBundle\Document;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(repositoryClass="Acme\SampleBundle\DocumentRepository")
 */
class Document
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\String(name="title") */
    public $title;
}

class DocumentRepository extends BaseDocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document)
    {
        return '/functional/'.$document->title;
    }
}
```

## Available field annotations

<table>
<tr>
    <td>Id:</td>
    <td>Read only except on new documents with the assigned id strategy. The
        PHPCR path to this node. (see above). For new nodes not using the
        default strategy, it is populated during the persist() operation.
    </td>
</tr>
<tr>
    <td>Uuid:</td>
    <td>Read only (generated on flush). The unique id of this node. (only allowed if node is referenceable).</td>
</tr>
<tr>
    <td> Node:          </td>
    <td>The PHPCR\NodeInterface instance for direct access. This is populated
        as soon as you register the document with the manager using persist().
    </td>
</tr>
<tr>
    <td>Nodename:</td>
    <td>Read only except for new documents with the parent and name strategy.
        For new nodes with other id strategies, it is populated during the
        persist() operation.
        The name of the PHPCR node (this is the part of the path after the last
        '/' in the id).
    </td>
</tr>
<tr>
    <td>ParentDocument:</td>
    <td>Read only except for new documents with the parent and name strategy.
        The parent document of this document. If the repository knows the
        document class, the document will be of that type, otherwise
        Doctrine\ODM\PHPCR\Document\Generic is used.
    </td>
</tr>
<tr>
    <td>Child(name=x):</td>
    <td>Map the child with name x to this field. If name is not specified, the
        name of the annotated varialbe is used.
    </td>
</tr>
<tr>
    <td>Children(filter=x): </td>
    <td>Map the collection of children to this field. Filter is optional and
        works like the parameter in <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getNodes()">PHPCR Node::getNodes()</a>
    </td>
</tr>
<tr>
    <td>ReferenceOne(targetDocument="myDocument", strategy="weak"):  (*)</td>
    <td>Refers a document of the type myDocument. The default is a weak
        reference. By optionaly specifying strategy="hard" you get a hard reference.
        Finally with strategy="path" it will simply store the path to the node,
        but automatically dereference.
        It is optional to specify the targetDocument, you can reference any
        document type. However using strategy="path" will be faster if a targetDocument
        is set.
    </td>
</tr>
<tr>
    <td> ReferenceMany(targetDocument="myDocument", weak="weak"): (*)</td>
    <td>Same as ReferenceOne except that you can refer many documents with the
        same document and reference type. If you dont't specify targetDocument
        you can reference documents of mixed types in the same property. This
        type of collection will always be lazy loaded regardless of the strategy
        chosen.
    </td>
</tr>
<tr>
    <td>Referrers(filter="x", referenceType=null): </td>
    <td>Read only, the inverse of the Reference field. This field is a
        collection of all documents that refer this document. The ``filter``
        is optional. If set, it is used as parameter ``name`` for <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getWeakReferences%28%29">Node::getReferences()<a/>
        or <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getWeakReferences%28%29">Node::getWeakReferences()</a>.
        You can also specify an optional referenceType, weak or hard, to only
        get documents that have either a weak or a hard reference to this
        document. If you specify null then all documents with weak or hard
        references are fetched, which is also the default behavior.
    </td>
</tr>
<tr>
    <td>Locale:</td>
    <td>Indentifies the field that will be used to store the current locale of
        the document. This annotation is required for translatable documents.
    </td>
</tr>
<tr>
    <td> VersionName:</td>
    <td>Read only, only populated for detached documents returned by
        findVersionByName. Stores the version name this document represents.
        Otherwise its ignored.
    </td>
</tr>
<tr>
    <td>VersionCreated:</td>
    <td>Read only, only populated for detached documents returned by
        findVersionByName. Stores the DateTime object when this version was
        created with the checkin() operation. Otherwise its ignored.
    </td>
</tr>
<tr>
    <td>
        String,               <br />
        Binary,               <br />
        Long (alias Int),     <br />
        Decimal,              <br />
        Double (alias Float), <br />
        Date,                 <br />
        Boolean,              <br />
        Name,                 <br />
        Path,                 <br />
        Uri
    </td>
    <td>Map node properties to the document. See
        <a href="http://phpcr.github.com/doc/html/phpcr/propertytype.html">PHPCR\PropertyType</a>
        for details about the types.
    </td>
</tr>
</table>

(*) Note that creating new references with the help of the ReferenceOne/ReferenceMany
annotations is only possible if your PHPCR implementation supports programmatically
setting the uuid property at node creation.

### Parameters for the property types

In the parenthesis after the type, you can specify some additional information
like the name of the PHPCR property to store the value in.

<table>
<tr>
    <td>name</td>
    <td>The property name to use for storing this field. If not specified,
        defaults to the php variable name.
    </td>
</tr>
<tr>
    <td>multivalue</td>
    <td>Set multivalue=true to mark this property as multivalue. It then
        contains a numerically indexed array of values instead of just one
        value. For more complex data structures, use child nodes.
    </td>
</tr>
<tr>
    <td>translated</td>
    <td>Set translated=true to mark this property as being translated. See below.</td>
</tr>
</table>

```php
<?php

/**
 * @PHPCRODM\String(name="categories", multivalue=true)
 */
private $cat;
```

# Multilingual documents

PHPCR-ODM supports multilingual documents so that you can mark properties as
translatable and then make the document manager automatically store the
translations.

To use translatable documents you need to use several annotations and some
bootstrapping code. Your document annotation must specify a translator type.

```php
<?php
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class Article
{
    /** @PHPCRODM\Id */
    public $id;

    /**
     * The language this document currently is in
     * @PHPCRODM\Locale
     */
    public $locale;

    /**
     * Untranslated property
     * @PHPCRODM\Date
     */
    public $publishDate;

    /**
     * Translated property
     * @PHPCRODM\String(translated=true)
     */
    public $topic;

    /**
     * Language specific image
     * @PHPCRODM\Binary(translated=true)
     */
    public $image;
}
```

Note that translation always happens on a document level, not on individual fields.
With the above document, there is no way to store a new translation for the topic without
generating a copy of the image (unless you remove the translated=true from image, but then
the image is no longer translated for any language).

## Select the translation strategy

A translation strategy needs to be selected by adding the `translator` parameter to the @Document annotation.
The translation strategy is responsible to actually persist the translated properties.

There are two default translation strategies implemented:

* **attribute** - will store the translations in attributes of the node containing the translatable properties
* **child** - will store the translations in a child node of the node containing the translatable properties

It is possible to implement other strategies to persist the translations, see below.

### Implementing your own translation strategy

You may want to implement your own translation strategy to persist the translatable properties of a node.
For example if you want all the translations to be stored in a separate branch of you content repository.

To do so you need to implement the `Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface`.

Then you have to register your translation strategy with the document manager during the bootstrap.

```php
<?php
class MyTranslationStrategy implements Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface
{
    // ...
}

$dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
$dm->setTranslationStrategy('my_strategy_name', new MyTranslationStrategy());
```

After registering your new translation strategy you can use it in the @Document annotation:

```php
<?php
/**
 * @PHPCRODM\Document(translator="my_strategy_name")
 */
class Article
{
    // ...
}
```

## Select the language chooser strategy

The language chooser strategy provides the default language and a list of languages
to be used as language fallback order to find the best available translation.

On reading, PHPCR-ODM tries to find a translation with each of the languages in that
list and throws a not found exception if none of the languages exists.

The default language chooser strategy (`Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser`) returns
a configurable list of languages based on the requested language. On instantiation, you specify
the default locale. This should be your application default locale. It is used to get the default locale order
which usually should not vary based on the current locale.
Based on the request or whatever criteria you have, you can use setLocale to have the document manager load
your document in the right language.

When you bootstrap the document manager, you need to set the language chooser strategy if you have
any translatable documents:

```php
<?php
$localePrefs = array(
    'en' => array('en', 'de', 'fr'), // When EN is requested try to get a translation first in EN, then DE and finally FR
    'fr' => array('fr', 'de', 'en'), // When FR is requested try to get a translation first in FR, then DE and finally EN
    'it' => array('fr', 'de', 'en'), // When IT is requested try to get a translation first in FR, then DE and finally EN
);

$dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
$dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));
```

You can write your own strategy by implementing `Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface`.
This is useful to determine the default language based on some logic, or provide fallback orders based on user preferences.


## Mark a field as @Locale

If you annotate a field with this annotation, the current locale of the
document is tracked there. It is populated when finding the document,
updated when you call bindTranslation and also taken into account when
you flush the document.

```php
<?php
/**
 * @PHPCRODM\Locale
 */
public $locale;
```

## Defining properties as translatable

A property is set as translatable adding the `translatable` parameter to the field definition annontation.

```php
<?php
/** @PHPCRODM\String(translated=true) */
public $topic;
```

You can set any type of property as translatable.

Having at least one property marked as translatable will make the whole document translatable and thus forces you to have a @Locale field (see above).

Please note that internally, the translatable properties will be persisted by the translator strategy, not directly by the document manager.

## Translations and references / hierarchy

For now, Child, Children, Parent, ReferenceMany, ReferenceOne and Referrers will all fall back to the default language.
The reason for this is that there can be only one tracked instance of a document per session. (Otherwise what should happen
if both copies where modified?...).

For more details, see the [wiki page](https://github.com/doctrine/phpcr-odm/wiki/Multilanguage) and the TODO at the top if this README.

## Translation API

Please refer to the phpDoc of the following functions:

__For reading__:

* DocumentManager::find (uses the default locale)
* DocumentManager::findTranslation (allows you to specify which locale to load)
* DocumentManager::getLocalesFor (get the available locales of a document)

__For writing__:

* DocumentManager::persist (save document in language based on @Locale or default language)
* DocumentManager::persitTranslation (save document with explicit language context)

## Example

```php
<?php

// bootstrap the DocumentManager as required (see above)

$localePrefs = array(
    'en' => array('en', 'fr'),
    'fr' => array('fr', 'en'),
);

$dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
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

// Get the document in default language (English if you bootstrapped as in the example)
$doc = $dm->find('Doctrine\Tests\Models\Translation\Article', '/my_test_node');

// Get the document in French
$doc = $dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/my_test_node', 'fr');
$doc->title = 'nouveau';
$dm->flush(); // french is updated as the language is tracked by the dm
```

## Limitations

The provided translation strategies will report a translation as not existing
if any of the fields declared in the document is not existing. This is a
feature because you want to know when you try to load an incomplete document.
But we are currently missing a concept how to do update the content to still be
compatible when document annotations are changed. The solution could look
similar to the ORM migrations concept.


# Versioning documents

PHPCR-ODM natively supports versioning documents, using the power of the PHPCR
Version features. Before you try this out, make sure your implementation
supports the versioning features.
PHPCR-ODM does not replicate the complete PHPCR Version API (VersionManager,
VersionHistory and Version). For the full power, you need to access the PHPCR
session and interact with the VersionManager directly.
PHPCR-ODM provides simple methods for the common operations.

## Concept

There are 2 levels: simpleVersionable and (full) versionable. Simple versioning
consists of a linear verison history and the checkin/checkout possibility.
Checking in a node creates a new version and makes the node readonly. You need
to check it out again to write to it (or just do a checkpoint to do both in one
call).
Full versioning additionally has non-linear versioning (which the PHPCR-ODM
does not provide any helper methods for) and version labels (which we plan to
support once Jackalope supports them). For each node, you can add labels to
version, but one label string may only occur once per version history (meaning
if you want to label another version, you need to remove the label from the
first version before you add the label).

Version names are generated by PHPCR and can not be controlled by the client
application. There is no concept of commit messages for PHPCR. We decided to
not build something like that into the core of the ODM versioning system to
avoid unnecessary overhead if the user does not need it. It is however doable
with having a field on your document that you set to your commit message and
flush before calling checkin().

For more background, read the [Versioning section in the PHPCR Tutorial](https://github.com/phpcr/phpcr/blob/master/doc/Tutorial.md)
and refer to the [specification JCR 2.0, Chapter 15](http://www.day.com/specs/jcr/2.0/15_Versioning.html).

For the PHPCR-ODM layer, the following applies: Contrary to translations,
getting an old version does not change the document representing the current
version. An old version can't be modified and can't be persisted. (Except with
the special restoreVersion and removeVersion methods.)
What you get is a detached instance of the document which is ignored by flush
and can not be persisted.



## Versioning API

Please refer to the phpDoc of the following functions:

__Read version information__:

* DocumentManager::find (returns the current version of the document)
* DocumentManager::getAllLinearVersions (returns information about existing versions)
* DocumentManager::findVersionByName (returns a detached read-only document representing a version)

__Modify the version history__:

* DocumentManager::checkin (create new version of a flushed document and make it readonly)
* DocumentManager::checkout (make a document that was checked in writable again)
* DocumentManager::checkpoint (create a new version without making the document read-only, aka checkin followed by checkout)
* DocumentManager::restoreVersion (restore the document to an old version)
* DocumentManager::removeVersion (completely remove an old version from the history)


## Example

```php
<?php
$article = new Article();
$article->id = '/test';
$article->topic = 'Test';
$dm->persist($article);
$dm->flush();

// generate a version snapshot of the document as currently stored
$dm->checkpoint($article);

$article->topic = 'Newvalue';
$dm->flush();

// get the version information
$versioninfos = $dm->getAllLinearVersions($article);
$firstVersion = reset($versioninfos);
// and use it to find the snapshot of an old version
$oldVersion = $dm->findVersionByName(null, $article->id, $firstVersion['name']);

echo $oldVersion->topic; // "Test"

// find the head version
$article = $dm->find('/test');
echo $article->topic; // "Newvalue"

// restore the head to the old version
$dm->restoreVersion($oldVersion);

// the article document is refreshed
echo $article->topic; // "Test"

// create a second version to demo removing a version
$article->topic = 'Newvalue';
$dm->flush();
$dm->checkpoint($article);

// remove the old version from the history (not allowed for the last version)
$dm->removeVersion($oldVersion);
```

## Annotations

To be able to use the versioning methods of the DocumentManager, you need to
specify the versionable attribute in your @Document annotation. You can choose
between "full" and "simple" versionable.

If you only use the methods the DocumentManager offers, "simple" is enough.
This will allow you to create a linear version history. The full versionable
corresponds to the PHPCR mix:versionable that allows to branch versions. If you
need that, you will need to access PHPCR directly for some operations.

```php
<?php
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="full")
 */
class Article
{
    ... // properties as normal
}
```

Note that all fields of a document are automatically versioned, you can not
exclude anything from being versioned. Referenced documents are not versioned,
but it is stored to which document the reference pointed at this time.
Children and parents are not versioned. (Actually children could be versioned
if you are using a PHCPR node types that specifies to cascade versioning. This
feature however is untested with PHPCR-ODM, if you have feedback please tell us.)


You can track some information about old versions in PHPCR-ODM. The VersionName
tracks the code that PHPCR assigned the version you created, VersionCreated the
timestamp when the version was created.

Be aware that there are two things:
1. The document that is *versionable*. This is **the** document and you can
    take snapshots of this document with the ``checkin()`` / ``checkpoint()``
    methods.
2. The frozen document that represents an old version of your document. You get
    this document with the findVersionByName method. It is read-only.
    The document class you use needs not be the same. You can define a *version*
    document that is the same as your base document, but all fields are read
    only and you use the VersionName and VersionCreated annotations on it. It
    also does not need the versionable document attribute. (You do not create
    versions of old versions, you only create versions of the main document.)

```php
<?php
    /** @PHPCRODM\VersionName */
    public $versionName;

    /** @PHPCRODM\VersionCreated */
    public $versionCreated;
```


# Lifecycle callbacks and event listeners / subscribers

You can use @PHPCRODM\PostLoad and friends to have doctrine call a method without
parameters on your entity.

You can also define event listeners and subscribers on the DocumentManager with
```$dm->getEventManager()->addEventListener(array(<eventnames>), listenerclass);```
Your class needs methods with the event names to get the events. They are passed
a parameter of the type Doctrine\ODM\PHPCR\Event\LifecycleEventArgs.
See also http://www.doctrine-project.org/docs/orm/2.0/en/reference/events.html

 * preRemove - occurs before a document is removed from the storage
 * postRemove - occurs after the document has been successfully removed
 * prePersist - occurs before a new document is created in storage
 * postPersist - occurs after a document has been created in storage. generated ids will be available in this state.
 * preUpdate - occurs before an existing document is updated in storage, during the flush operation
 * postUpdate - occurs after an existing document has successfully been updated in storage
 * postLoad - occurs after the document has been loaded from storage

Note: If you use this inside symfony2, you can use the tag
doctrine_phpcr.event_listener to register a service as event listener.
See the [README of the DoctrinePHPCRBundle](https://github.com/doctrine/DoctrinePHPCRBundle)
for more information.

# Doc TODOS

 * Explain Configuration class in more detail
 * Proxy classes: Either configuration with setAutoGenerateProxyClasses(true) or make sure you generate proxies.
    proxies are used when you have references, children and so on to not load the whole PHPCR repository.
