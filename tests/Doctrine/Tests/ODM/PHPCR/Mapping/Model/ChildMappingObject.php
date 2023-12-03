<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class ChildMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Child(nodeName="first") */
    #[PHPCR\Child(nodeName: 'first')]
    public $child1;

    /** @PHPCRODM\Child(nodeName="second") */
    #[PHPCR\Child(nodeName: 'second')]
    public $child2;
}
