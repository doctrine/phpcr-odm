<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class CmsArticle
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $topic;
    /** @PHPCRODM\String */
    public $text;
    /** @PHPCRODM\ReferenceOne(targetDocument="CmsUser") */
    public $user;
    public $comments;

    /** @PHPCRODM\Binary(nullable=true) */
    public $attachments;

    public function setAuthor(CmsUser $author)
    {
        $this->user = $author;
    }
}
