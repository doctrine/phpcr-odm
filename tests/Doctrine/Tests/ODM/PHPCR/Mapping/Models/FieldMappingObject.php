<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Models;

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
    
    /** @PHPCRODM\String(name="string") */
    public $string;
    /** @PHPCRODM\Binary(name="binary") */
    public $binary;
    /** @PHPCRODM\Long(name="long") */
    public $long;
    /** @PHPCRODM\Int(name="int") */
    public $int;
    /** @PHPCRODM\Decimal(name="decimal") */
    public $decimal;
    /** @PHPCRODM\Double(name="double") */
    public $double;
    /** @PHPCRODM\Float(name="float") */
    public $float;
    /** @PHPCRODM\Date(name="date") */
    public $date;
    /** @PHPCRODM\Boolean(name="boolean") */
    public $boolean;
    /** @PHPCRODM\Name(name="name") */
    public $name;
    /** @PHPCRODM\Path(name="path") */
    public $path;
    /** @PHPCRODM\Uri(name="uri") */
    public $uri;
}
