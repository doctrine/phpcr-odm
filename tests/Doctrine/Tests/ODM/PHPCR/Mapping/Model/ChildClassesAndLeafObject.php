<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(childClasses={
 *     "stdClass",
 * }, isLeaf=true)
 */
class ChildClassesAndLeafObject
{
    /** @PHPCRODM\Id */
    public $id;
}
