<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
 *
 * @PHPCRODM\Document(nodeType="nt:test")
 */
#[PHPCR\Document(nodeType: 'nt:test')]
class NodeTypeMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
