<?php

namespace Documents;

namespace Doctrine\Tests\Models\Inheritance;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferenceableParentDocument
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;

    public function getId()
    {
        return $this->id;
    }
}

