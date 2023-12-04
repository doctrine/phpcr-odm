<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * This document has a Version annotated field but it is not marked as versionable
 */
#[PHPCR\Document]
class InconsistentVersionableArticle
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $author;

    #[PHPCR\Field(type: 'string')]
    public $topic;

    #[PHPCR\Field(type: 'string')]
    private $text;

    #[PHPCR\VersionName]
    public $versionName;

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }
}
