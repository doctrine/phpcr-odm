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

    /** @PHPCRODM\Field(type="string") */
    public $message;

    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $username;
}
