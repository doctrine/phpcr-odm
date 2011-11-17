PHPCR ODM for Doctrine2
=======================

Current Status
--------------

* most key features implemented
* alpha stage
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/PHPCR)
* [![Build Status](https://secure.travis-ci.org/doctrine/phpcr-odm.png)](http://travis-ci.org/doctrine/phpcr-odm)

TODO
----

* complete mapping for relations (parent, references), then remove the node mapping
* ensure that no Jackalope specific classes are used (especially relevant for the tests)
* have the register-system-node-types command provide api conform node type definition as well to support other implementations
* add support for SQL/QOM
* write documentation
* expand test suite

Getting Started
---------------

 0. Install jackrabbit according to https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr%3Amanaged <br />
        You need a patched jackrabbit and run the command to register types, as explained in the linked documtation.

 1. Define one of those mapping drivers

    ```php
    <?php
    // Annotation driver
    $reader = new \Doctrine\Common\Annotations\AnnotationReader();
    $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader, array('/path/to/your/document/classes'));

    // Xml driver
    $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver(array('/path/to/your/mapping/files'));

    // Yaml driver
    $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver(array('/path/to/your/mapping/files'));
    ```

 2. Initialize a PHPCR session<br />
        Eventually, this module will support all PHPCR backends, but at the moment it is only tested with jackalope jackrabbit.

    ```php
    <?php
    $repository = \Jackalope\RepositoryFactoryJackrabbit::getRepository(
                        array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server'));
    $credentials = new \PHPCR\SimpleCredentials('user', 'pass');
    $session = $repository->login($credentials, 'your_workspace');
    ```

 3. Initialize the DocumentManager

    ```php
    <?php
    $config = new \Doctrine\ODM\PHPCR\Configuration();
    $config->setMetadataDriverImpl($driver);

    $dm = new \Doctrine\ODM\PHPCR\DocumentManager($session, $config);
    ```

 4. Example usage

    ```php
    <?php
    // fetching a document by JCR path (id in PHPCR ODM lingo)
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

    // run a query
    $query = $dm->createQuery('SELECT *
                      FROM [nt:unstructured]
                      WHERE ISCHILDNODE("/functional")
                      ORDER BY username',
                      \PHPCR\Query\QueryInterface::JCR_SQL2);
    $query->setLimit(2);
    $result = $this->dm->getDocumentsByQuery($query, 'My\Document\Class');
    foreach ($result as $document) {
        echo $document->getId();
    }
    ```

Document Classes
----------------

You write your own document classes that will be mapped to and from the phpcr database by doctrine. The documents are usually simple

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

Note that there are basic Document classes for the standard PHPCR node types nt:file, nt:folder and nt:resource
See lib/Doctrine/ODM/PHPCR/Document/

Storing documents in the repository: Id Generator Strategy
----------------------------------------------------------

When defining an ``id`` its possible to choose the generator strategy. The id
is the path where in the phpcr content repository the document should be stored.
By default the assigned id generator is used, which requires manual assignment
of the path to a field annotated as being the Id.
You can tell doctrine to use a different strategy to find the id.

A document id can be defined by the Nodename and the ParentDocument annotations.
The resulting id will be the id of the parent concatenated with '/' and the
Nodename.

If you supply a ParentDocument annotation, the strategy is automatically set to parent. This strategy will check the parent and the name and will fall back to the id field if either is missing.

Currently, there is the "repository" strategy which calls can be used which
calls generateId on the repository class to give you full control how you want
to build the path.

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
        return 'functional/'.$document->title;
    }
}
```

Available annotations
---------------------

<table>
<tr><td> Id:            </td><td>The phpcr path to this node. (see above). For new nodes not using the default strategy, it is populated during the persist() operation.</td></tr>
<tr><td> Uuid:          </td><td>The unique id of this node. (only allowed if node is referenceable). </td></tr>
<tr><td> Version:       </td><td>The version of this node, for versioned nodes. </td></tr>
<tr><td> Node:          </td><td>The PHPCR NodeInterface instance for direct access. This is populated as soon as you register the document with the manager using persist(). (This is subject to be removed when we have mapped all functionality you can get from the PHPCR node.) </td></tr>
<tr><td> Nodename:          </td><td>The name of the PHPCR node (this is the part of the path after the last '/' in the id). This property is read only except on document creation with the parent strategy. For new nodes, it is populated during the persist() operation.</td></tr>
<tr><td> ParentDocument:          </td><td>The parent document of this document. If a type is defined, the document will be of that type, otherwise Doctrine\ODM\PHPCR\Document\Generic will be used. This property is read only except on document creation with the parent strategy.</td></tr>
<tr><td> Child(name=x): </td><td>Map the child with name x to this field. </td></tr>
<tr><td> Children(filter=x): </td><td>Map the collection of children with matching name to this field. Filter is optional and works like the parameter in PHPCR Node::getNodes() (see the <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getNodes()">API</a>)</td></tr>
<tr><td> ReferenceOne(targetDocument="myDocument", weak=false):  </td><td>Refers a document of the type myDocument. The default is a weak reference. By optionaly specifying weak=false you get a hard reference. It is optional to specify the targetDocument, you can reference any document.</td></tr>
<tr><td> ReferenceMany(targetDocument="myDocument", weak=false): </td><td>Same as ReferenceOne except that you can refer many documents with the same document and reference type. If you dont't specify targetDocument you can reference different documents with one property.</td></tr>
<tr><td> Referrers(filterName="x", referenceType=null):     </td><td>A field of this type stores documents that refer this document. filterName is optional. Its value is passed to the name parameter of <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getWeakReferences%28%29">Node::getReferences()<a/> or <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getWeakReferences%28%29">Node::getWeakReferences()</a>. You can also specify an optional referenceType, weak or hard, to only get documents that have either a weak or a hard reference to this document. If you specify null then all documents with weak or hard references are fetched, which is also the default behavior.</td></tr>
<tr><td> String,               <br />
         Binary,               <br />
         Long (alias Int),     <br />
         Decimal,              <br />
         Double (alias Float), <br />
         Date,                 <br />
         Boolean,              <br />
         Name,                 <br />
         Path,                 <br />
         Uri
</td><td>Map node properties to the document. See <a href="http://phpcr.github.com/doc/html/phpcr/propertytype.html">PHPCR\PropertyType</a> for details about the types.</td></tr>
</table>

In the parenthesis after the type, you can specify the name of the PHPCR property
to store the value (name defaults to the php variable name you use), and whether
this is a multivalue property. For example

```php
<?php

/**
 * @PHPCRODM\String(name="categories", multivalue=true)
 */
private $cat;
```

Note that the reference annotations are only possible if your PHPCR implementation supports programmatically setting the uuid property at node creation.


Lifecycle callbacks
-------------------

You can use @PHPCRODM\PostLoad and friends to have doctrine call a method without
parameters on your entity.

You can also define event listeners on the DocumentManager with
```$dm->getEventManager()->addEventListener(array(<events>), listenerclass);```
Your class needs event name methods for the events. They get a parameter of type
Doctrine\Common\EventArgs.
See also http://www.doctrine-project.org/docs/orm/2.0/en/reference/events.html

 * preRemove - occurs before a document is removed from the storage
 * postRemove - occurs after the document has been successfully removed
 * prePersist - occurs before a new document is created in storage
 * postPersist - occurs after a document has been created in storage. generated ids will be available in this state.
 * preUpdate - occurs before an existing document is updated in storage, during the flush operation
 * postUpdate - occurs after an existing document has successfully been updated in storage
 * postLoad - occurs after the document has been loaded from storage
