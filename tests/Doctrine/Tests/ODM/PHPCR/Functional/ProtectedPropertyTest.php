<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Jackalope\Session;
use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\UnsupportedRepositoryOperationException;

/**
 * @see http://www.doctrine-project.org/jira/browse/PHPCR-78
 * @group functional
 */
class ProtectedPropertyTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->resetFunctionalNode($this->dm);

        $session = $this->dm->getPhpcrSession();
        if (!$session instanceof Session) {
            $this->markTestSkipped('Not a Jackalope session');
        }

        $cnd = <<<'CND'
<test='http://test.fr'>
[test:protected_property_test] > nt:hierarchyNode
  - reference (REFERENCE)
  - changeme (STRING)
CND;

        $cnd2 = <<<'CND'
<test='http://test.fr'>
[test:protected_property_test2] > nt:hierarchyNode
  - reference (REFERENCE)
  - reference2 (REFERENCE)
CND;

        $ntm = $session->getWorkspace()->getNodeTypeManager();

        try {
            $ntm->registerNodeTypesCnd($cnd, true);
            $ntm->registerNodeTypesCnd($cnd2, true);
        } catch (UnsupportedRepositoryOperationException $e) {
            $this->markTestSkipped('CND parsing not supported');
        }
    }

    public function testPersistDocumentWithReferenceAndProtectedProperty(): void
    {
        $object = new ProtectedPropertyTestObj();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $this->dm->clear();
        } catch (ConstraintViolationException $e) {
            $this->fail(sprintf('A ConstraintViolationException has been thrown when persisting document ("%s").', $e->getMessage()));
        }

        $this->assertTrue(true);
    }

    public function testPersistDocumentWithSeveralReferencesAndProtectedProperty(): void
    {
        $object = new ProtectedPropertyTestObj2();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $this->dm->clear();
        } catch (ConstraintViolationException $e) {
            $this->fail(sprintf('A ConstraintViolationException has been thrown when persisting document ("%s").', $e->getMessage()));
        }

        $this->assertTrue(true);
    }

    public function testModificationWithProtectedProperty(): void
    {
        $object = new ProtectedPropertyTestObj();
        $object->id = '/functional/pp';

        try {
            $this->dm->persist($object);
            $this->dm->flush();
            $object->changeme = 'changed';
            $this->dm->flush();
            $this->dm->clear();
        } catch (ConstraintViolationException $e) {
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

    /** @PHPCRODM\Field(type="date", property="jcr:created") */
    public $created;

    /** @PHPCRODM\Field(type="string", property="jcr:createdBy") */
    public $createdBy;

    /** @PHPCRODM\Field(type="string", nullable=true) */
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

    /** @PHPCRODM\Field(type="date", property="jcr:created") */
    public $created;

    /** @PHPCRODM\Field(type="string", property="jcr:createdBy") */
    public $createdBy;
}
