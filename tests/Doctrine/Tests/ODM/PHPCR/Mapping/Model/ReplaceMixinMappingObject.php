<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(mixins={"mix:lastModified"}, inheritMixins=false)
 */
class ReplaceMixinMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Property(type="date", property="jcr:lastModified") */
    public $lastModified;

    /** @PHPCRODM\Property(type="string", property="jcr:lastModifiedBy") */
    public $lastModifiedBy;

}
