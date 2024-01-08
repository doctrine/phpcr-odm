<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that has a unique node type among other mapped documents.
 */
#[PHPCR\Document(uniqueNodeType: true)]
class UniqueNodeTypeMappingObject
{
    #[PHPCR\Id]
    public $id;
}
