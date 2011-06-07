<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class CmsArticle
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $topic;
    /** @ODM\String */
    public $text;
    /** @ODM\ReferenceOne(targetDocument="CmsUser") */
    public $user;
    public $comments;
    /** @ODM\Version */
    public $version;

    /** @ODM\Attachments */
    public $attachments;

    public function setAuthor(CmsUser $author) {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment) {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
