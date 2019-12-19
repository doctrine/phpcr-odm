<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="full")
 */
class FullVersionableArticleWithChildren extends FullVersionableArticle
{
    /**
     * @PHPCRODM\Children
     */
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
