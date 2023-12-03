<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class ChildrenMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Children() */
    #[PHPCR\Children]
    public $all;

    /** @PHPCRODM\Children(filter="*some*", fetchDepth=2, cascade={"persist", "remove"}) */
    #[PHPCR\Children(filter: '*some*', fetchDepth: 2, cascade: ['persist', 'remove'])]
    public $some;
}
