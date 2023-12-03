<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document]
class ChildMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Child(nodeName: 'first')]
    public $child1;

    #[PHPCR\Child(nodeName: 'second')]
    public $child2;
}
