<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class ParentNoNodeNameTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Field(type: 'string')]
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
