<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="RefAnnotationTestObj", referenceable=true)
 */
class RefAnnotationTestObj
{
    /** @PHPCRODM\Id */
    public $id;
}
