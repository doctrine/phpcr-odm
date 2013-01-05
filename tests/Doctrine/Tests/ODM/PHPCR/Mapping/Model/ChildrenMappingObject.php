<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties 
 * 
 * @PHPCRODM\Document
 */
class ChildrenMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Children() */
    public $all;

    /** @PHPCRODM\Children(filter="*some*", fetchDepth=2, cascade={"persist", "remove"}) */
    public $some;
}
