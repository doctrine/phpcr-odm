<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\Models\References\RefCascadeManyTestObj;
use Doctrine\Tests\Models\References\RefCascadeTestObj;

use Doctrine\Tests\Models\References\HardRefTestObj;
use Doctrine\Tests\Models\References\WeakRefTestObj;
use Doctrine\Tests\Models\References\NonRefTestObj;
use Doctrine\Tests\Models\References\RefType2TestObj;
use Doctrine\Tests\Models\References\RefType1TestObj;
use Doctrine\Tests\Models\References\RefDifTestObj;
use Doctrine\Tests\Models\References\RefTestObj;
use Doctrine\Tests\Models\References\RefTestObjByPath;
use Doctrine\Tests\Models\References\RefRefTestObj;
use Doctrine\Tests\Models\References\RefTestPrivateObj;
use Doctrine\Tests\Models\References\RefManyTestObj;
use Doctrine\Tests\Models\References\RefManyTestObjForCascade;
use Doctrine\Tests\Models\References\RefManyWithParentTestObjForCascade;
use Doctrine\Tests\Models\References\ParentTestObj;

use PHPCR\Util\UUIDHelper;

/**
 * @group functional
 */
class ReferenceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    private $referrerType = 'Doctrine\Tests\Models\References\RefTestObj';
    private $referencedType = 'Doctrine\Tests\Models\References\RefRefTestObj';
    private $referrerManyType = 'Doctrine\Tests\Models\References\RefManyTestObj';
    private $referrerManyForCascadeType = 'Doctrine\Tests\Models\References\RefManyTestObjForCascade';
    private $hardReferrerType = 'Doctrine\Tests\Models\References\HardRefTestObj';
    private $referrerDifType = 'Doctrine\Tests\Models\References\RefDifTestObj';
    private $referrerManyWithParentForCascadeType = 'Doctrine\Tests\Models\References\RefManyWithParentTestObjForCascade';

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreate()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $refRefTestNode = $this->session->getNode('/functional/refRefTestObj');
        $this->assertEquals('referenced', $refRefTestNode->getProperty('name')->getString());

        $refTestNode = $this->session->getNode('/functional/refTestObj');
        $this->assertTrue($refTestNode->hasProperty('myReference'));

        $this->assertEquals($refRefTestNode->getIdentifier(), $refTestNode->getProperty('myReference')->getString());
        $this->assertTrue(UUIDHelper::isUUID($refTestNode->getProperty('myReference')->getString()));
    }

    public function testFindByUUID()
    {
        $refTestObj = new RefRefTestObj();
        $refTestObj->id = "/functional/refRefTestObj";
        $refTestObj->name = 'referrer';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $node = $this->session->getNode($refTestObj->id);

        $document = $this->dm->find($this->referencedType, $node->getIdentifier());
        $this->assertInstanceOf($this->referencedType, $document);

        $documents = $this->dm->findMany($this->referencedType, array($node->getIdentifier()));
        $this->assertInstanceOf($this->referencedType, $documents->first());
    }

    public function testCreateByPath()
    {
        $refTestObj = new RefTestObjByPath();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $refRefTestNode = $this->session->getNode('/functional/refRefTestObj');
        $this->assertEquals($refRefTestNode ->getProperty('name')->getString(), 'referenced');

        $refTestNode = $this->session->getNode('/functional/refTestObj');
        $this->assertTrue($refTestNode->hasProperty('reference'));
        $this->assertEquals($refTestNode->getProperty('reference')->getValue(), "/functional/refRefTestObj");

        $this->assertEquals($refRefTestNode->getPath(), $refTestNode->getProperty('reference')->getString());
    }

    public function testCreatePrivate()
    {
        $refTestObj = new RefTestPrivateObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->setReference($refRefTestObj);

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $refRefTestNode = $this->session->getNode('/functional/refRefTestObj');
        $this->assertEquals($refRefTestNode->getProperty('name')->getString(), 'referenced');

        $refTestNode = $this->session->getNode('/functional/refTestObj');
        $this->assertTrue($refTestNode->hasProperty('reference'));
        $this->assertEquals($refRefTestNode->getIdentifier(), $refTestNode->getProperty('reference')->getString());

        $ref = $this->dm->find('Doctrine\Tests\Models\References\RefTestPrivateObj', '/functional/refTestObj');
        $refref = $ref->getReference();

        $this->assertNotNull($refref);
        $this->assertEquals('/functional/refRefTestObj', $refref->id);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testReferenceNonReferenceable()
    {
        $refTestObj = new RefTestPrivateObj();
        $refRefTestObj = new NonRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";

        $refTestObj->setReference($refRefTestObj);

        $this->dm->persist($refTestObj);
        try {
            $this->dm->flush();
        } catch (PHPCRException $e) {
            $this->assertContains('Referenced document Doctrine\Tests\Models\References\NonRefTestObj is not referenceable', $e->getMessage());
            throw $e;
        }
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCreateManyNoArrayError()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";

        $refManyTestObj->references = $refRefTestObj;

        $this->dm->persist($refManyTestObj);
        try {
            $this->dm->flush();
        } catch (PHPCRException $e) {
            $this->assertContains('Referenced document is not stored correctly in a reference-many property.', $e->getMessage());
            throw $e;
        }
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCreateOneArrayError()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = array($refRefTestObj);

        $this->dm->persist($refTestObj);
        try {
            $this->dm->flush();
        } catch (PHPCRException $e) {
            $this->assertContains('Referenced document is not stored correctly in a reference-one property.', $e->getMessage());
            throw $e;
        }
    }

    public function testCreateWithoutRef()
    {
        $refTestObj = new RefTestObj();
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));
    }

    public function testCreateWithoutManyRef()
    {
        $refTestObj = new RefManyTestObj();
        $refTestObj->id = '/functional/refManyTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refManyTestObj')->hasProperty('myReferences'));
    }

    public function testCreateAddRefLater()
    {
        $refTestObj = new RefTestObj();
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('myReference'));

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $referrer->reference = new RefRefTestObj();
        $referrer->reference->id = '/functional/refRefTestObj';
        $referrer->reference->name = 'referenced';

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $refTestNode = $this->session->getNode('/functional/refTestObj');
        $refRefTestNode = $this->session->getNode('/functional')->getNode('refRefTestObj');
        $this->assertTrue($refTestNode->hasProperty('myReference'));
        $this->assertEquals($refTestNode->getProperty('myReference')->getString(), $refRefTestNode->getIdentifier());
    }

    public function testCreateAddManyRefLater()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = '/functional/refManyTestObj';

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refManyTestObj')->hasProperty('references'));

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $referrer->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $refManyNode = $this->session->getNode('/functional/refManyTestObj');
        $this->assertTrue($refManyNode->hasProperty('myReferences'));

        $this->assertCount($max, $refManyNode->getProperty('myReferences')->getString());

        foreach ($refManyNode->getProperty('myReferences')->getNode() as $referenced) {
            $this->assertTrue($referenced->hasProperty('name'));
        }
    }

    public function testUpdate()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $referrer->reference->setName('referenced changed');

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $node = $this->session->getNode('/functional/refTestObj')->getPropertyValue('myReference');
        $this->assertInstanceOf('PHPCR\\NodeInterface', $node);
        $this->assertEquals('referenced changed', $node->getPropertyValue('name'));
    }

    public function testUpdateMany()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($referrer->references as $reference) {
            $reference->name = "new name ".$i;
            $i++;
        }

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($this->session->getNode('/functional/refManyTestObj')->getProperty('myReferences')->getNode() as  $node) {
            $this->assertEquals($node->getProperty('name')->getValue(), "new name ".$i);
            $i++;
        }
        $this->assertEquals($i, $max);
    }

    public function testUpdateOneInMany()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $pos = 2;

        $referrer->references[$pos]->name = "new name";

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($this->session->getNode('/functional/refManyTestObj')->getProperty('myReferences')->getNode() as  $node) {
            if ($i != $pos) {
                $this->assertEquals("refRefTestObj$i", $node->getProperty('name')->getValue());
            } else {
                $this->assertEquals("new name", $referrer->references[$pos]->name);
            }
            $i++;
        }
        $this->assertEquals($max, $i);
    }

    public function testRemoveReference()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refManyTestObj->references[] = $refRefTestObj;

        $this->assertEquals(1, count($refManyTestObj->references));

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(1, count($refManyTestObj->references));
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        unset($refManyTestObj->references[0]);
        $this->assertEquals(0, count($refManyTestObj->references));
        $this->assertNotNull($refManyTestObj->references);
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertNotNull($refManyTestObj->references);
        $this->assertEquals(0, count($refManyTestObj->references));
    }

    public function testRemoveMultipleReferences()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObjA = new RefRefTestObj();
        $refRefTestObjB = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObjA->id = "/functional/refRefTestObjA";
        $refRefTestObjA->name = "referencedA";
        $refRefTestObjB->id = "/functional/refRefTestObjB";
        $refRefTestObjB->name = "referencedB";

        $refManyTestObj->references[] = $refRefTestObjA;
        $refManyTestObj->references[] = $refRefTestObjB;

        $this->assertEquals(2, count($refManyTestObj->references));

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(2, count($refManyTestObj->references));
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        unset($refManyTestObj->references[0]);
        $this->assertNotNull($refManyTestObj->references);
        $this->assertEquals(1, count($refManyTestObj->references));
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(1, count($refManyTestObj->references));
        unset($refManyTestObj->references[0]);
        $this->assertNotNull($refManyTestObj->references);
        $this->assertEquals(0, count($refManyTestObj->references));
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertNotNull($refManyTestObj->references);
        $this->assertEquals(0, count($refManyTestObj->references));
    }

    public function testRemoveAllReferences()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObjA = new RefRefTestObj();
        $refRefTestObjB = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObjA->id = "/functional/refRefTestObjA";
        $refRefTestObjA->name = "referencedA";
        $refRefTestObjB->id = "/functional/refRefTestObjB";
        $refRefTestObjB->name = "referencedB";

        $refManyTestObj->references[] = $refRefTestObjA;
        $refManyTestObj->references[] = $refRefTestObjB;

        $this->assertEquals(2, count($refManyTestObj->references));

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(2, count($refManyTestObj->references));
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        unset($refManyTestObj->references[0]);
        unset($refManyTestObj->references[1]);
        $this->assertEquals(0, count($refManyTestObj->references));
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(0, count($refManyTestObj->references));
    }

    public function testAddMultipleReferences()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObjA = new RefRefTestObj();
        $refRefTestObjB = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObjA->id = "/functional/refRefTestObjA";
        $refRefTestObjA->name = "referencedA";
        $refRefTestObjB->id = "/functional/refRefTestObjB";
        $refRefTestObjB->name = "referencedB";

        $this->assertEquals(0, count($refManyTestObj->references));

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(0, count($refManyTestObj->references));
        $refManyTestObj->references[] = $refRefTestObjA;
        $this->assertEquals(1, count($refManyTestObj->references));
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(1, count($refManyTestObj->references));
        $refManyTestObj->references[] = $refRefTestObjB;
        $this->assertEquals(2, count($refManyTestObj->references));
        $this->dm->flush();
        $this->dm->clear();

        $refManyTestObj = $this->dm->find(null, "/functional/refTestObj");
        $this->assertEquals(2, count($refManyTestObj->references));
    }

    public function testRemoveReferrer()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->dm->remove($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $testReferrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->assertNull($testReferrer);
        $this->assertTrue($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $this->assertFalse($this->session->getNode('/functional')->hasNode('refTestObj'));
    }

    public function testRemoveReferrerMany()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $this->dm->remove($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $testReferrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->assertNull($testReferrer);
        $this->assertFalse($this->session->getNode('/functional')->hasNode('refTestObj'));

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $this->assertTrue($this->session->getNode('/functional')->hasNode("refRefTestObj$i"));
            $this->assertEquals("refRefTestObj$i", $this->session->getNode('/functional')->getPropertyValue("refRefTestObj$i/name"));
        }
    }

    /**
     * Remove referrer node, but change referenced node before
     */
    public function testRemoveReferrerChangeBefore()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $referrer->reference->setName('referenced changed');

        $this->dm->remove($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $testReferrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->assertNull($testReferrer);
        $this->assertTrue($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $this->assertFalse($this->session->getNode('/functional')->hasNode('refTestObj'));
        $this->assertEquals('referenced changed', $this->session->getNode('/functional/refRefTestObj')->getProperty('name')->getString());
    }

    /**
     * Remove referrer node, but change referenced nodes before
     */
    public function testRemoveReferrerManyChangeBefore()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($referrer->references as $reference ) {
            $reference->name = "new name $i";
            $i++;
        }

        $this->dm->remove($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $testReferrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $this->assertNull($testReferrer);

        for ($i = 0; $i < $max; $i++) {
            $this->assertEquals("new name $i", $this->session->getNode("/functional/refRefTestObj$i")->getPropertyValue("name"));
        }
    }

    public function testDeleteByRef()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->dm->remove($referrer->reference);
        $referrer->reference = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->hasNode('refRefTestObj'));
        $this->assertFalse($this->session->getNode('/functional/refTestObj')->hasProperty('reference'));
    }

    public function testWeakReference()
    {
        $weakRefTestObj = new WeakRefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $weakRefTestObj->id = "/functional/weakRefTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $weakRefTestObj->reference = $refRefTestObj;

        $this->dm->persist($weakRefTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find($this->referencedType, '/functional/refRefTestObj');
        $this->dm->remove($referenced);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode('/functional/weakRefTestObj')->hasProperty('reference'));
        $this->assertFalse($this->session->getNode('/functional')->hasNode('refRefTestObj'));
    }

    public function testHardReferenceDelete()
    {
        $hardRefTestObj = new HardRefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $hardRefTestObj->id = "/functional/hardRefTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $hardRefTestObj->reference = $refRefTestObj;

        $this->dm->persist($hardRefTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find($this->referencedType, '/functional/refRefTestObj');

        $this->setExpectedException('PHPCR\ReferentialIntegrityException');
        $this->dm->remove($referenced);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testHardReferenceDeleteSuccess()
    {
        $hardRefTestObj = new HardRefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $hardRefTestObj->id = "/functional/hardRefTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $hardRefTestObj->reference = $refRefTestObj;

        $this->dm->persist($hardRefTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->hardReferrerType, '/functional/hardRefTestObj');
        $referenced = $this->dm->find($this->referencedType, '/functional/refRefTestObj');

        $referrer->reference = null;
        $this->dm->remove($referenced);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional/hardRefTestObj')->hasProperty('reference'));
        $this->assertFalse($this->session->getNode('/functional')->hasNode('refRefTestObj'));
    }

    public function testReferenceMany()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = '/functional/refManyTestObj';
        $refManyTestObj->name = 'referrer';

        $max = 5;

        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $this->assertCount($max, $referrer->references);

        $refnode = $this->session->getNode('/functional')->getNode('refManyTestObj');
        foreach ($refnode->getProperty('myReferences')->getNode() as $referenced) {
            $this->assertTrue($referenced->hasProperty('name'));
        }
    }

    public function testNoReferenceInitOnFlush()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = '/functional/refManyTestObj';
        $refManyTestObj->name = 'referrer';

        $max = 5;

        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referencing = $this->dm->find('Doctrine\Tests\Models\References\RefManyTestObj', '/functional/refManyTestObj');
        $this->dm->flush();

        $this->assertFalse($referencing->references->isInitialized());
    }

    public function testDeleteOneInMany()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $pos = 2;

        $this->dm->remove($referrer->references[$pos]);
        $referrer->references[$pos] = null;

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $names = array();
        for ($i = 0; $i < $max; $i++) {
            if ($i != $pos) {
                $names[] = "refRefTestObj$i";
            }
        }

        $this->assertCount($max - 1, $names);

        $i = 0;
        foreach ($this->session->getNode('/functional')->getNode('refManyTestObj')->getProperty('myReferences')->getNode() as  $node) {
            if ($i != $pos) {
                $this->assertTrue(in_array($node->getProperty('name')->getValue(), $names));
            }
            $i++;
        }
        $this->assertEquals($max - 1, $i);
    }

    public function testModificationAfterPersist()
    {
        $referrer = new RefTestObj();
        $referenced = new RefRefTestObj();

        $referrer->id = '/functional/refTestObj';
        $referenced->id = "/functional/refRefTestObj";

        $this->dm->persist($referrer);
        $referrer->name = 'Referrer';
        $referrer->reference = $referenced;
        $referenced->name = 'Referenced';

        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $this->assertNotNull($referrer->reference);
        $this->assertEquals('Referenced', $referrer->reference->name);

        $referrer->reference->name = 'Changed';

        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');

        $this->assertNotNull($referrer->reference);
        $this->assertEquals('Changed', $referrer->reference->name);
    }

    public function testModificationManyAfterPersist()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($referrer->references as $reference) {
            $reference->name = "new name $i";
            $i++;
        }
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($referrer->references as $reference) {
            $this->assertEquals("new name $i", $reference->name);
            $i++;
        }
    }

    public function testCreateCascade()
    {
        $referrer = new RefTestObj();
        $referrer->id = "/functional/refTestObj";

        $refCascadeTestObj = new RefCascadeTestObj();
        $refCascadeTestObj->id = "/functional/refCascadeTestObj";
        $refCascadeTestObj->name = "refCascadeTestObj";

        $referrer->reference = $refCascadeTestObj;

        $refRefTestObj = new RefRefTestObj();
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "refRefTestObj";

        $referrer->reference->reference = $refRefTestObj;

        $this->dm->persist($referrer);
        $this->dm->flush();

        $this->assertTrue($this->session->getNode("/functional")->hasNode("refTestObj"));
        $this->assertTrue($this->session->getNode("/functional")->hasNode("refCascadeTestObj"));
        $this->assertTrue($this->session->getNode("/functional")->hasNode("refRefTestObj"));

        $this->assertTrue($this->session->getNode("/functional/refTestObj")->hasProperty("myReference"));
        $this->assertTrue($this->session->getNode("/functional/refCascadeTestObj")->hasProperty("reference"));

        $this->assertEquals(
            $this->session->getNode("/functional/refCascadeTestObj")->getIdentifier(),
            $this->session->getNode("/functional/refTestObj")->getProperty("myReference")->getString()
        );
        $this->assertEquals(
            $this->session->getNode("/functional/refRefTestObj")->getIdentifier(),
            $this->session->getNode("/functional/refCascadeTestObj")->getProperty("reference")->getString()
        );
    }

    public function testCreateManyCascade()
    {
        $refManyTestObjForCascade = new RefManyTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyTestObjForCascade";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefCascadeManyTestObj = new RefCascadeManyTestObj();
            $newRefCascadeManyTestObj->id = "/functional/refCascadeManyTestObj$i";
            $newRefCascadeManyTestObj->name = "refCascadeManyTestObj$i";
            $refManyTestObjForCascade->references[] = $newRefCascadeManyTestObj;
        }

        $j = 0;
        foreach ($refManyTestObjForCascade->references as $reference) {
            for ($i = 0; $i < $max; $i++) {
                $newRefRefTestObj= new RefRefTestObj();
                $newRefRefTestObj->id = "/functional/refRefTestObj$j$i";
                $newRefRefTestObj->name = "refRefTestObj$j$i";
                $reference->references[] = $newRefRefTestObj;
            }
            $j++;
        }

        $this->dm->persist($refManyTestObjForCascade);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode("/functional")->hasNode("refManyTestObjForCascade"));

        $refIds = $this->session->getNode("/functional/refManyTestObjForCascade")->getProperty("references")->getString();
        for ($i = 0; $i < $max; $i++) {
            $this->assertTrue($this->session->getNode("/functional")->hasNode("refCascadeManyTestObj$i"));
            $this->assertTrue($this->session->getNode("/functional/refCascadeManyTestObj$i")->hasProperty('references'));
            $this->assertTrue(in_array($this->session->getNode("/functional/refCascadeManyTestObj$i")->getIdentifier(), $refIds));
        }

        for ($j = 0; $j < $max; $j++) {
            $refIds = $this->session->getNode("/functional/refCascadeManyTestObj$j")->getProperty("references")->getString();
            for ($i = 0; $i < $max; $i++) {
                $this->assertTrue($this->session->getNode("/functional")->hasNode("refRefTestObj$j$i"));
                $this->assertEquals("refRefTestObj$j$i", $this->session->getNode("/functional/refRefTestObj$j$i")->getPropertyValue("name"));
                $this->assertTrue(in_array($this->session->getNode("/functional/refRefTestObj$j$i")->getIdentifier(), $refIds));
            }
        }
    }

    public function testManyCascadeChangeOne()
    {
        $refManyTestObjForCascade = new RefManyTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyTestObjForCascade";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefCascadeManyTestObj = new RefCascadeManyTestObj();
            $newRefCascadeManyTestObj->id = "/functional/refCascadeManyTestObj$i";
            $newRefCascadeManyTestObj->name = "refCascadeManyTestObj$i";
            $refManyTestObjForCascade->references[] = $newRefCascadeManyTestObj;
        }

        $j = 0;
        foreach ($refManyTestObjForCascade->references as $reference) {
            for ($i = 0; $i < $max; $i++) {
                $newRefRefTestObj= new RefRefTestObj();
                $newRefRefTestObj->id = "/functional/refRefTestObj$j$i";
                $newRefRefTestObj->name = "refRefTestObj$j$i";
                $reference->references[] = $newRefRefTestObj;
            }
            $j++;
        }

        $this->dm->persist($refManyTestObjForCascade);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyForCascadeType, '/functional/refManyTestObjForCascade');

        $pos1 = 1;
        $pos2 = 2;

        $referrer->references[$pos1]->references[$pos2]->name = "new name";

        $this->dm->flush();

        $this->assertEquals("new name", $this->session->getNode("/functional/refRefTestObj$pos1$pos2")->getPropertyValue("name"));
    }

    public function testManyCascadeDeleteOne()
    {
        $refManyTestObjForCascade = new RefManyTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyTestObjForCascade";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefCascadeManyTestObj = new RefCascadeManyTestObj();
            $newRefCascadeManyTestObj->id = "/functional/refCascadeManyTestObj$i";
            $newRefCascadeManyTestObj->name = "refCascadeManyTestObj$i";
            $refManyTestObjForCascade->references[] = $newRefCascadeManyTestObj;
        }

        $j = 0;
        foreach ($refManyTestObjForCascade->references as $reference) {
            for ($i = 0; $i < $max; $i++) {
                $newRefRefTestObj= new RefRefTestObj();
                $newRefRefTestObj->id = "/functional/refRefTestObj$j$i";
                $newRefRefTestObj->name = "refRefTestObj$j$i";
                $reference->references[] = $newRefRefTestObj;
            }
            $j++;
        }

        $this->dm->persist($refManyTestObjForCascade);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyForCascadeType, '/functional/refManyTestObjForCascade');

        $pos1 = 1;
        $pos2 = 2;

        $this->dm->remove($referrer->references[$pos1]->references[$pos2]);

        $this->dm->flush();

        $this->assertFalse($this->session->getNode("/functional")->hasNode("refRefTestObj$pos1$pos2"));
        $this->assertTrue($this->session->getNode("/functional/refCascadeManyTestObj$pos1")->hasProperty("references"));
        $this->assertCount($max, $this->session->getNode("/functional/refCascadeManyTestObj$pos1")->getProperty("references")->getString());
    }

    public function testRefDifTypes()
    {
        $refDifTestObj = new RefDifTestObj();
        $refDifTestObj->id = "/functional/refDifTestObj";

        $referenceType1 = new RefType1TestObj();
        $referenceType1->id = "/functional/refType1TestObj";
        $referenceType1->name = "type1";
        $refDifTestObj->referenceType1 = $referenceType1;

        $referenceType2 = new RefType2TestObj();
        $referenceType2->id  = "/functional/refType2TestObj";
        $referenceType2->name = "type2";
        $refDifTestObj->referenceType2 = $referenceType2;

        $this->dm->persist($refDifTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerDifType, "/functional/refDifTestObj");

        $this->assertTrue(($referrer->referenceType1 instanceof RefType1TestObj));
        $this->assertTrue(($referrer->referenceType2 instanceof RefType2TestObj));

        $this->assertEquals("type1", $referrer->referenceType1->name);
        $this->assertEquals("type2", $referrer->referenceType2->name);
    }

    public function testRefDifTypesChangeBoth()
    {
        $refDifTestObj = new RefDifTestObj();
        $refDifTestObj->id = "/functional/refDifTestObj";

        $referenceType1 = new RefType1TestObj();
        $referenceType1->id = "/functional/refType1TestObj";
        $referenceType1->name = "type1";
        $refDifTestObj->referenceType1 = $referenceType1;

        $referenceType2 = new RefType2TestObj();
        $referenceType2->id  = "/functional/refType2TestObj";
        $referenceType2->name = "type2";
        $refDifTestObj->referenceType2 = $referenceType2;

        $this->dm->persist($refDifTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerDifType, "/functional/refDifTestObj");

        $referrer->referenceType1->name = "new name 1";
        $referrer->referenceType2->name = "new name 2";
        $this->dm->flush();

        $this->assertEquals("new name 1", $this->session->getNode("/functional/refType1TestObj")->getPropertyValue('name'));
        $this->assertEquals("new name 2", $this->session->getNode("/functional/refType2TestObj")->getPropertyValue('name'));
    }

    public function testTwoDifferentObjectrefs()
    {
        $refTestObj = new RefTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

        $refTestObj->reference = $refRefTestObj;

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $ins0 = $this->dm->find($this->referencedType, "/functional/refRefTestObj");
        $ins0->name = "0 new name";
        $ins1 = $this->dm->find($this->referrerType, "/functional/refTestObj");
        $ins1->reference->name = "1 new name";

        $this->dm->flush();
        $this->assertEquals("1 new name", $ins0->name);
        $this->assertEquals(spl_object_hash($ins1->reference), spl_object_hash($ins0));
    }

    public function testManyTwoDifferentObjectrefs()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->id = "/functional/refManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefRefTestObj = new RefRefTestObj();
            $newRefRefTestObj->id = "/functional/refRefTestObj$i";
            $newRefRefTestObj->name = "refRefTestObj$i";
            $refManyTestObj->references[] = $newRefRefTestObj;
        }

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $documents = array();
        $hashs = array();
        for ($i = 0; $i < $max; $i++) {
           $doc = $this->dm->find($this->referencedType, "/functional/refRefTestObj$i");
           $documents[] = $doc;
           $hashs[] = spl_object_hash($doc);
        }

        asort($hashs);

        $refDocuments = array();
        $refHashs = array();
        $refDocuments = $this->dm->find($this->referrerManyType, "/functional/refManyTestObj")->references;

        foreach ($refDocuments as $refDoc) {
           $refHashs[] = spl_object_hash($refDoc);
        }

        asort($refHashs);
        $tmp = array_diff($hashs, $refHashs);
        $this->assertTrue(empty($tmp));

        $i = 0;
        foreach ($refDocuments as $refDocument) {
            $refDocument->name = "new name $i";
            $i++;
        }
        $this->assertEquals(5, $i);

        $this->dm->flush();

        $i = 0;
        foreach ($documents as $document) {
            $this->assertEquals("new name $i", $document->name);
            $i++;
        }
        $this->assertEquals(5, $i);
    }

    public function testManyCascadeWithParentDelete()
    {
        $refManyTestObjForCascade = new RefManyWithParentTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyWithParentTestObjForCascade";

        $references = array();
        for ($i = 0; $i < 3; $i++) {
            $newRefCascadeManyTestObj = new ParentTestObj();
            $newRefCascadeManyTestObj->nodename = "ref$i";
            $newRefCascadeManyTestObj->name = "refCascadeWithParentManyTestObj$i";
            $references[] = $newRefCascadeManyTestObj;
        }
        $refManyTestObjForCascade->setReferences($references);

        $this->dm->persist($refManyTestObjForCascade);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode("/functional")->hasNode("refManyWithParentTestObjForCascade"));

        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($this->session->getNode("/functional/refManyWithParentTestObjForCascade")->hasNode("ref$i"));
        }

        $referrer = $this->dm->find($this->referrerManyWithParentForCascadeType, '/functional/refManyWithParentTestObjForCascade');

        $this->dm->remove($referrer);
        $this->dm->flush();

        $this->assertFalse($this->session->getNode("/functional")->hasNode("refManyWithParentTestObjForCascade"));
    }

    public function testCascadeRemoveByCollection()
    {
        $referrerRefManyTestObj = new ReferenceTestObj();
        $referrerRefManyTestObj->id = "/functional/referenceTestObj";
        $referrerRefManyTestObj->reference = array();

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newReferrerTestObj = new ReferenceRefTestObj();
            $newReferrerTestObj->id = "/functional/referenceRefTestObj$i";
            $newReferrerTestObj->name = "referrerTestObj$i";
            $referrerRefManyTestObj->reference[] = $newReferrerTestObj;
        }

        $this->dm->persist($referrerRefManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrered = $this->dm->find(null, "/functional/referenceTestObj");
        $this->assertCount($max, $referrered->reference);
        $referrered->reference->remove(0);
        $referrered->reference->remove(3);
        $this->assertCount($max - 2, $referrered->reference);

        $this->dm->flush();
        $this->dm->clear();

        $referrered = $this->dm->find(null, "/functional/referenceTestObj");
        $this->assertCount($max - 2, $referrered->reference);
    }
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferenceRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers(referringDocument="ReferenceTestObj", referencedBy="reference", cascade={"persist"}) */
    public $referrers;
}

/**
 * @PHPCRODM\Document()
 */
class ReferenceTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceMany(targetDocument="ReferenceRefTestObj", cascade={"persist", "remove"}) */
    public $reference;
}
