<?php

namespace Documents;

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(isLeaf=true)
 */
class CmsBlogPost
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;

    /** @PHPCRODM\Nodename() */
    public $name;

    /** @PHPCRODM\ParentDocument() */
    public $parent;
}
