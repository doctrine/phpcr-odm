<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(mixins={"mix:title"})
 */
class InheritedMixinMappingObject extends MixinMappingObject
{
    /** @PHPCRODM\Field(type="string", property="jcr:title") */
    public $title;

    /** @PHPCRODM\Field(type="string", property="jcr:description") */
    public $description;
}
