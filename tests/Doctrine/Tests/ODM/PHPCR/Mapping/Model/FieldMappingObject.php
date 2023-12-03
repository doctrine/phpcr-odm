<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped fields via properties.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class FieldMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Field(type="string") */
    #[PHPCR\Field(type: 'string')]
    public $string;

    /** @PHPCRODM\Field(type="binary") */
    #[PHPCR\Field(type: 'binary')]
    public $binary;

    /** @PHPCRODM\Field(type="long") */
    #[PHPCR\Field(type: 'long')]
    public $long;

    /** @PHPCRODM\Field(type="long") */
    #[PHPCR\Field(type: 'long')]
    public $int;

    /** @PHPCRODM\Field(type="decimal") */
    #[PHPCR\Field(type: 'decimal')]
    public $decimal;

    /** @PHPCRODM\Field(type="double") */
    #[PHPCR\Field(type: 'double')]
    public $double;

    /** @PHPCRODM\Field(type="double") */
    #[PHPCR\Field(type: 'double')]
    public $float;

    /** @PHPCRODM\Field(type="date") */
    #[PHPCR\Field(type: 'date')]
    public $date;

    /** @PHPCRODM\Field(type="boolean") */
    #[PHPCR\Field(type: 'boolean')]
    public $boolean;

    /** @PHPCRODM\Field(type="name") */
    #[PHPCR\Field(type: 'name')]
    public $name;

    /** @PHPCRODM\Field(type="path") */
    #[PHPCR\Field(type: 'path')]
    public $path;

    /** @PHPCRODM\Field(type="uri") */
    #[PHPCR\Field(type: 'uri')]
    public $uri;
}
