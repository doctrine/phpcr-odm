<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(versionable: 'full')]
class FullVersionableArticleWithChildren extends FullVersionableArticle
{
    #[PHPCR\Children]
    public $childArticles;

    public function __construct()
    {
        $this->childArticles = new ArrayCollection();
    }

    public function addChildArticle(NonVersionableArticle $a)
    {
        $this->childArticles->add($a);
    }
}
