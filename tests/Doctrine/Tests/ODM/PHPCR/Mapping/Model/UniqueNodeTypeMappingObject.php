<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that has a unique node type among other mapped documents.
 *
 * @PHPCRODM\Document(uniqueNodeType=true)
 */
class UniqueNodeTypeMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
}
