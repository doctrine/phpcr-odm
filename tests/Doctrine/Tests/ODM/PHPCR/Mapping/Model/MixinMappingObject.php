<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document(mixins: ['mix:lastModified'])]
class MixinMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(property: 'jcr:lastModified', type: 'date')]
    public $lastModified;

    #[PHPCR\Field(property: 'jcr:lastModifiedBy', type: 'string')]
    public $lastModifiedBy;
}
