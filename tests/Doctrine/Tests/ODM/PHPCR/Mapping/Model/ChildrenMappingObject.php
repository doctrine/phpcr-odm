<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document]
class ChildrenMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Children]
    public $all;

    #[PHPCR\Children(filter: '*some*', fetchDepth: 2, cascade: ['persist', 'remove'])]
    public $some;
}
