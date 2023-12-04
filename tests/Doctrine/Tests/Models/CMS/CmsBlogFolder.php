<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(childClasses: CmsBlogPost::class)]
class CmsBlogFolder
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Children]
    public $posts;
}
