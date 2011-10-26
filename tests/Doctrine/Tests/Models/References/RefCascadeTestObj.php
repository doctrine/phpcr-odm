<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="RefCascadeTestObj", referenceable=true)
 */
class RefCascadeTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne */
    public $reference;
    /** @PHPCRODM\String */
    public $name;
}
