Getting Started
===============

.. note::

    **Difference from the ORM**

    Doctrine ORM knows several models of developing. If you know the ORM, please note that
    with PHPCR-ODM you can only develop "Code First". We do not (yet) have any generator to
    build model classes from a content repository.

    As PHPCR allows NoSQL like data storage, we speak of Documents rather than Entities
    to stress the fact that there need not be a rigid database model.

Doctrine PHPCR-ODM is an object-document mapper (ODM) for PHP 5.3.0+. It uses
the Data Mapper pattern to transparently map PHPCR nodes to standard PHP
objects allowing the programmer to easily build a domain model for their
application instead of dealing with raw data.

Starting with the object-oriented model is called the "Code First" approach.

What are Documents?
-------------------

Documents are lightweight PHP Objects that don't need to extend any
abstract base class or interface. A document class must not be final
or contain final methods. Additionally it must not implement
**clone** nor **wakeup**.

.. todo: or :doc:`do so safely <../cookbook/implementing-wakeup-or-clone>`.

See the :doc:`architecture chapter <../reference/architecture>` for a full list of the restrictions
that your entities need to comply with.

A document contains persistable properties. A persistable property
is an instance variable of the document that is saved into and retrieved from the content repository
by Doctrine's data mapping capabilities.

An Example Model: Document Management
-------------------------------------

* Documents have a title and a content
* Documents are arranged in a tree
* Documents can reference other documents

We do not build a web interface but simple run scripts on the command line to keep this example simple.

.. note::
    This is a simplistic document manage to illustrate the PHPCR-ODM features. If you want to build a
    custom Web Content Management System, we recommend looking into the `Symfony CMF <http://cmf.symfony.com>`_
    which is a content management framework built on top of Doctrine PHPCR-ODM and Symfony2.


Setup Project
-------------

Create a file composer.json in your project directory.

.. code-block:: javascript

    {
        "minimum-stability": "dev",
        "require": {
            "doctrine/phpcr-odm": "~1.2",
            "jackalope/jackalope-doctrine-dbal": "~1.1"
        },
        "autoload": {
          "psr-0": { "Demo\\": "src/" }
        }
    }

Then run the following commands on your command line

.. code-block:: bash

    $ curl -s http://getcomposer.org/installer | php --
    $ php composer.phar install

This will download the dependencies into the vendor/ folder and generate ``vendor/autoload.php``.

.. _intro-bootstrap:

Now we bootstrap Doctrine PHPCR-ODM. Create a file called ``bootstrap.php`` in
your project root directory::

    // bootstrap.php

    $vendorDir = __DIR__.'/vendor';

    $file = $vendorDir.'/autoload.php';
    if (file_exists($file)) {
        $autoload = require_once $file;
    } else {
        throw new RuntimeException('Install dependencies with composer.');
    }

    $params = array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'user'      => 'root',
        'password'  => '',
        'dbname'    => 'phpcr_odm_tutorial',
    );

    $workspace = 'default';
    $user = 'admin';
    $pass = 'admin';

    /* --- transport implementation specific code begin --- */
    // for more options, see https://github.com/jackalope/jackalope-doctrine-dbal#bootstrapping
    $dbConn = \Doctrine\DBAL\DriverManager::getConnection($params);
    $parameters = array('jackalope.doctrine_dbal_connection' => $dbConn);
    $repository = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository($parameters);
    $credentials = new \PHPCR\SimpleCredentials(null, null);
    /* --- transport implementation specific code  ends --- */

    $session = $repository->login($credentials, $workspace);

    /* prepare the doctrine configuration */
    use Doctrine\Common\Annotations\AnnotationRegistry;
    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
    use Doctrine\ODM\PHPCR\Configuration;
    use Doctrine\ODM\PHPCR\DocumentManager;

    AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));

    $reader = new AnnotationReader();
    $driver = new AnnotationDriver($reader, array(
        // this is a list of all folders containing document classes
        'vendor/doctrine/phpcr-odm/lib/Doctrine/ODM/PHPCR/Document',
        'src/Demo',
    ));

    $config = new Configuration();
    $config->setMetadataDriverImpl($driver);

    $documentManager = DocumentManager::create($session, $config);

    return $autoload;

To enable the command line, copy the cli-config.<implementation>.php.dist
to cli-config.php in your vendor directory and adjust it to match your
bootstrap.php. Or better, remove the duplicate code and include cli-config.php
from your bootstrap.php file.

If you want it in the root directory, configure the composer bin-dir to ``bin``:

.. code-block:: javascript

    "config": {
        "bin-dir": "bin"
    }

Building the model
------------------

Models are plain PHP classes. Note that you have several ways to define the mapping.
For easy readability, we use the annotation mapping with PHPCR namespace in this tutorial::

    // src/Demo/Document.php
    namespace Demo;

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document
     */
    class MyDocument
    {
        /**
         * @PHPCR\Id
         */
        private $id;

        /**
         * @PHPCR\ParentDocument
         */
        private $parent;

        /**
         * @PHPCR\Nodename
         */
        private $name;

        /**
         * @PHPCR\Children
         */
        private $children;

        /**
         * @PHPCR\Field(type="string")
         */
        private $title;

        /**
         * @PHPCR\Field(type="string")
         */
        private $content;

        public function getId()
        {
            return $this->id;
        }
        public function getChildren()
        {
            return $this->children;
        }
        public function setParent($parent)
        {
            $this->parent = $parent;
        }
        public function setName($name)
        {
            $this->name = $name;
        }

        public function setTitle($title)
        {
            $this->title = $title;
        }
        public function getTitle()
        {
            return $this->title;
        }
        public function setContent($content)
        {
            $this->content = $content;
        }
        public function getContent()
        {
            return $this->content;
        }
    }

If you are familiar with Doctrine ORM, this code should look pretty familiar to you. The
only important difference are the hierarchy related annotations ParentDocument, Name and Children.
In PHPCR, data is stored in trees. Every document has a parent and its own name. The id is
built from this structure, resulting in path strings. The recommended way to generate the
id is by assigning a name and a parent to the document. See the section on identifier
strategies in the reference chapter :doc:`Objects and Fields <basic-mapping>`
for other possibilities.

.. note::

    PHPCR-ODM provides default classes for the standard PHPCR node types ``nt:file``,
    ``nt:folder`` and ``nt:resource``, as well as a GenericDocument for when a PHPCR node
    can not be mapped to a specific document. See the classes in lib/Doctrine/ODM/PHPCR/Document/


Initialize the repository
-------------------------

With jackalope-doctrine-dbal, you need to run the following command to
init the database:

.. code-block:: bash

    ./vendor/bin/phpcrodm jackalope:init:dbal

Then, regardless of the PHPCR implementation you use, you need to run
another command to let Doctrine set up the repository for using it:

.. code-block:: bash

    ./vendor/bin/phpcrodm doctrine:phpcr:register-system-node-types


Storing documents
-----------------

We write a simple PHP script to generate some sample data::

    // src/generate.php

    require_once '../bootstrap.php';

    // get the root node to add our data to it
    $rootDocument = $documentManager->find(null, '/');

    // create a new document
    $doc = new \Demo\Document();
    $doc->setParent($rootDocument);
    $doc->setName('doc');
    $doc->setTitle('My first document');
    $doc->setContent('The document content');

    // create a second document
    $childDocument = new \Demo\Document();
    $childDocument->setParent($doc);
    $childDocument->setName('child');
    $childDocument->setTitle('My child document');
    $childDocument->setContent('The child document content');


    // make the documents known to the document manager
    $documentManager->persist($doc);
    $documentManager->persist($childDocument);

    // store all changes, insertions, etc. with the storage backend
    $documentManager->flush();

.. note::

    In real projects, you should look into the `doctrine fixtures`_
    to script generating content.


Reading documents
-----------------

This script will simply echo the data to the console::

    // src/read.php

    require_once '../bootstrap.php';

    $doc = $documentManager->find(null, "/doc");

    echo 'Found '.$doc->getId() ."\n";
    echo 'Title: '.$doc->getTitle()."\n";
    echo 'Content: '.$doc->getContent()."\n";

The DocumentManager will automatically determine the document class when
you pass ``null`` as first argument to ``find()``.

Tree traversal
--------------

PHPCR is a tree based store. Every document must have a parent, and
can have children. We already used this when creating the document.
The ``@ParentDocument`` maps the parent of a document and is used
to determine the position in the tree, together with ``@Nodename``.

As the children of our sample document are mapped with ``@Children``,
we can traverse them::

    use Demo\MyDocument;

    $doc = $documentManager->find(null, "/doc");

    foreach($doc->getChildren() as $child) {
        if ($child instanceof MyDocument) {
            echo 'Has child '.$child->getId() . "\n";
        } else {
            echo 'Unexpected child '.get_class($child)."\n";
        }
    }

.. caution::

    Children can be of any class. Be careful when looping over children
    to be sure they are of the expected class.

Even if children are not mapped, you can use the document manager to get all
flushed children of a document::

    $children = $documentManager->getChildren($parent);

.. note:: *Difference from ORM*

    While with the ORM, the natural thing to get data is to query, with
    PHPCR-ODM the natural way is to use the hierarchy, that is parent-child
    relations.

    If you need to query, see :ref:`Querying in the Working with Objects section <workingobjects-query>`.


Add references
--------------

PHPCR-ODM supports arbitrary links between documents. The referring
document does not need to know what class it links to. Use
``ReferenceOne`` resp. ``@ReferenceMany`` to map the link
to a document or a collection of links to documents.

You can also map the inverse relation. ``@Referrers`` needs the
referring class but can be used to add referencing documents.
``@MixedReferrers`` maps all documents referencing this document,
but is readonly.

Lets look at an example of document ``A`` referencing ``B``::

    // src/Demo/A.php

    namespace Demo;

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document
     */
    class A
    {
        /**
         * @PHPCR\ReferenceOne
         */
        private $ref;

        ...
    }

    /**
     * @PHPCR\Document
     */
    class B
    {
        /**
         * @PHPCR\Referrers(referringDocument="Demo\A", referencedBy="ref")
         */
        private $referrers;
    }

We can now create a reference with the following code::

    $parent = $dm->find(null, '/');
    $a = new A();
    $a->setParent($parent);
    $a->setNodename('a');
    $dm->persist($a);
    $b = new B();
    $b->setParent($parent);
    $b->setNodename('b');

    $a->setRef($b);

    $dm->flush();
    $dm->clear();

    $b = $dm->find(null, '/b');

    // output Demo\A
    var_dump(get_class($b->getReferrers()));

If referrers are not mapped on a document, you can use the document
manager to get all flushed referrers of a document::

    $referrers = $documentManager->getReferrers($b);


Removing documents
------------------

To delete a document, call the ``remove`` method on the ``DocumentManager``::

    // src/manipulate.php

    require_once '../bootstrap.php';

    // remove a document
    $doc = $documentManager->find(null, '/doc');
    $documentManager->remove($doc);

    // persist all operations
    $documentManager->flush();


Other helpful methods on the DocumentManager
----------------------------------------------

You can move a document to a different path with the ``move`` method.
Alternatively, you can assign a different Parent and/or Nodename to move
by assignment. The latter is for example handy with Symfony2 forms::

    // src/manipulate.php

    require_once '../bootstrap.php';

    // we move a node
    $child = $documentManager->find(null, '/doc/child');
    $documentManager->move($child, '/newpath');

    // persist all operations
    $documentManager->flush();


Conclusion
----------

This tutorial is over here, I hope you had fun.

Additional details on all the topics discussed here can be found in
the respective manual chapters.


.. _`doctrine fixtures`: https://github.com/doctrine/data-fixtures
