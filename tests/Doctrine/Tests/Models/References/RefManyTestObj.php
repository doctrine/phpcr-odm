<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="RefManyTestObj")
 */
class RefManyTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceMany */
    public $references;
    /** @PHPCRODM\String */
    public $name;

    public function __construct()
    {
       $references = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
