<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=false)
 */
class NonRefTestObj
{
    /** @PHPCRODM\Id */
    public $id;
}
