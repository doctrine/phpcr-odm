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

    /** @PHPCRODM\ReferenceMany(cascade="all") */
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
