<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefManyWithParentTestObjForCascade
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceMany(cascade: 'all')]
    public $references;

    public function setReferences($references)
    {
        foreach ($references as $reference) {
            $reference->setParentDocument($this);
        }

        $this->references = $references;
    }

    public function getReferences()
    {
        return $this->references;
    }
}
