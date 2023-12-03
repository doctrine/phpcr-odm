<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(mixins={"mix:lastModified"})
 */
#[PHPCR\Document(mixins: ['mix:lastModified'])]
class MixinMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Node */
    #[PHPCR\Node]
    public $node;

    /** @PHPCRODM\Field(type="date", property="jcr:lastModified") */
    #[PHPCR\Field(type: 'date', property: 'jcr:lastModified')]
    public $lastModified;

    /** @PHPCRODM\Field(type="string", property="jcr:lastModifiedBy") */
    #[PHPCR\Field(type: 'string', property: 'jcr:lastModifiedBy')]
    public $lastModifiedBy;
}
