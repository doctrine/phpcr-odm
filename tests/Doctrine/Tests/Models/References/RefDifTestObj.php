<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class RefDifTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefType1TestObj") */
    public $referenceType1;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefType2TestObj") */
    public $referenceType2;
    /** @PHPCRODM\String */
    public $name;

    public function __construct()
    {
       $references = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
