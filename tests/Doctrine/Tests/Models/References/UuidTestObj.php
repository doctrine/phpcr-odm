<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class UuidTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Uuid */
    public $uuid1;
}
