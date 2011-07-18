<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/** @PHPCRODM\Document */
class CmsUserRights
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Boolean */
    public $canWriteArticle = false;
    /** @PHPCRODM\Boolean */
    public $canDeleteArticle = false;
}