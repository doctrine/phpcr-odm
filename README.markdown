PHPCR ODM for Doctrine2
=======================

Current Status
--------------

* usable for basic tasks
* not yet really performance optimized
* pre alpha stage

TODO
----

* update to Doctrine\Common 3.0.x annotations
* complete mapping for relations (parent, references), then remove the node mapping
* ensure that no Jackalope specific classes are used (especially relevant for the tests)
* add support for SQL/OQM
* write documentation
* expand test suite

Getting Started
---------------

 0. Install jackrabbit according to https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr%3Amanaged
        You need a patched jackrabbit and run the command to register types.

 1. Define one of those mapping drivers

        // Annotation driver
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\PHPCR\Mapping\\');
        // This line defines the jcr namespace, so you can annotate with @jcr:Property for example instead of just @Property
        $reader->setAnnotationNamespaceAlias('Doctrine\ODM\PHPCR\Mapping\\', 'jcr');
        $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader, array('/path/to/your/document/classes'));

        // Xml driver
        $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver(array('/path/to/your/mapping/files'));

        // Yaml driver
        $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver(array('/path/to/your/mapping/files'));

 2. Initialize a Jackalope session

        $repository = new \Jackalope\Repository('http://localhost:8080/server');
        $credentials = new \PHPCR\SimpleCredentials('user', 'pass');
        $session = $repository->login($credentials, 'your_workspace');

 3. Initialize the DocumentManager

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($driver);
        $config->setPhpcrSession($session);

        $dm = new \Doctrine\ODM\PHPCR\DocumentManager($config);

 4. Example usage

        // fetching a document by JCR path (id in PHPCR ODM lingo)
        $user = $dm->getRepository('Namespace\For\Document\User')->find('/bob');

        // create a new document
        $newUser = new \Namespace\For\Document\User();
        $newUser->username = 'Timmy';
        $newUser->email = 'foo@example.com';
        $newUser->path = '/timmy';
        $dm->persist($newUser);

        // store all changes, insertions, etc.
        $dm->flush();


Document Classes
----------------

You write your own document classes that will be mapped to and from the phpcr database by doctrine. The documents are usually simple

    <?php
    namespace Acme\SampleBundle\Document;
    /**
     * @phpcr:Document(alias="mydocument")
     */
    class MyDocument
    {
        /**
         * @phpcr:Id()
         */
        public $path;
        /**
         * @phpcr:String()
         */
        public $title;

        /**
         * @phpcr:String()
         */
        public $content;
    }

Note that there are basic Document classes for the standard PHPCR node types nt:file, nt:folder and nt:resource
See lib/Doctrine/ODM/PHPCR/Document/

Storing documents in the repository: Id Generator Strategy
----------------------------------------------------------

When defining an ``id`` its possible to choose the generator strategy. The id
is the path where in the phpcr content repository the document should be stored.
By default the assigned id generator is used, which requires manual assignment
of the path to a property annotated as being the Id.
You can tell doctrine to use a different strategy to find the id.

Currently, there is the "repository" strategy which calls can be used which
calls generateId on the repository class to give you full control how you want
to build the path.

    namespace Acme\SampleBundle\Document;

    use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
    use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;

    /**
     * @Document(repositoryClass="Acme\SampleBundle\DocumentRepository", alias="document")
     */
    class Document
    {
        /** @Id(strategy="repository") */
        public $id;
        /** @String(name="title") */
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

Available annotations
---------------------

<table>
<tr><td> Id:            </td><td>The phpcr path to this node. (see above)</td></tr>
<tr><td> Uuid:          </td><td>The unique id of this node. (only allowed if node is referenceable). </td></tr>
<tr><td> Version:       </td><td>The version of this node, for versioned nodes. </td></tr>
<tr><td> Node:          </td><td>The PHPCR NodeInterface instance for direct access. (This is subject to be removed when we have mapped all functionality you can get from the PHPCR node. </td></tr>
<tr><td> Child(name=x): </td><td>Map the child with name x to this property. </td></tr>
<tr><td> Children(filter=x): </td><td>Map the collection of children with matching name to this property. Filter is optional and works like the parameter in PHPCR Node::getNodes() (see the <a href="http://phpcr.github.com/doc/html/phpcr/nodeinterface.html#getNodes()">API</a>)</td></tr>
<tr><td> Property:      </td><td>A property of the node, without specified type. </td></tr>
<tr><td> Boolean,    <br />
         Int,        <br />
         Long,       <br />
         Float,      <br />
         String,     <br />
         Date,       <br />
         Binary,     <br />
         ArrayField: </td><td>Typed property</td></tr>
</table>

In the parenthesis after the type, you can specify the name of the PHPCR field
to store the value (name defaults to the php variable name you use), and whether
this is a multivalue property. For example
/**
 * @phpcr:String(name="categories", multivalue=true)
 */
private $cat;

Lifecycle callbacks
-------------------

If you put the annotation @HasLifecycleCallbacks into the document class doc,
you can use @PostLoad and friends to have doctrine call a method without
parameters on your entity.

You can also define event listeners on the DocumentManager with
$dm->getEventManager()->addEventListener(array(<events>), listenerclass);
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
