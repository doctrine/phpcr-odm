<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\EventListener;

use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\ODM\PHPCR\EventListener;

/**
 * Class LastModifiedTest
 * @package Doctrine\Tests\ODM\PHPCR\Functional\EventListener
 * @group event-listener
 */
class LastModifiedTest extends PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var MixinMappingObject
     */
    private $mixin;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    /**
     * @var integer
     */
    private $startDate;

    /**
     * @var \Doctrine\ODM\PHPCR\EventListener\LastModified $testedListener
     */
    private $testedListener;

    public function setUp()
    {
        $now = new \DateTime();
        $this->startDate = $now->getTimestamp();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $this->mixin = new MixinMappingObject();
        $this->testedListener = new EventListener\LastModified();

        $this->dm->getEventManager()->addEventListener(array('prePersist', 'preUpdate'), $this->testedListener);
    }

    public function testUpdateLastModified()
    {
        $this->mixin->id = '/functional/mixin';

        $this->dm->persist($this->mixin);
        $this->dm->flush();

        $this->assertTrue($this->mixin->node->hasProperty('jcr:lastModified'));

        /** @var \PHPCR\PropertyInterface $flushDateProperty */
        $flushDateProperty = $this->mixin->node->getProperty('jcr:lastModified');
        $this->assertLessThanOrEqual(2, abs($flushDateProperty->getLong() - $this->startDate));

        $this->mixin->node->setProperty('jcr:lastModified', new \DateTime('2010-01-01'));
        $this->dm->getPhpcrSession()->save();
        $this->dm->clear();

        $this->mixin = $this->dm->find(null, '/functional/mixin');

        $this->mixin->title = 'my title';

        $this->dm->flush();

        /** @var \PHPCR\PropertyInterface $moveDateProperty */
        $dateProperty = $this->mixin->node->getProperty('jcr:lastModified');
        $this->assertLessThanOrEqual(2, abs($dateProperty->getLong() - $this->startDate));
    }
}