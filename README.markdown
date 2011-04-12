PHPCR ODM for Doctrine2
=======================

Current Status
--------------

* (very) basic CRUD is implemented
* metadata reading implemented for annotations
* there is a symfony2 bundle available at https://github.com/symfony-cmf/DoctrinePHPCRBundle

TODO
----

* implement the relations (children, parent, references). see https://github.com/doctrine/phpcr-odm/pull/4
* use the mixin phpcr:alias instead of the _doctrine_alias property. see https://github.com/doctrine/phpcr-odm/pull/4
* fix tests to only depend on PHPCR but not on any Jackalope specific classes
* improve event listener markup doc.

Notes
-----

* The type of the document is stored in each repository node (stored as _doctrine_alias for the moment)

Getting Started
---------------

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
        $dm->persist($newUser, '/timmy');

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
<tr><td> Id:         </td><td>The phpcr path to this node. (see above)</td></tr>
<tr><td> Node:       </td><td>The phpcr NodeInterface instance for direct access. </td></tr>
<tr><td> Uuid:         </td><td>The unique id of this node. (only allowed if node is referenceable). </td></tr>
<tr><td> Version:    </td><td>The version of this node, for versioned nodes. </td></tr>
<tr><td> Property:   </td><td>Any property of the node, without specified type. </td></tr>
<tr><td> Boolean,    <br />
         Int,        <br />
         Long,       <br />
         Float,      <br />
         String,     <br />
         Date,       <br />
         Binary,     <br />
         ArrayField: </td><td>Typed property</td></tr>
</table>

If you give the document @HasLifecycleCallbacks then you can use @PostLoad and friends to have doctrine call a method without parameters on your entity.

You can also define event listeners on the DocumentManager with
$dm->getEventManager()->addEventListener(array(<events>), listenerclass);
Your class needs event name methods for the events. They get a parameter of type Doctrine\Common\EventArgs.
See also http://www.doctrine-project.org/docs/orm/2.0/en/reference/events.html

 * preRemove - occurs before a document is removed from the storage
 * postRemove - occurs after the document has been successfully removed
 * prePersist - occurs before a new document is created in storage
 * postPersist - occurs after a document has been created in storage. generated ids will be available in this state.
 * preUpdate - occurs before an existing document is updated in storage, during the flush operation
 * postUpdate - occurs after an existing document has successfully been updated in storage
 * postLoad - occurs after the document has been loaded from storage
