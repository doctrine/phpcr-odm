<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains a mapped parent document via properties.
 */
#[PHPCR\Document]
class DepthMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Depth]
    public $depth;
}
