<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="RefTestObj")
 */
class RefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne */
    public $reference;
    /** @PHPCRODM\String */
    public $name;
}
