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

        $this->assertEquals(count($reference->referrers), 1);
        $this->assertEquals($reference->referrers->first()->id, "/functional/referrerTestObj");
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

        $this->assertEquals(count($document->referrers), 0);
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

        $this->assertEquals(count($reference->referrers), $max);

        $tmpIds = array();
        foreach ($reference->referrers as $referrer) {
            $tmpIds[] = $referrer->id;
        }

        foreach ($ids as $id) {
            $this->assertTrue(in_array($id, $tmpIds));
        }
    }

    public function testCreateAddRefLater()
    {
        $referrerTestObj = new ReferrerTestObj();
        $referrerTestObj->name = "referrer";
        $referrerTestObj->id = "/functional/referrerTestObj";

        $this->dm->persist($referrerTestObj);

        $referrerRefTestObj = new ReferrerRefTestObj();
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";
        $referrerRefTestObj->name = "referenced";

        $this->dm->persist($referrerRefTestObj);

        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");
        $this->assertEquals(count($reference->referrers), 0);

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");

        $referrer->reference = $reference;

        $this->dm->flush();
        $this->dm->clear();

        $tmpReference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertEquals(count($tmpReference->referrers), 1);
        $this->assertEquals($tmpReference->referrers->first()->id, "/functional/referrerTestObj");
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
        $this->assertEquals($referrer->name, "referrer changed");
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

        $this->assertEquals(count($reference->referrers), 1);
        $this->assertEquals($reference->referrers->first()->id, "/functional/referrerTestObj");

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");
        $this->dm->remove($referrer);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->dm->find(null, "/functional/referrerTestObj"));

        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertEquals(count($reference->referrers), 0);
        $this->assertFalse($reference->referrers->first());
    }

    public function testRemoveReferrerMany()
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

        $this->assertEquals(count($reference->referrers), $max -1);

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

    public function testRemoveReferrerChangeBevore()
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



        $reference = $this->dm->find($this->referrerType, "/functional/referrerRefTestObj");
        $reference->referrers[0]->name = "referenced changed");

        $this->dm->remove($reference);
        $this->dm->flush();
        $this->dm->clear();

        $reference = $this->dm->find($this->referrerType, '/functional/referrerRefTestObj');
        $this->assertNull($reference);

        $referrer = $this->dm->find($this->referrerType, '/functional/referrerTestObj');
        $this->assertEquals($referrer->name, 'referenced changed');
    }
}

/**
 * @PHPCRODM\Document(alias="ReferrerTestObj")
 */
class ReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj", weak=false) */
    public $reference;
}

/**
 * @PHPCRODM\Document(alias="ReferrerRefTestObj", referenceable="true")
 */
class ReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\Referrers */
    public $referrers;
}
