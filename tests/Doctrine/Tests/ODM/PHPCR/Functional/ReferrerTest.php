<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * These tests test if referrers are correctly read. For cascading
 * referrers, see CascadePersistTest
 *
 * @group functional
 */
class ReferrerTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    protected $localePrefs = array(
        'en' => array('en', 'fr'),
        'fr' => array('fr', 'en'),
    );

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
        $this->node = $this->resetFunctionalNode($this->dm);
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

        /** @var $reference ReferrerRefTestObj */
        $reference = $this->dm->find(null, "/functional/referrerRefTestObj");

        $this->assertCount($max, $reference->referrers);

        $tmpIds = array();
        foreach ($reference->referrers as $referrer) {
            $this->assertInstanceOf('\Doctrine\Tests\ODM\PHPCR\Functional\ReferrerTestObj', $referrer);
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
        $referrerTestObj->name = 'referrer';
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";

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

        $hardReferrerRefTestObj = new HardReferrerRefTestObj();
        $hardReferrerRefTestObj->id = "/functional/hardReferrerRefTestObj";

        $allReferrerRefTestObj = new AllReferrerRefTestObj();
        $allReferrerRefTestObj->id = "/functional/allReferrerRefTestObj";


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

        $referrerTestObj->reference = $allReferrerRefNamedPropTestObj;
        $referrerNamedPropTestObj->namedReference = $allReferrerRefNamedPropTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->persist($referrerNamedPropTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find(null, "/functional/allReferrerRefNamedPropTestObj");

        $this->assertCount(1, $referenced->referrers);
        $this->assertEquals("referrerNamedPropTestObj", $referenced->referrers[0]->name);

        $otherRef = new OtherReferrerTestObj();
        $otherRef->id = '/functional/otherObj';
        $otherRef->name = 'other ref';
        $otherRef->namedReference = $referenced;

        $this->dm->persist($otherRef);
        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find(null, "/functional/allReferrerRefNamedPropTestObj");

        // the other ref is a different class and should get filtered out
        $this->assertCount(1, $referenced->referrers);
        $this->assertEquals("referrerNamedPropTestObj", $referenced->referrers[0]->name);

        $allReferrers = $this->dm->getReferrers($referenced, null, 'named-reference');
        $this->assertCount(2, $allReferrers);
    }

    /**
     * There was a bug that documents translated fields where overwritten when they are re-created
     *
     * depends testCreate
     */
    public function testMultilangReferrers()
    {
        $this->dm->setLocaleChooserStrategy(new \Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser($this->localePrefs, 'en'));
        $this->dm->setTranslationStrategy('attribute', new \Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy($this->dm));

        $referrerTestObj = new ReferrerTestObjMultilang();
        $referrerRefTestObj = new ReferrerRefTestObj();

        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrer";
        $referrerRefTestObj->id = "/functional/referrerRefTestObj";

        $referrerTestObj->reference = $referrerRefTestObj;

        $this->dm->persist($referrerTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, '/functional/referrerTestObj');
        $referenced = $this->dm->find(null, "/functional/referrerRefTestObj");

        $referrer->name = 'changed';
        $this->assertEquals('changed', $referrer->name);

        $this->assertCount(1, $referenced->referrers);
        $this->assertSame($referrer, $referenced->referrers->first());
        $this->assertEquals('changed', $referrer->name);
    }

    public function testCascadeRemoveByCollection()
    {
        $referrerRefManyTestObj = new ReferrerRefTestObj2();
        $referrerRefManyTestObj->id = "/functional/referrerRefManyTestObj";

        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $newReferrerTestObj = new ReferrerTestObj2();
            $newReferrerTestObj->id = "/functional/referrerTestObj$i";
            $newReferrerTestObj->name = "referrerTestObj$i";
            $newReferrerTestObj->reference = $referrerRefManyTestObj;
            $this->dm->persist($newReferrerTestObj);
        }

        $this->dm->persist($referrerRefManyTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find(null, "/functional/referrerRefManyTestObj");
        $this->assertCount($max, $referenced->referrers);
        $referenced->referrers->remove(0);
        $referenced->referrers->remove(3);
        $this->assertCount($max - 2, $referenced->referrers);

        $this->dm->flush();
        $this->dm->clear();

        $referenced = $this->dm->find(null, "/functional/referrerRefManyTestObj");
        $this->assertCount($max - 2, $referenced->referrers);
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
     * @PHPCRODM\ReferenceOne(targetDocument="WeakReferrerRefTestObj", cascade="persist")
     */
    public $referenceToWeak;
    /** @PHPCRODM\ReferenceOne(targetDocument="HardReferrerRefTestObj", strategy="weak", cascade="persist") */
    public $referenceToHard;
    /** @PHPCRODM\ReferenceOne(targetDocument="AllReferrerRefTestObj", strategy="weak", cascade="persist") */
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
    /** @PHPCRODM\MixedReferrers(referenceType="weak") */
    public $referrers;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class HardReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\MixedReferrers(referenceType="hard") */
    public $referrers;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class AllReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\MixedReferrers() */
    public $referrers;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class AllReferrerRefNamedPropTestObj extends ReferrerRefTestObj
{
    /** @PHPCRODM\Referrers(referencedBy="namedReference",referringDocument="ReferrerNamedPropTestObj") */
    public $referrers;
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
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj", cascade="persist") */
    public $reference;
}

/**
 * @PHPCRODM\Document()
 */
class OtherReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj", property="named-reference", cascade="persist") */
    public $namedReference;
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class ReferrerTestObjMultilang
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String(translated=true) */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj", cascade="persist") */
    public $reference;

    /** @PHPCRODM\Locale */
    protected $locale;
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
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj", property="named-reference", cascade="persist") */
    public $namedReference;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferrerRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\MixedReferrers() */
    public $referrers;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferrerRefTestObj2
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Referrers(referringDocument="ReferrerTestObj2", referencedBy="reference", cascade={"persist", "remove"}) */
    public $referrers;
}

/**
 * @PHPCRODM\Document()
 */
class ReferrerTestObj2
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="ReferrerRefTestObj2", cascade="persist") */
    public $reference;
}
