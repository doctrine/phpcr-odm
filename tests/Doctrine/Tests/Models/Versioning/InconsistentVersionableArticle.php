<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This document has a Version annotated field but it is not marked as versionable
 * @PHPCRODM\Document
 */
class InconsistentVersionableArticle
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\String */
    public $author;

    /** @PHPCRODM\String */
    public $topic;

    /** @PHPCRODM\String */
    private $text;

    /** @PHPCRODM\VersionName */
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
