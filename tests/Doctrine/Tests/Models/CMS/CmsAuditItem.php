<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class CmsAuditItem
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $message;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $username;
}
