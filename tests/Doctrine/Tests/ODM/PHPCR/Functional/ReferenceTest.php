<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use Doctrine\Tests\Models\References\RefTestObj;
use Doctrine\Tests\Models\References\RefRefTestObj;
use Doctrine\Tests\Models\References\RefTestPrivateObj;
use Doctrine\Tests\Models\References\RefManyTestObj;
use Doctrine\Tests\Models\References\RefManyTestObjForCascade;

use Doctrine\ODM\PHPCR\PHPCRException;

/**
 * @group functional
 */
class ReferenceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();

        $this->referrerType = 'Doctrine\Tests\Models\References\RefTestObj';
        $this->referencedType = 'Doctrine\Tests\Models\References\RefRefTestObj';
        $this->referrerManyType = 'Doctrine\Tests\Models\References\RefManyTestObj';
        $this->referrerManyForCascadeType = 'Doctrine\Tests\Models\References\RefManyTestObjForCascade';
        $this->weakReferrerType = 'Doctrine\Tests\Models\References\WeakRefTestObj';
        $this->hardReferrerType = 'Doctrine\Tests\Models\References\HardRefTestObj';
        $this->referrerDifType = 'Doctrine\Tests\Models\References\RefDifTestObj';

        $this->session = $this->dm->getPhpcrSession();
        $root = $this->session->getNode('/');

        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $this->session->save();
        }

        $this->node = $root->addNode('functional');

        $this->session->save();
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
        $this->assertEquals($this->session->getNode('/functional')->getNode('refRefTestObj')->getProperty('name')->getString(), 'referenced');

        $this->assertTrue($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));
        $this->assertEquals($this->session->getNode('/functional')->getNode('refTestObj')->getProperty('reference')->getValue(), $this->session->getNode('/functional')->getNode('refRefTestObj'));

        $this->assertEquals($this->session->getNode('/functional')->getProperty('refTestObj/reference')->getString(), $this->session->getNode('/functional')->getNode('refRefTestObj')->getIdentifier());
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
        $this->assertEquals($this->session->getNode('/functional')->getNode('refRefTestObj')->getProperty('name')->getString(), 'referenced');

        $this->assertTrue($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));
        $this->assertEquals($this->session->getNode('/functional')->getNode('refTestObj')->getProperty('reference')->getValue(), $this->session->getNode('/functional')->getNode('refRefTestObj'));

        $this->assertEquals($this->session->getNode('/functional')->getProperty('refTestObj/reference')->getString(), $this->session->getNode('/functional')->getNode('refRefTestObj')->getIdentifier());

        $ref = $this->dm->find('Doctrine\Tests\Models\References\RefTestPrivateObj', '/functional/refTestObj');
        $refref = $ref->getReference();

        $this->assertNotNull($refref);
        $this->assertEquals('/functional/refRefTestObj', $refref->id);
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testReferenceNonReferenceable()
    {
        $refTestObj = new RefTestPrivateObj();
        $refRefTestObj = new \Doctrine\Tests\Models\References\NonRefTestObj();

        $refTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

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
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCreateManyNoArrayError()
    {
        $refManyTestObj = new RefManyTestObj();
        $refRefTestObj = new RefRefTestObj();

        $refManyTestObj->id = "/functional/refTestObj";
        $refRefTestObj->id = "/functional/refRefTestObj";
        $refRefTestObj->name = "referenced";

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
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
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
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));
    }

    public function testCreateWithoutManyRef()
    {
        $refTestObj = new RefManyTestObj();
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refManyTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refManyTestObj')->hasProperty('references'));
    }

    public function testCreateAddRefLater()
    {
        $refTestObj = new RefTestObj();
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $referrer->reference = new RefRefTestObj();
        $referrer->reference->id = '/functional/refRefTestObj';
        $referrer->reference->name = 'referenced';

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->session->getNode('/functional')->getNode('refTestObj')->hasProperty('reference'));
        $this->assertEquals($this->session->getNode('/functional')->getNode('refTestObj')->getProperty('reference')->getValue(), $this->session->getNode('/functional')->getNode('refRefTestObj'));

        $this->assertEquals($this->session->getNode('/functional')->getProperty('refTestObj/reference')->getString(), $this->session->getNode('/functional')->getNode('refRefTestObj')->getIdentifier());
    }

    public function testCreateAddManyRefLater()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->name = 'referrer';
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

        $this->assertTrue($this->session->getNode('/functional')->getNode('refManyTestObj')->hasProperty('references'));

        $this->assertEquals(count($this->session->getNode('/functional')->getProperty('refManyTestObj/references')->getString()), $max);

        $refnode = $this->session->getNode('/functional')->getNode('refManyTestObj');
        foreach ($refnode->getProperty('references')->getValue() as $referenced) {
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

        $this->assertNotEquals($this->session->getNode('/functional')->getNode('refTestObj')->getProperty('reference')->getValue()->getProperty('name')->getString(), "referenced");
        $this->assertEquals($this->session->getNode('/functional')->getNode('refTestObj')->getProperty('reference')->getValue()->getProperty('name')->getString(), "referenced changed");
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
        foreach ($this->session->getNode('/functional')->getNode('refManyTestObj')->getProperty('references')->getValue() as  $node) {
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
        foreach ($this->session->getNode('/functional')->getNode('refManyTestObj')->getProperty('references')->getValue() as  $node) {
            if ($i != $pos) {
                $this->assertEquals($node->getProperty('name')->getValue(), "refRefTestObj$i");
            } else {
                $this->assertEquals($referrer->references[$pos]->name, "new name");
            }
            $i++;
        }
        $this->assertEquals($i, $max);
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
            $this->assertEquals($this->session->getNode('/functional')->getPropertyValue("refRefTestObj$i/name"), "refRefTestObj$i");
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
        $this->assertEquals($this->session->getNode('/functional/refRefTestObj')->getProperty('name')->getString(), 'referenced changed');
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
            $this->assertEquals($this->session->getNode("/functional/refRefTestObj$i")->getPropertyValue("name"), "new name $i");
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
        $weakRefTestObj = new \Doctrine\Tests\Models\References\WeakRefTestObj();
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
        $this->assertFalse($this->session->getNode('/functional/')->hasNode('refRefTestObj'));
    }

    public function testHardReferenceDelete()
    {
        $hardRefTestObj = new \Doctrine\Tests\Models\References\HardRefTestObj();
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
        $hardRefTestObj = new \Doctrine\Tests\Models\References\HardRefTestObj();
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
        $this->assertFalse($this->session->getNode('/functional/')->hasNode('refRefTestObj'));
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

        $this->assertEquals($max, count($referrer->references));

        $refnode = $this->session->getNode('/functional')->getNode('refManyTestObj');
        foreach ($refnode->getProperty('references')->getValue() as $referenced) {
            $this->assertTrue($referenced->hasProperty('name'));
        }
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

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $names = array();
        for ($i = 0; $i < $max; $i++) {
            if ($i != $pos) {
                $names[] = "refRefTestObj$i";
            }
        }

        $this->assertEquals(count($names), $max - 1);

        $i = 0;
        foreach ($this->session->getNode('/functional')->getNode('refManyTestObj')->getProperty('references')->getValue() as  $node) {
            if ($i != $pos) {
                $this->assertTrue(in_array($node->getProperty('name')->getValue(), $names));
            }
            $i++;
        }
        $this->assertEquals($i, $max - 1);
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
            $this->assertEquals($reference->name, "new name $i");
            $i++;
        }
    }

    public function testCreateCascade()
    {
        $referrer = new RefTestObj();
        $referrer->id = "/functional/refTestObj";

        $refCascadeTestObj = new \Doctrine\Tests\Models\References\RefCascadeTestObj();
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

        $this->assertTrue($this->session->getNode("/functional/refTestObj")->hasProperty("reference"));
        $this->assertTrue($this->session->getNode("/functional/refCascadeTestObj")->hasProperty("reference"));

        $this->assertEquals($this->session->getNode("/functional/refTestObj")->getProperty("reference")->getString(),
                            $this->session->getNode("/functional/refCascadeTestObj")->getIdentifier());
        $this->assertEquals($this->session->getNode("/functional/refCascadeTestObj")->getProperty("reference")->getString(),
                            $this->session->getNode("/functional/refRefTestObj")->getIdentifier());
    }

    public function testCreateManyCascade()
    {
        $refManyTestObjForCascade = new RefManyTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyTestObjForCascade";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefCascadeManyTestObj = new \Doctrine\Tests\Models\References\RefCascadeManyTestObj();
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
                $this->assertEquals($this->session->getNode("/functional/refRefTestObj$j$i")->getPropertyValue("name"), "refRefTestObj$j$i");
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
            $newRefCascadeManyTestObj = new \Doctrine\Tests\Models\References\RefCascadeManyTestObj();
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

        $this->assertEquals($this->session->getNode("/functional/refRefTestObj$pos1$pos2")->getPropertyValue("name"), "new name");
    }

    public function testManyCascadeDeleteOne()
    {
        $refManyTestObjForCascade = new RefManyTestObjForCascade();
        $refManyTestObjForCascade->id = "/functional/refManyTestObjForCascade";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newRefCascadeManyTestObj = new \Doctrine\Tests\Models\References\RefCascadeManyTestObj();
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
        $this->assertEquals(count($this->session->getNode("/functional/refCascadeManyTestObj$pos1")->getProperty("references")->getString()), $max);
    }

    public function testRefDifTypes()
    {
        $refDifTestObj = new \Doctrine\Tests\Models\References\RefDifTestObj();
        $refDifTestObj->id = "/functional/refDifTestObj";

        $referenceType1 = new \Doctrine\Tests\Models\References\RefType1TestObj();
        $referenceType1->id = "/functional/refType1TestObj";
        $referenceType1->name = "type1";
        $refDifTestObj->referenceType1 = $referenceType1;

        $referenceType2 = new \Doctrine\Tests\Models\References\RefType2TestObj();
        $referenceType2->id  = "/functional/refType2TestObj";
        $referenceType2->name = "type2";
        $refDifTestObj->referenceType2 = $referenceType2;

        $this->dm->persist($refDifTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerDifType, "/functional/refDifTestObj");

        $this->assertTrue(($referrer->referenceType1 instanceof \Doctrine\Tests\Models\References\RefType1TestObj));
        $this->assertTrue(($referrer->referenceType2 instanceof \Doctrine\Tests\Models\References\RefType2TestObj));

        $this->assertEquals($referrer->referenceType1->name, "type1");
        $this->assertEquals($referrer->referenceType2->name, "type2");
    }

    public function testRefDifTypesChangeBoth()
    {
        $refDifTestObj = new \Doctrine\Tests\Models\References\RefDifTestObj();
        $refDifTestObj->id = "/functional/refDifTestObj";

        $referenceType1 = new \Doctrine\Tests\Models\References\RefType1TestObj();
        $referenceType1->id = "/functional/refType1TestObj";
        $referenceType1->name = "type1";
        $refDifTestObj->referenceType1 = $referenceType1;

        $referenceType2 = new \Doctrine\Tests\Models\References\RefType2TestObj();
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

        $this->assertEquals($this->session->getNode("/functional/refType1TestObj")->getPropertyValue('name'), "new name 1");
        $this->assertEquals($this->session->getNode("/functional/refType2TestObj")->getPropertyValue('name'), "new name 2");
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
        $this->assertEquals($ins0->name, "1 new name");
        $this->assertEquals(spl_object_hash($ins0), spl_object_hash($ins1->reference));
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
        $this->assertEquals($i, 5);

        $this->dm->flush();

        $i = 0;
        foreach ($documents as $document) {
            $this->assertEquals($document->name, "new name $i");
            $i++;
        }
        $this->assertEquals($i, 5);
    }
}
