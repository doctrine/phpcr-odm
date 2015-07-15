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

    /** @PHPCRODM\Property(type="string") */
    public $message;

    /** @PHPCRODM\Property(type="string", nullable=true) */
    public $username;
}
