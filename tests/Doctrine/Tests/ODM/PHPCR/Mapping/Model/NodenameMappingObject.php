<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped fields via properties.
 */
#[PHPCR\Document]
class NodenameMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Nodename]
    public $namefield;
}
