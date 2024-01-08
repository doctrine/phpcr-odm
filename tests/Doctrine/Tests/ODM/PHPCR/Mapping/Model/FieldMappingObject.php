<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped fields via properties.
 */
#[PHPCR\Document]
class FieldMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $string;

    #[PHPCR\Field(type: 'binary')]
    public $binary;

    #[PHPCR\Field(type: 'long')]
    public $long;

    #[PHPCR\Field(type: 'long')]
    public $int;

    #[PHPCR\Field(type: 'decimal')]
    public $decimal;

    #[PHPCR\Field(type: 'double')]
    public $double;

    #[PHPCR\Field(type: 'double')]
    public $float;

    #[PHPCR\Field(type: 'date')]
    public $date;

    #[PHPCR\Field(type: 'boolean')]
    public $boolean;

    #[PHPCR\Field(type: 'name')]
    public $name;

    #[PHPCR\Field(type: 'path')]
    public $path;

    #[PHPCR\Field(type: 'uri')]
    public $uri;
}
