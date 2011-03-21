PHPCR ODM for Doctrine2
=======================

Current Status
--------------

* (very) basic CRUD is implemented
* metadata reading implemented for annotations
* there is a symfony2 bundle available in symfony core.

Todo
----

* fix the tests that fail
* figure out how we can do relations in a sane way

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

        // fetching a document by path
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
         * @phpcr:String()
         */
        public $title;

        /**
         * @phpcr:String()
         */
        public $content;
    }

Available annotations are
<table>
<tr><td> @Path:       </td><td>The phpcr path to this node. </td></tr>
<tr><td> @Node:       </td><td>The phpcr NodeInterface instance for direct access. </td></tr>
<tr><td> @Id:         </td><td>The unique id of this node. (only allowed if node is referenceable). </td></tr>
<tr><td> @Version:    </td><td>The version of this node, for versioned nodes. </td></tr>
<tr><td> @Property:   </td><td>Any property of the node, without specified type. </td></tr>
<tr><td> @Boolean,    <br />
         @Int,        <br />
         @Long,       <br />
         @Float,      <br />
         @String,     <br />
         @Date,       <br />
         @Binary,     <br />
         @ArrayField: </td><td>Typed property</td></tr>
</table>

TODO: References and child / embed annotations.
