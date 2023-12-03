<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(childClasses={
 *     "stdClass",
 * }, isLeaf=false)
 */
#[PHPCR\Document(childClasses: [\stdClass::class], isLeaf: false)]
class ChildClassesObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
