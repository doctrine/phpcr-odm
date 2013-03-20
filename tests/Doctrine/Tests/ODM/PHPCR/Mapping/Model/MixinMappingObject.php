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

    /** @PHPCRODM\Date(name="jcr:lastModified", readonly=true) */
    public $lastModified;

    /** @PHPCRODM\String(name="jcr:lastModifiedBy", readonly=true) */
    public $lastModifiedBy;

}
