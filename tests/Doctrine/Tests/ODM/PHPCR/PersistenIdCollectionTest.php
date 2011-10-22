<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\PersistentIdCollection;
use Doctrine\Common\Collections\ArrayCollection;

class PersistentIdCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInitializedNoIds()
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $class = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();

        $pers = new PersistentIdCollection($dm, new ArrayCollection, $class, array());

        $this->assertTrue($this->readAttribute($pers, 'initialized'));
    }

    public function testInitialized()
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $class = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();

        $pers = new PersistentIdCollection($dm, new ArrayCollection, $class, array(1, 2));

        $this->assertFalse($this->readAttribute($pers, 'initialized'));
    }
}

