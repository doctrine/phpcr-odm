<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="cms_article")
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
    /** @PHPCRODM\Version */
    public $version;

    /** @PHPCRODM\Binary */
    public $attachments;

    public function setAuthor(CmsUser $author)
    {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment)
    {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
