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
    /** @PHPCRODM\ReferenceOne(targetDocument="RefType1TestObj", cascade="persist") */
    public $referenceType1;
    /** @PHPCRODM\ReferenceOne(targetDocument="RefType2TestObj", cascade="persist") */
    public $referenceType2;
}
