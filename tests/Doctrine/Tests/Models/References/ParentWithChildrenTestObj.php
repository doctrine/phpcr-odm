<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ParentWithChildrenTestObj
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Nodename */
    public $nodename;
    /** @PHPCRODM\Field(type="string") */
    public $name;
    /** @PHPCRODM\Children() */
    public $children;

    public function getParentDocument()
    {
        return $this->parent;
    }

    public function setParentDocument($parent)
    {
        $this->parent = $parent;
    }
}
