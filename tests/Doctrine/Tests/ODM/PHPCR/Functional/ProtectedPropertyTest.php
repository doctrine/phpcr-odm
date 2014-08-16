<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @see http://www.doctrine-project.org/jira/browse/PHPCR-78
 * @group functional
 */
class ProtectedPropertyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $session = $this->dm->getPhpcrSession();
        if (! $session instanceof \Jackalope\Session) {
            $this->markTestSkipped('Not a Jackalope session');
        }

        $cnd = <<<CND
<test='http://test.fr'>
[test:protected_property_test] > nt:hierarchyNode
  - reference (REFERENCE)
  - changeme (STRING)
CND;

        $cnd2 = <<<CND
<test='http://test.fr'>
[test:protected_property_test2] > nt:hierarchyNode
  - reference (REFERENCE)
  - reference2 (REFERENCE)
CND;

        $ntm = $session->getWorkspace()->getNodeTypeManager();
        try {
            $ntm->registerNodeTypesCnd($cnd, true);
            $ntm->registerNodeTypesCnd($cnd2, true);
        } catch (\PHPCR\UnsupportedRepositoryOperationException $e) {
            $this->markTestSkipped('CND parsing not supported');
        }
    }

    public function testPersistDocumentWithReferenceAndProtectedProperty()
    {
        $object = new ProtectedPropertyTestObj();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $this->dm->clear();
        } catch (\PHPCR\NodeType\ConstraintViolationException $e) {
            $this->fail(sprintf('A ConstraintViolationException has been thrown when persisting document ("%s").', $e->getMessage()));
        }

        $this->assertTrue(true);
    }

    public function testPersistDocumentWithSeveralReferencesAndProtectedProperty()
    {
        $object = new ProtectedPropertyTestObj2();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $this->dm->clear();
        } catch (\PHPCR\NodeType\ConstraintViolationException $e) {
            $this->fail(sprintf('A ConstraintViolationException has been thrown when persisting document ("%s").', $e->getMessage()));
        }

        $this->assertTrue(true);
    }

    public function testModificationWithProtectedProperty()
    {
        $object = new ProtectedPropertyTestObj();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $object->changeme = 'changed';
            $this->dm->flush();
            $this->dm->clear();
        } catch (\PHPCR\NodeType\ConstraintViolationException $e) {
            $this->fail(sprintf('A ConstraintViolationException has been thrown when persisting document ("%s").', $e->getMessage()));
        }

        $this->assertTrue(true);
    }
}

/**
 * @PHPCRODM\Document(nodeType="test:protected_property_test")
 */
class ProtectedPropertyTestObj
{
    /** @PHPCRODM\Id() */
    public $id;

    /** @PHPCRODM\ReferenceOne(strategy="hard") */
    public $reference;

    /** @PHPCRODM\Date(property="jcr:created") */
    public $created;

    /** @PHPCRODM\String(property="jcr:createdBy") */
    public $createdBy;

    /** @PHPCRODM\String(nullable=true) */
    public $changeme;
}

/**
 * @PHPCRODM\Document(nodeType="test:protected_property_test2")
 */
class ProtectedPropertyTestObj2
{
    /** @PHPCRODM\Id() */
    public $id;

    /** @PHPCRODM\ReferenceOne(strategy="hard") */
    public $reference;

    /** @PHPCRODM\ReferenceOne(strategy="hard") */
    public $reference2;

    /** @PHPCRODM\Date(property="jcr:created") */
    public $created;

    /** @PHPCRODM\String(property="jcr:createdBy") */
    public $createdBy;
}
