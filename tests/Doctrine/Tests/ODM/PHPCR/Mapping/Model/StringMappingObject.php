<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains string properties
 *
 * @PHPCRODM\Document
 */
class StringMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\String(assoc="") */
    public $stringAssoc;
}
