Installation
============

If you use the Doctrine PHPCR ODM **Symfony Bundle**, please look into the
`Tutorial to install the DoctrinePHPCRBundle <http://symfony.com/doc/master/cmf/cookbook/installing_configuring_doctrine_phpcr_odm.html>`_.
This documentation explains how to use PHPCR ODM outside of symfony, which requires some
manual initialization.


Composer
--------

Doctrine PHPCR-ODM should be installed through composer. The reason is that it depends on
quite a few other libraries which in turn require other libraries.
If you really can't use composer in a project, we recommend you do a test installation of
phpcr-odm with composer to see the dependencies and add manually everything from vendor/ to
your project.

`Composer <http://www.getcomposer.org>`_ is the recommended installation method for Doctrine PHPCR-ODM.
Define the following requirement in your ``composer.json`` file:

.. code-block:: javascript

    {
        "require": {
            "doctrine/phpcr-odm": "*"
        }
    }

Then run ``composer install`` and you are done.

PHPCR provider
--------------

Doctrine PHPCR-ODM uses the `PHP Content Repository API <http://phpcr.github.io/>`_ for
storage. You can force one of the available providers in your projects composer.json file
"require" section by specifying one of the "suggest" libraries in the phpcr-odm composer.json

Each of the providers requires some additional setup. The following sections will briefly list
how to set each of them up. Please refer to the documentation of the provider you choose for details.

Install Jackalope-Jackrabbit PHCPR provider
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Jackalope-Jackrabbit uses the Java backend jackrabbit for storage.

Follow `Running Jackrabbit Server <http://github.com/jackalope/jackalope/wiki/Running-a-jackrabbit-server>`_
from the Jackalope wiki to have the storage backend.

Bootstrap will roughly look like this::

    $workspace = 'default';
    $user = 'admin';
    $pass = 'admin';
    $repository = \Jackalope\RepositoryFactoryJackrabbit::getRepository(
                        array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server'));
    $credentials = new \PHPCR\SimpleCredentials($user, $pass);
    $session = $repository->login($credentials, $workspace);



Install Jackalope Doctrine DBAL PHPCR provider
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Jackalope Doctrine DBAL maps PHPCR to relational databases. It should support all
databases that Doctrine DBAL can support.

Create the database as described in the documentation of
`Jackalope Doctrine DBAL <http://github.com/jackalope/jackalope-doctrine-dbal>`_.


Bootstrap will roughly look like this when using mysql as storage backend::

    $workspace = 'default';
    $user = 'admin';
    $pass = 'admin';

    $params = array(
        'driver'    => 'pdo_mysql', // or pdo_pgsql
        'host'      => 'localhost',
        'user'      => $user,
        'password'  => $pass,
        'dbname'    => 'phpcr_odm_tutorial',
    );

    // Bootstrap Doctrine DBAL
    $dbConn = \Doctrine\DBAL\DriverManager::getConnection($params);

    $repository = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository(
        array('jackalope.doctrine_dbal_connection' => $dbConn)
    );
    // dummy credentials to comply with the API
    $credentials = new \PHPCR\SimpleCredentials(null, null);
    $session = $repository->login($credentials, $workspace);


Jackalope Doctrine DBAL does currently not manage users, so the simple
credentials are ignored.

Jackalope Doctrine DBAL also works with sqlite. Use the following parameters::

    $params = array(
        'driver' => 'pdo_sqlite',
        'dbname' => 'odm',
        'path' => '/tmp/jackalope.db',
    );


Install Midgard2 PHPCR provider
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

`Midgard2 <https://github.com/midgardproject/phpcr-midgard2>`_ is a PHP extension
that persists PHPCR into relational databases like SQLite and MySQL.

Midgard2 needs `the migard-php5 PHP extension <https://github.com/midgardproject/midgard-php5>`_
to run. On typical Linux setups getting the extension is as easy as:

    sudo apt-get install php5-midgard2

Bootstrap will roughly look like this when using mysql as storage backend::

    $workspace = 'default';
    $user = 'admin';
    $pass = 'password';

    $params = array(
        'midgard2.configuration.db.type' => 'MySQL',
        'midgard2.configuration.db.name' => 'phpcr',
        'midgard2.configuration.db.host' => 'localhost',
        'midgard2.configuration.db.username' => 'midgard',
        'midgard2.configuration.db.password' => 'midgard',
        'midgard2.configuration.blobdir' => '/some/path/for/blobs',
        'midgard2.configuration.db.init' => true,
    );
    $repository = \Midgard\PHPCR\RepositoryFactory::getRepository($params);

    $credentials = new \PHPCR\SimpleCredentials($user, $pass);
    $session = $repository->login($credentials, $workspace);


Note that the `midgard2.configuration.db.init` setting should only be used the
first time you connect to the Midgard2 repository. After that the database is
ready and this setting should be removed for better performance.

The `$user` and `$pass` are the credentials for the PHPCR user. The
`...db.username` and `...db.password` configuration values are used by the
mysql driver of midgard to connect to the database.


Midgard can also use sqlite, with the following parameters::

    $params = array(
        'midgard2.configuration.db.type' => 'SQLite',
        'midgard2.configuration.db.name' => 'odm',
        'midgard2.configuration.db.dir' => '/tmp',
        'midgard2.configuration.blobdir' => '/tmp/blobs'
        'midgard2.configuration.db.init' => true,
    );

Configuration
=============

Bootstrapping Doctrine PHPCR-ODM is a relatively simple procedure that
roughly exists of four steps:

-  Installation (see above)
-  Making sure Doctrine class files are autoloaded.
-  Obtaining a DocumentManager instance.
-  Configuration of the Console Tool and run the register-system-node-types command

.. tip::

    Straightforward bootstrap sample files for all PHPCR implementations
    are found in the root folder of phpcr-odm. They are called
    cli-config.*.php.dist. You will need one of those files to
    :ref:`set up the console <installation_configuration_console>`, but it can
    be used for the rest of your application too.

Class loading with composer
---------------------------

Autoloading is taken care of by Composer. You just have to include the
composer autoload file in your project::

    // Include Composer Autoload
    // if this file does not exist, you forgot to run php composer.phar install
    require_once __DIR__ . "/vendor/autoload.php";

Obtaining an ObjectManager
--------------------------

Once you have prepared the class loading, you acquire an *ObjectManager*
instance. The ObjectManager class is the primary access point to the document
mapper functionality provided by Doctrine PHPCR-ODM.

Prepare the mapping driver
~~~~~~~~~~~~~~~~~~~~~~~~~~

In order to make PHPCR-ODM understand your documents, you need to provide mappings.

You can choose between the drivers for annotations, xml and yml configuration files.
Add the respective code right after the autoloading.

See later in this chapter for more options with the mapping drivers.

Annotation Mapping Driver
^^^^^^^^^^^^^^^^^^^^^^^^^

With the annotation driver, you can annotate the fields in your document
classes with the mapping metadata::

    use Doctrine\Common\Annotations\AnnotationRegistry;
    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;

    AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));

    $reader = new AnnotationReader();
    $driver = new AnnotationDriver($reader, array('/path/to/your/document/classes'));

.. note::

    Since PHPCR-ODM 1.1, the annotations are autoloaded like any other class.

    With version 1.0, you needed to register the annotation file::

        use Doctrine\Common\Annotations\AnnotationRegistry;

        AnnotationRegistry::registerLoader(function($class) use ($autoload) {
            $autoload->loadClass($class);
            return class_exists($class, false);
        });
        AnnotationRegistry::registerFile(__DIR__.'/vendor/doctrine/phpcr-odm/lib/Doctrine/ODM/PHPCR/Mapping/Annotations/DoctrineAnnotations.php');

XML Mapping Driver
^^^^^^^^^^^^^^^^^^

With the XML driver, you create separate XML files that map between your
documents and PHPCR::

    use Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver;

    $driver = new XmlDriver(array('/path/to/your/xml-mapping/files'));

YML Mapping Driver
^^^^^^^^^^^^^^^^^^

Your project must require symfony/yaml in composer.json::

    use Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver;

    $driver = new YamlDriver(array('/path/to/your/yml-mapping/files'));


Quick Configuration Example
~~~~~~~~~~~~~~~~~~~~~~~~~~~

A complete configuration could look like this::

    $workspace = 'default';
    $user = 'admin';
    $pass = 'admin';

    /***** transport implementation specific code begin *****/

    /* --- see above for sample bootstrapping code of other repository implementations --- */

    $params = array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'user'      => $user,
        'password'  => $pass,
        'dbname'    => 'phpcr_odm_tutorial',
    );
    $dbConn = \Doctrine\DBAL\DriverManager::getConnection($params);
    $parameters = array('jackalope.doctrine_dbal_connection' => $dbConn);
    $repository = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository($parameters);
    $credentials = new \PHPCR\SimpleCredentials(null, null);

    /***** transport implementation specific code  ends *****/


    $session = $repository->login($credentials, $workspace);

    /* prepare the doctrine configuration */
    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
    use Doctrine\ODM\PHPCR\DocumentManager;

    $reader = new AnnotationReader();
    $driver = new AnnotationDriver($reader, array('/path/to/your/document/classes'));

    $config = new \Doctrine\ODM\PHPCR\Configuration();
    $config->setMetadataDriverImpl($driver);

    $documentManager = DocumentManager::create($session, $config);

.. note::

    Your PHPCR implementation should document the options for the repository
    factory.

    As you can see, the PHPCR implementation jackalope-doctrine-dbal used in
    this example needs a Doctrine DBAL connection to store its data in a
    database. You can learn more about the options for the connection in this
    case with the
    `Doctrine DBAL connection configuration reference <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html>`_.

Configuration Options
---------------------

The following sections describe all the configuration options
available on a ``Doctrine\ORM\Configuration`` instance.

Proxy Directory (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Configure the directory where proxy objects are cached::

    $config->setProxyDir($dir);
    $config->getProxyDir();

For a detailed explanation on proxy classes and how they are used in Doctrine,
see :ref:`installation_proxy-objects`.

Proxy Namespace (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    $config->setProxyNamespace($namespace);
    $config->getProxyNamespace();

Gets or sets the namespace to use for generated proxy classes. For
a detailed explanation on proxy classes and how they are used in
Doctrine, refer to the "Proxy Objects" section further down.

Metadata Driver (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    $config->setMetadataDriverImpl($driver);
    $config->getMetadataDriverImpl();

Gets or sets the metadata driver implementation that is used by
Doctrine to acquire the object-relational metadata for your
classes.

There are currently 4 implementations available:

-  ``Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver``
-  ``Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver``
-  ``Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver``
-  ``Doctrine\ODM\PHPCR\Mapping\Driver\DriverChain``

Throughout the most part of this manual the AnnotationDriver is
used in the examples. For information on the usage of the XmlDriver
or YamlDriver please refer to the dedicated chapters
``XML Mapping`` and ``YAML Mapping``.

When you manually instantiate the annotation driver, you need to tell it the
path to the entities. All metadata drivers accept either a single directory as
a string or an array of directories. With this feature a single driver can
support multiple directories of Documents.

Metadata Cache (***RECOMMENDED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    $config->setMetadataCacheImpl($cache);
    $config->getMetadataCacheImpl();

Gets or sets the cache implementation to use for caching metadata
information, that is, all the information you supply via
annotations, xml or yaml, so that they do not need to be parsed and
loaded from scratch on every single request which is a waste of
resources. The cache implementation must implement the
``Doctrine\Common\Cache\Cache`` interface.

Usage of a metadata cache is highly recommended.

The recommended implementations for production are:


-  ``Doctrine\Common\Cache\ApcCache``
-  ``Doctrine\Common\Cache\MemcacheCache``
-  ``Doctrine\Common\Cache\XcacheCache``
-  ``Doctrine\Common\Cache\RedisCache``

For development you should use the
``Doctrine\Common\Cache\ArrayCache`` which only caches data on a
per-request basis.

Auto-generating Proxy Classes (***OPTIONAL***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    $config->setAutoGenerateProxyClasses($bool);
    $config->getAutoGenerateProxyClasses();

Gets or sets whether proxy classes should be generated
automatically at runtime by Doctrine. If set to ``FALSE``, proxy
classes must be generated manually through the doctrine command
line task ``generate-proxies``. The strongly recommended value for
a production environment is ``FALSE``.

Development vs Production Configuration
---------------------------------------

You should code your Doctrine PHPCR-ODM bootstrapping with two different
runtime models in mind. There are some serious benefits of using
APC or Memcache in production. In development however this will
frequently give you fatal errors, when you change your entities and
the cache still keeps the outdated metadata. That is why we
recommend the ``ArrayCache`` for development.

Furthermore you should have the Auto-generating Proxy Classes
option to true in development and to false in production. If this
option is set to ``TRUE`` it can seriously hurt your script
performance if several proxy classes are re-generated during script
execution. Filesystem calls of that magnitude can even slower than
all the database queries Doctrine issues. Additionally writing a
proxy sets an exclusive file lock which can cause serious
performance bottlenecks in systems with regular concurrent
requests.

Connection Options
------------------

The ``$session`` passed as the first argument to ``DocumentManager::create()``
has to be an instance of ``PHPCR\SessionInterface``.
See the documentation of your PHPCR implementation for further options when
creating the session.

.. _installation_proxy-objects:

Proxy Objects
-------------

A proxy object is an object that is put in place or used instead of
the "real" object. A proxy object can add behavior to the object
being proxied without that object being aware of it. In Doctrine 2,
proxy objects are used to realize several features but mainly for
transparent lazy-loading.

Proxy objects with their lazy-loading facilities help to keep the
subset of objects that are already in memory connected to the rest
of the objects. This is an essential property as without it there
would always be fragile partial objects at the outer edges of your
object graph.

Doctrine 2 implements a variant of the proxy pattern where it
generates classes that extend your entity classes and adds
lazy-loading capabilities to them. Doctrine can then give you an
instance of such a proxy class whenever you request an object of
the class being proxied. This happens in two situations:

Reference Proxies
~~~~~~~~~~~~~~~~~

The method ``DocumentManager::getReference($documentName, $identifier)``
lets you obtain a reference to a document for which the identifier
is known, without loading that entity from the database. This is
useful, for example, as a performance enhancement, when you want to
establish an association to an entity for which you have the
identifier. You could simply do this::

    // $dm instanceof DocumentManager, $cart instanceof MyProject\Model\Cart
    // $itemId comes from somewhere, probably a request parameter
    $item = $dm->getReference('MyProject\Model\Item', $itemId);
    $cart->addItem($item);

Here, we added an Item to a Cart without loading the Item from the
database. If you invoke any method on the Item instance, it would
fully initialize its state transparently from the database. Here
$item is actually an instance of the proxy class that was generated
for the Item class but your code does not need to care. In fact it
**should not care**. Proxy objects should be transparent to your
code.

Be aware that in this situation, you may not pass null for the $documentName
as the autodetecting only works when it can actually load the document from
the repository.


Generating Proxy classes
~~~~~~~~~~~~~~~~~~~~~~~~

Proxy classes can either be generated manually through the Doctrine
Console or automatically by Doctrine. The configuration option that
controls this behavior is::

    $config->setAutoGenerateProxyClasses($bool);
    $config->getAutoGenerateProxyClasses();

The default value is ``true`` for convenient development. However,
this setting is not optimal for performance and therefore not
recommended for a production environment. To eliminate the overhead
of proxy class generation during runtime, set this configuration
option to ``false``. When you do this in a development environment,
note that you may get class/file not found errors if certain proxy
classes are not available or failing lazy-loads if new methods were
added to the entity class that are not yet in the proxy class.

When you set auto generate to ``false``, you need to generate the proxy classes
each time you change anything on your class or mapping:

.. code-block:: bash

    $ ./vendor/bin/phpcrodm doctrine:phpcr:generate-proxies

.. note::

    This command is only available since PHPCR-ODM 1.1.

Autoloading Proxies
~~~~~~~~~~~~~~~~~~~

When you deserialize proxy objects from the session or any other storage
it is necessary to have an autoloading mechanism in place for these classes.
For implementation reasons Proxy class names are not PSR-0 compliant. This
means that you have to register a special autoloader for these classes::

    use Doctrine\ORM\Proxy\Autoloader;

    $proxyDir = '/path/to/proxies';
    $proxyNamespace = 'MyProxies';

    Autoloader::register($proxyDir, $proxyNamespace);

If you want to execute additional logic to intercept the proxy file not found
state you can pass a closure as the third argument. It will be called with
the arguments proxydir, namespace and className when the proxy file could not
be found.


Multiple Metadata Sources
~~~~~~~~~~~~~~~~~~~~~~~~~

When using different components using Doctrine 2 you may end up
with them using two different metadata drivers, for example XML and
YAML. You can use the DriverChain Metadata implementations to
aggregate these drivers based on namespaces::

    use Doctrine\ORM\Mapping\Driver\DriverChain;

    $chain = new DriverChain();
    $chain->addDriver($xmlDriver, 'Doctrine\Tests\Models\Company');
    $chain->addDriver($yamlDriver, 'Doctrine\Tests\PHPCR-ODM\Mapping');

Based on the namespace of the entity the loading of entities is
delegated to the appropriate driver. The chain semantics come from
the fact that the driver loops through all namespaces and matches
the entity class name against the namespace using a
``strpos() === 0`` call. This means you need to order the drivers
correctly if sub-namespaces use different metadata driver
implementations.

Default Repository (***OPTIONAL***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Specifies the FQCN of a subclass of the Doctrine\Common\Persistence\ObjectRepository.
This will be used for all documents that do not specify a custom repository
class::

    $config->setDefaultRepositoryClassName($fqcn);
    $config->getDefaultRepositoryClassName();

The default value is ``Doctrine\ODM\PHPCR\DocumentRepository``.

.. note::

    This option was introduced in PHPCR-ODM 1.1.

.. _installation_configuration_console:

Setting up the Console
----------------------

Doctrine uses the Symfony Console component for generating the command line
interface. You can take a look at the ``bin/phpcrodm.php`` script  for
inspiration how to setup the cli.

If you installed Doctrine PHPCR-ODM through Composer, then the ``phpcrodm``
script is available to you in the bin-dir, by default at
``vendor/bin/phpcrodm``. Otherwise create a symlink to the file or run it
inside the phpcr-odm folder.

Next, you need to copy the cli-config.<implementation>.php.dist file from the
phpcr-odm folder to the parent folder of where you have the binary and adjust
it to bootstrap your application. The details of what you can configure are
explained above.

The :doc:`Tools Chapter <tools>` explains the commands you have available.

Register system node types
~~~~~~~~~~~~~~~~~~~~~~~~~~

PHPCR ODM uses a `custom node type <http://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr%3Amanaged>`_
to track meta information without interfering with your content. Before you can
use a PHPCR repository to store documents, you need to run the following
command:

.. code-block:: bash

    $ php bin/phpcrodm doctrine:phpcr:register-system-node-types
