<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class RefManyTestObjPathStrategy
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\ReferenceMany(targetDocument="RefRefTestObj", cascade="persist", property="myReferences", strategy="path") */
    public $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }
}
