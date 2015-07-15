<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped fields via properties
 *
 * @PHPCRODM\Document
 */
class PropertyMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Property(type="string") */
    public $string;
    /** @PHPCRODM\Property(type="binary") */
    public $binary;
    /** @PHPCRODM\Property(type="long") */
    public $long;
    /** @PHPCRODM\Property(type="long") */
    public $int;
    /** @PHPCRODM\Property(type="decimal") */
    public $decimal;
    /** @PHPCRODM\Property(type="double") */
    public $double;
    /** @PHPCRODM\Property(type="double") */
    public $float;
    /** @PHPCRODM\Property(type="date") */
    public $date;
    /** @PHPCRODM\Property(type="boolean") */
    public $boolean;
    /** @PHPCRODM\Property(type="name") */
    public $name;
    /** @PHPCRODM\Property(type="path") */
    public $path;
    /** @PHPCRODM\Property(type="uri") */
    public $uri;
}
