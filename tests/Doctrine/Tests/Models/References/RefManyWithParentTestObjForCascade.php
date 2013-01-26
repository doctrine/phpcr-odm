<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class RefManyWithParentTestObjForCascade
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\ReferenceMany(targetDocument="RefCascadeManyTestObj", cascade="all") */
    public $references;

    /** @PHPCRODM\String */
    public $name;

    public function setReferences($references)
    {
        foreach ($references as $reference) {
            $reference->setParent($this);
        }

        $this->references = $references;
    }

    public function getReferences()
    {
        return $this->references;
    }
}
