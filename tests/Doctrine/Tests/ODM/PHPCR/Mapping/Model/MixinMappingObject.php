<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(mixins={"mix:lastModified"})
 */
class MixinMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Date(property="jcr:lastModified") */
    public $lastModified;

    /** @PHPCRODM\String(property="jcr:lastModifiedBy") */
    public $lastModifiedBy;

}
