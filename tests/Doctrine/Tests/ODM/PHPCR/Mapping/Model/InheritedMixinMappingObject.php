<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties.
 *
 * @PHPCRODM\Document(mixins={"mix:title"})
 */
#[PHPCR\Document(mixins: ['mix:title'])]
class InheritedMixinMappingObject extends MixinMappingObject
{
    /** @PHPCRODM\Field(type="string", property="jcr:title") */
    #[PHPCR\Field(type: 'string', property: 'jcr:title')]
    public $title;

    /** @PHPCRODM\Field(type="string", property="jcr:description") */
    #[PHPCR\Field(type: 'string', property: 'jcr:description')]
    public $description;
}
