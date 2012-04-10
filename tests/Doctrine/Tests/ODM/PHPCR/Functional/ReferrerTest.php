<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class ReferrerTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();

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
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount(1, $reference->referrers);
        $this->assertEquals("/functional/referrerTestObj", $reference->referrers->first()->id);
    }

    public function testJIRA41DonotPersistReferrersCollection()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();


        $tmpReferrer = $this->dm->find(null, "/functional/referrerTestObj");
        $tmpReferrer->name = "new referrer name";

        $tmpReference = $this->dm->find(null, "/functional/referrerRefTestObj");

        // persist referenced document again
        $this->dm->persist($tmpReference);

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertEquals("new referrer name", $reference->referrers->first()->name);
    }

    public function testCreateWithoutRef()
    {
        $referrerTestObj = new ReferrerRefTestObj();
        $referrerTestObj->name = 'referrerRefTestObj';
        $referrerTestObj->id = '/functional/referrerRefTestObj';

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(null, '/functional/referrerRefTestObj');

        $this->assertCount(0, $document->referrers);
    }

    public function testCreateManyRef()
    {
        $max = 5;

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $ids = array();
        for ($i = 0; $i < $max; $i++) {
            $referrerTestObj = new ReferrerTestObj();
            $referrerTestObj->id = "/functional/referrerTestObj$i";
            $referrerTestObj->name = "referrer $i";
            $referrerTestObj->reference = $referrerRefTestObj;
            $this->dm->persist($referrerTestObj);
            $ids[] = "/functional/referrerTestObj$i";
        }

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount($max, $reference->referrers);

        $tmpIds = array();
        foreach ($reference->referrers as $referrer) {
            $tmpIds[] = $referrer->id;
        }

        foreach ($ids as $id) {
            $this->assertTrue(in_array($id, $tmpIds));
        }
    }

    public function testNoReferrerInitOnFlush()
    {
        $max = 5;

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $ids = array();
        for ($i = 0; $i < $max; $i++) {
            $referrerTestObj = new ReferrerTestObj();
            $referrerTestObj->id = "/functional/referrerTestObj$i";
            $referrerTestObj->name = "referrer $i";
            $referrerTestObj->reference = $referrerRefTestObj;
            $this->dm->persist($referrerTestObj);
            $ids[] = "/functional/referrerTestObj$i";
        }

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");
        $this->dm->flush();

        $this->assertFalse($reference->referrers->isInitialized());
    }

    public function testUpdate()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");
        $reference->referrers->first()->name = "referrer changed";

        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");
        $this->assertEquals("referrer changed", $referrer->name);
    }

    public function testUpdateMany()
    {
        $max = 5;

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $ids = array();
        for ($i = 0; $i < $max; $i++) {
            $referrerTestObj = new ReferrerTestObj();
            $referrerTestObj->id = "/functional/referrerTestObj$i";
            $referrerTestObj->name = "referrer $i";
            $referrerTestObj->reference = $referrerRefTestObj;
            $this->dm->persist($referrerTestObj);
            $ids[] = "/functional/referrerTestObj$i";
        }

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $i = 0;
        $names = array();
        foreach ($reference->referrers as $referrer) {
            $newName = "new name ".$i;
            $names[] = $newName;
            $referrer->name = $newName;
            $i++;
        }

        $this->dm->flush();
        $this->dm->clear();

        $tmpNames = array();
        for ($i = 0; $i < $max; $i++) {
            $tmpNames[] = $this->dm->find(null, "/functional/referrerTestObj$i")->name;
        }

        foreach ($names as $name) {
            $this->assertTrue(in_array($name, $tmpNames));
        }
    }

    public function testUpdateOneInMany()
    {
        $max = 5;

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $ids = array();
        for ($i = 0; $i < $max; $i++) {
            $referrerTestObj = new ReferrerTestObj();
            $referrerTestObj->id = "/functional/referrerTestObj$i";
            $referrerTestObj->name = "referrer $i";
            $referrerTestObj->reference = $referrerRefTestObj;
            $this->dm->persist($referrerTestObj);
            $ids[$i] = "/functional/referrerTestObj$i";
        }

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $i = 0;
        $names = array();
        foreach ($reference->referrers as $referrer) {
            if ($i !== 2) {
                $names[] = $referrer->name;
            } else {
                $newName = "new name ".$i;
                $referrer->name = $newName;
                $names[] = $newName;
            }
            $i++;
        }

        $this->dm->flush();
        $this->dm->clear();

        $tmpNames = array();
        for ($i = 0; $i < $max; $i++) {
            $tmpNames[] = $this->dm->find(null, "/functional/referrerTestObj$i")->name;
        }

        foreach ($names as $name) {
            $this->assertTrue(in_array($name, $tmpNames));
        }
    }

    public function testRemoveReferrer()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount(1, $reference->referrers);
        $this->assertEquals("/functional/referrerTestObj", $reference->referrers->first()->id);

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");
        $this->dm->remove($referrer);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->dm->find(null, "/functional/referrerTestObj"));

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount(0, $reference->referrers);
        $this->assertFalse($reference->referrers->first());
    }

    public function testRemoveReferrerOneInMany()
    {
        $max = 5;

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $ids = array();
        for ($i = 0; $i < $max; $i++) {
            $referrerTestObj = new ReferrerTestObj();
            $referrerTestObj->id = "/functional/referrerTestObj$i";
            $referrerTestObj->name = "referrer $i";
            $referrerTestObj->reference = $referrerRefTestObj;
            $this->dm->persist($referrerTestObj);
            $ids[$i] = "/functional/referrerTestObj$i";
        }

        $this->dm->flush();
        $this->dm->clear();

        $delete = 2;
        $delRef = $this->dm->find(null, "/functional/referrerTestObj$delete");
        $this->dm->remove($delRef);
        unset($ids[$delete]);

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount($max -1, $reference->referrers);

        $tmpIds = array();
        foreach ($reference->referrers as $referrer) {
            $tmpIds[] = $referrer->id;
        }

        foreach ($ids as $id) {
            $this->assertTrue(in_array($id, $tmpIds));
        }
    }

    /**
     * Remove referenced node, but change referrer node before
     */
    public function testRemoveReferrerChangeBefore()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");
        $reference->referrers[0]->name = "referenced changed";

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");
        $referrer->reference = null;

        $this->dm->remove($reference);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");
        $this->assertNull($reference);

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");
        $this->assertEquals("referenced changed", $referrer->name);
    }

    /**
     * Remove referenced node, but change referrer nodes before
     */
    public function testRemoveReferrerManyChangeBefore()
    {
        $referrerRefManyTestObj = new ReferrerRefTestObj();
        $referrerRefManyTestObj->id = "/functional/referrerRefManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newReferrerTestObj = new ReferrerTestObj();
            $newReferrerTestObj->id = "/functional/referrerTestObj$i";
            $newReferrerTestObj->name = "referrerTestObj$i";
            $newReferrerTestObj->reference = $referrerRefManyTestObj;
            $this->dm->persist($newReferrerTestObj);
        }

        $this->dm->persist($referrerRefManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefManyTestObj");

        $names = array();
        $i = 0;
        foreach ($reference->referrers as $referrer) {
            $name = "new name $i";
            $names[] = $name;
            $referrer->name = $name;
            $i++;
        }
        $this->assertEquals($max, $i);

        for ($i = 0; $i < $max; $i++) {
            $referrer = $this->dm->find(null, "/functional/referrerTestObj$i");
            $referrer->reference = null;
        }

        $this->dm->remove($reference);
        $this->dm->flush();
        $this->dm->clear();

        $refNames = array();
        for ($i = 0; $i < $max; $i++) {
            $referrer = $this->dm->find(null, "/functional/referrerTestObj$i");
            $refNames[] = $referrer->name;
        }
        $this->assertEquals($max, $i);

        foreach ($names as $name) {
            $this->assertTrue(in_array($name, $refNames));
        }
    }

    public function testDeleteByRef()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->dm->remove($reference->referrers[0]);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertCount(0, $this->dm->find(null, "/functional/referrerRefTestObj")->referrers);
        $this->assertFalse($this->session->getNode("/functional")->hasNode("referrerTestObj"));
    }

    public function testWeakHardRef()
    {
        $weakReferrerTestObj = new WeakReferrerTestObj();
        $weakReferrerTestObj->id = "/functional/weakReferrerTestObj";
        $weakReferrerTestObj->name = "weakReferrerTestObj";

        $hardReferrerTestObj = new HardReferrerTestObj();
        $hardReferrerTestObj->id = "/functional/hardReferrerTestObj";
        $hardReferrerTestObj->name = "hardReferrerTestObj";


        $weakReferrerRefTestObj = new WeakReferrerRefTestObj();
        $weakReferrerRefTestObj->id = "/functional/weakReferrerRefTestObj";
        $weakReferrerRefTestObj->name = "weakReferrerRefTestObj";

        $hardReferrerRefTestObj = new HardReferrerRefTestObj();
        $hardReferrerRefTestObj->id = "/functional/hardReferrerRefTestObj";
        $hardReferrerRefTestObj->name = "hardReferrerRefTestObj";

        $allReferrerRefTestObj = new AllReferrerRefTestObj();
        $allReferrerRefTestObj->id = "/functional/allReferrerRefTestObj";
        $allReferrerRefTestObj->name = "allReferrerRefTestObj";


        $weakReferrerTestObj->referenceToWeak = $weakReferrerRefTestObj;
        $weakReferrerTestObj->referenceToHard = $hardReferrerRefTestObj;
        $weakReferrerTestObj->referenceToAll = $allReferrerRefTestObj;

        $hardReferrerTestObj->referenceToWeak = $weakReferrerRefTestObj;
        $hardReferrerTestObj->referenceToHard = $hardReferrerRefTestObj;
        $hardReferrerTestObj->referenceToAll = $allReferrerRefTestObj;

        $this->dm->persist($hardReferrerTestObj);
        $this->dm->persist($weakReferrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $weakReferrerRefTestObj = $this->dm->find(null, "/functional/weakReferrerRefTestObj");
        $this->assertCount(1, $weakReferrerRefTestObj->referrers);
        $this->assertEquals("weakReferrerTestObj", $weakReferrerRefTestObj->referrers[0]->name);

        $hardReferrerRefTestObj = $this->dm->find(null, "/functional/hardReferrerRefTestObj");
        $this->assertCount(1, $hardReferrerRefTestObj->referrers);
        $this->assertEquals("hardReferrerTestObj", $hardReferrerRefTestObj->referrers[0]->name);

        $allReferrerRefTestObj = $this->dm->find(null, "/functional/allReferrerRefTestObj");
        $this->assertCount(2, $allReferrerRefTestObj->referrers);

        $tmpNames = array();
        foreach ($allReferrerRefTestObj->referrers as $referrer) {
            $tmpNames[] = $referrer->name;
        }

        $names = array("weakReferrerTestObj", "hardReferrerTestObj");
        foreach ($names as $name) {
            $this->assertTrue(in_array($name, $tmpNames));
        }
    }

    public function testNamedRef()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerNamedPropTestObj = new ReferrerNamedPropTestObj();

        $allReferrerRefNamedPropTestObj = new AllReferrerRefNamedPropTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrerTestObj";

        $referrerNamedPropTestObj->id = "/functional/referrerNamedPropTestObj";
        $referrerNamedPropTestObj->name = "referrerNamedPropTestObj";

        $allReferrerRefNamedPropTestObj->id = "/functional/allReferrerRefNamedPropTestObj";
        $allReferrerRefNamedPropTestObj->name = "referenced";

        $referrerTestObj->reference = $allReferrerRefNamedPropTestObj;
        $referrerNamedPropTestObj->namedReference = $allReferrerRefNamedPropTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->persist($referrerNamedPropTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/allReferrerRefNamedPropTestObj");

        $this->assertCount(1, $reference->referrers);
        $this->assertEquals("referrerNamedPropTestObj", $reference->referrers[0]->name);
    }
}

/**
 * @PHPCRODM\Document()
 */
class HardReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="HardReferrerRefTestObj", strategy="hard") */
    public $referenceToHard;
    /** @PHPCRODM\ReferenceOne(targetDocument="WeakReferrerRefTestObj", strategy="hard") */
    public $referenceToWeak;
    /** @PHPCRODM\ReferenceOne(targetDocument="AllReferrerRefTestObj", strategy="hard") */
    public $referenceToAll;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document()
 */
class WeakReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /**
     * Should implicitly default to strategy="weak"
     * @PHPCRODM\ReferenceOne(targetDocument="WeakReferrerRefTestObj")
     */
    public $referenceToWeak;
    /** @PHPCRODM\ReferenceOne(targetDocument="HardReferrerRefTestObj", strategy="weak") */
    public $referenceToHard;
    /** @PHPCRODM\ReferenceOne(targetDocument="AllReferrerRefTestObj", strategy="weak") */
    public $referenceToAll;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class WeakReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers(referenceType="weak") */
    public $referrers;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class HardReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers(referenceType="hard") */
    public $referrers;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class AllReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers() */
    public $referrers;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class AllReferrerRefNamedPropTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers(filter="namedReference") */
    public $referrers;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document()
 */
class ReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj") */
    public $reference;
}

/**
 * @PHPCRODM\Document()
 */
class ReferrerNamedPropTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj") */
    public $namedReference;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\Referrers() */
    public $referrers;
}
