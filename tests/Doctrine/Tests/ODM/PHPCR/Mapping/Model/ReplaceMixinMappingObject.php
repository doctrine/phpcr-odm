<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document(mixins: ['mix:lastModified'], inheritMixins: false)]
class ReplaceMixinMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(type: 'date', property: 'jcr:lastModified')]
    public $lastModified;

    #[PHPCR\Field(type: 'string', property: 'jcr:lastModifiedBy')]
    public $lastModifiedBy;
}
