<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains string properties.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class StringMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Field(type="string", assoc="") */
    #[PHPCR\Field(type: 'string', assoc: '')]
    public $stringAssoc;
}
