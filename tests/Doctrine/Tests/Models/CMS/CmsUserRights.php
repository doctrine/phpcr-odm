<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/** @PHPCRODM\Document(alias="cms_user_rights") */
class CmsUserRights
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Boolean */
    public $canWriteArticle = false;
    /** @PHPCRODM\Boolean */
    public $canDeleteArticle = false;
}
