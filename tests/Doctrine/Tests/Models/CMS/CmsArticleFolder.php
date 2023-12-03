<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(childClasses: CmsArticle::class)]
class CmsArticleFolder
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Children]
    public $articles;
}
