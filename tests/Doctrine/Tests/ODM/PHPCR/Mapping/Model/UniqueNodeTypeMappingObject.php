<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that has a unique node type among other mapped documents.
 *
 * @PHPCRODM\Document(uniqueNodeType=true)
 */
#[PHPCR\Document(uniqueNodeType: true)]
class UniqueNodeTypeMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
