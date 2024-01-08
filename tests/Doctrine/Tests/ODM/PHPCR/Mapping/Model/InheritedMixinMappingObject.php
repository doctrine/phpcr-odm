<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 */
#[PHPCR\Document(mixins: ['mix:title'])]
class InheritedMixinMappingObject extends MixinMappingObject
{
    #[PHPCR\Field(type: 'string', property: 'jcr:title')]
    public $title;

    #[PHPCR\Field(type: 'string', property: 'jcr:description')]
    public $description;
}
