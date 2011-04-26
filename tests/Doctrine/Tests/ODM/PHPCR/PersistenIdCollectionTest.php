<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\PersistentIdCollection;
use Doctrine\Common\Collections\ArrayCollection;

class PersistentIdCollectionTest extends \PHPUnit_Framework_TestCase
{

    public function testInitializedNoIds()
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $pers = new PersistentIdCollection(new ArrayCollection, 'foo', $dm, array());

        $this->assertTrue($this->readAttribute($pers, 'isInitialized'));
    }

    public function testInitialized()
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $pers = new PersistentIdCollection(new ArrayCollection, 'foo', $dm, array(1, 2));

        $this->assertFalse($this->readAttribute($pers, 'isInitialized'));
    }
}

