<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(childClasses={
 *     "stdClass",
 * }, isLeaf=true)
 */
#[PHPCR\Document(childClasses: [\stdClass::class], isLeaf: true)]
class ChildClassesAndLeafObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
