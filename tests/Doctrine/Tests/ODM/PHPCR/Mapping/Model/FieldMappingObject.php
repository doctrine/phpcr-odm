<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped fields via properties
 *
 * @PHPCRODM\Document
 */
class FieldMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $string;
    /** @PHPCRODM\Field(type="binary") */
    public $binary;
    /** @PHPCRODM\Field(type="long") */
    public $long;
    /** @PHPCRODM\Field(type="long") */
    public $int;
    /** @PHPCRODM\Field(type="decimal") */
    public $decimal;
    /** @PHPCRODM\Field(type="double") */
    public $double;
    /** @PHPCRODM\Field(type="double") */
    public $float;
    /** @PHPCRODM\Field(type="date") */
    public $date;
    /** @PHPCRODM\Field(type="boolean") */
    public $boolean;
    /** @PHPCRODM\Field(type="name") */
    public $name;
    /** @PHPCRODM\Field(type="path") */
    public $path;
    /** @PHPCRODM\Field(type="uri") */
    public $uri;
}
