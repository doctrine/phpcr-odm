<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(childClasses={"Doctrine\Tests\Models\CMS\CmsArticle"})
 */
class CmsArticleFolder
{
    /**
     * @PHPCRODM\Id
     */
    public $id;

    /**
     * @PHPCRODM\Children()
     */
    public $articles;
}
