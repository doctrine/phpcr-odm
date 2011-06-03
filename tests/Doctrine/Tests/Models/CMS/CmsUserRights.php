<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;

/** @ODM\Document */
class CmsUserRights
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Boolean */
    public $canWriteArticle = false;
    /** @ODM\Boolean */
    public $canDeleteArticle = false;
}