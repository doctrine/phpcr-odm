<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class ReferenceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();

        $this->referrerType = 'Doctrine\Tests\ODM\PHPCR\Functional\RefTestObj';
        $this->referencedType = 'Doctrine\Tests\ODM\PHPCR\Functional\RefRefTestObj';
        $this->referrerManyType = 'Doctrine\Tests\ODM\PHPCR\Functional\RefManyTestObj';
        $this->weakReferrerType = 'Doctrine\Tests\ODM\PHPCR\Functional\WeakRefTestObj';
        $this->hardReferrerType = 'Doctrine\Tests\ODM\PHPCR\Functional\HardRefTestObj';

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


        $this->assertTrue($this->node->hasNode('refRefTestObj'));
        $this->assertEquals($this->node->getNode('refRefTestObj')->getProperty('name')->getString(), 'referenced');

        $this->assertTrue($this->node->getNode('refTestObj')->hasProperty('reference'));
        $this->assertEquals($this->node->getNode('refTestObj')->getProperty('reference')->getValue(), $this->node->getNode('refRefTestObj'));
    }

    public function testCreateWithoutRef()
    {
        $refTestObj = new RefTestObj();
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('refTestObj')->hasProperty('reference'));
    }

    public function testCreateWithoutManyRef()
    {
        $refTestObj = new RefManyTestObj();
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refManyTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('refManyTestObj')->hasProperty('references'));
    }

    public function testCreateAddRefLater()
    {
        $refTestObj = new RefTestObj();
        $refTestObj->name = 'referrer';
        $refTestObj->id = '/functional/refTestObj';

        $this->dm->persist($refTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('refTestObj')->hasProperty('reference'));

        $referrer = $this->dm->find($this->referrerType, '/functional/refTestObj');
        $referrer->reference = new RefRefTestObj();
        $referrer->reference->id = '/functional/refRefTestObj';
        $referrer->reference->name = 'referenced';

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('refTestObj')->hasProperty('reference'));
        $this->assertEquals($this->node->getNode('refTestObj')->getProperty('reference')->getValue(), $this->node->getNode('refRefTestObj'));
    }

    public function testCreateAddManyRefLater()
    {
        $refManyTestObj = new RefManyTestObj();
        $refManyTestObj->name = 'referrer';
        $refManyTestObj->id = '/functional/refManyTestObj';

        $this->dm->persist($refManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('refManyTestObj')->hasProperty('references'));

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

        $this->assertTrue($this->node->getNode('refManyTestObj')->hasProperty('references'));

        $refnode = $this->node->getNode('refManyTestObj');
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

        $this->assertNotEquals($this->node->getNode('refTestObj')->getProperty('reference')->getValue()->getProperty('name')->getString(), "referenced");
        $this->assertEquals($this->node->getNode('refTestObj')->getProperty('reference')->getValue()->getProperty('name')->getString(), "referenced changed");
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
            $i += 1;
        }

        $this->dm->persist($referrer);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find($this->referrerManyType, '/functional/refManyTestObj');

        $i = 0;
        foreach ($this->node->getNode('refManyTestObj')->getProperty('references')->getValue() as  $node) {
            $this->assertEquals($node->getProperty('name')->getValue(), "new name ".$i);
            $i++;
        }
        $this->assertEquals($i, $max);

    }

    /**
     * Remove referrer node, but change referenced node before
     */
    public function testRemove()
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
        $this->assertEquals($this->session->getNode('/functional/refRefTestObj')->getProperty('name')->getString(), 'referenced changed');
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

        $referenced = $referrer->reference;
        $referrer->reference = null;
        $this->dm->persist($referrer);

        $this->dm->remove($referenced);

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
        $this->assertFalse($this->session->getNode('/functional/')->hasNode('refRefTestObj'));
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

        $refnode = $this->node->getNode('refManyTestObj');
        foreach ($refnode->getProperty('references')->getValue() as $referenced) {
            $this->assertTrue($referenced->hasProperty('name'));
        }

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
}

/**
 * @PHPCRODM\Document(alias="RefManyTestObj")
 */
class RefManyTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceMany(targetDocument="RefRefTestObj") */
    public $references;
    /** @PHPCRODM\String */
    public $name;

   public function __construct()
   {
      $references = new \Doctrine\Common\Collections\ArrayCollection();
   }
}

/**
 * @PHPCRODM\Document(alias="HardRefTestObj")
 */
class HardRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefRefTestObj", weak=false) */
    public $reference;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(alias="WeakRefTestObj")
 */
class WeakRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefRefTestObj", weak=true) */
    public $reference;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(alias="RefTestObj")
 */
class RefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefRefTestObj") */
    public $reference;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(alias="RefRefTestObj", referenceable="true")
 */
class RefRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

}

/**
 * @PHPCRODM\Document(alias="RefAnnotationTestObj", referenceable="true")
 */
class RefAnnotationTestObj
{
    /** @PHPCRODM\Id */
    public $id;
}

