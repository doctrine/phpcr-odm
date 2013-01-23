<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ParentTestObj
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Nodename */
    public $nodename;
    /** @PHPCRODM\String */
    public $name;

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
