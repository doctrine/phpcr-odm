<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="RefType2TestObj", referenceable=true)
 */
class RefType2TestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
}
