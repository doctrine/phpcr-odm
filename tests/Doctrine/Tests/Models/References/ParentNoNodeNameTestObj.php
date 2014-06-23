<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ParentNoNodeNameTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\String */
    public $name;

    public function getParentDocument()
    {
        return $this->parent;
    }

    public function setParentDocument($parent)
    {
        $this->parent = $parent;
    }
}
