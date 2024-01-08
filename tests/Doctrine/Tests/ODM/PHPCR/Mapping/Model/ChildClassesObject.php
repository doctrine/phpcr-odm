<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document(childClasses: [\stdClass::class], isLeaf: false)]
class ChildClassesObject
{
    #[PHPCR\Id]
    public $id;
}
