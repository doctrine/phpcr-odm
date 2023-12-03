<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 *
 * @PHPCRODM\Document(isLeaf=true)
 */
#[PHPCR\Document(isLeaf: true)]
class IsLeafObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
