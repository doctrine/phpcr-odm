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

    /** @PHPCRODM\String */
    public $string;
    /** @PHPCRODM\Binary */
    public $binary;
    /** @PHPCRODM\Long */
    public $long;
    /** @PHPCRODM\Int */
    public $int;
    /** @PHPCRODM\Decimal */
    public $decimal;
    /** @PHPCRODM\Double */
    public $double;
    /** @PHPCRODM\Float */
    public $float;
    /** @PHPCRODM\Date */
    public $date;
    /** @PHPCRODM\Boolean */
    public $boolean;
    /** @PHPCRODM\Name */
    public $name;
    /** @PHPCRODM\Path */
    public $path;
    /** @PHPCRODM\Uri */
    public $uri;
}
