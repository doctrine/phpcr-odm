<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
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
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\ProtectedPropertyTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $cnd = <<<CND
<test='http://test.fr'>
[test:protected_property_test] > nt:hierarchyNode
  - reference (REFERENCE)
CND;

        $session = $this->dm->getPhpcrSession();
        if (! $session instanceof \Jackalope\Session) {
            $this->markTestSkipped('Not a Jackalope session');
        }
        $ntm = $session->getWorkspace()->getNodeTypeManager();
        $ntm->registerNodeTypesCnd($cnd, true);
    }

    /**
     * @see http://www.doctrine-project.org/jira/browse/PHPCR-78
     */
    public function testPersistDocumentReferenceAndProtectedProperty()
    { 
        $parent = new ProtectedPropertyTestObj();
        $parent->id = '/functional/pp';
        $parent->content = 'foo';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();
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

    /** @PHPCRODM\Date(name="jcr:created") */
    public $created;

    /** @PHPCRODM\String(name="jcr:createdBy") */
    public $createdBy;
}
