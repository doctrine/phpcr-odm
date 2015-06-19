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

    /** @PHPCRODM\Field(type="string") */
    public $author;

    /** @PHPCRODM\Field(type="string") */
    public $topic;

    /** @PHPCRODM\Field(type="string") */
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
