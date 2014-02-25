<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class UuidMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /**@PHPCRODM\Uuid()*/
    public $uuid;
}
