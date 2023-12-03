<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class ParentWithChildrenTestObj
{
    #[PHPCR\Id(strategy: 'parent')]
    public $id;
    #[PHPCR\ParentDocument]
    public $parent;
    #[PHPCR\Nodename]
    public $nodename;
    #[PHPCR\Field(type: 'string')]
    public $name;
    #[PHPCR\Children]
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
