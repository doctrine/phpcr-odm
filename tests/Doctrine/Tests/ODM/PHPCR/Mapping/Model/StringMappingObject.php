<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains string properties.
 */
#[PHPCR\Document]
class StringMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string', assoc: '')]
    public $stringAssoc;
}
