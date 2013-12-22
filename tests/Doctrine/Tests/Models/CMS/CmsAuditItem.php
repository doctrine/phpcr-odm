<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class CmsAuditItem
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\String */
    public $message;

    /** @PHPCRODM\String(nullable=true) */
    public $username;
}
