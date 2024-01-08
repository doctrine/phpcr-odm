<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class RefAnnotationTestObj
{
    #[PHPCR\Id]
    public $id;
}
