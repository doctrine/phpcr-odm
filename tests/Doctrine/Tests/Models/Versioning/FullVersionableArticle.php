<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="full")
 */
class FullVersionableArticle
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

    /** @PHPCRODM\VersionCreated */
    public $versionCreated;


    public function getText()
    {
        return $this->text;
    }
    public function setText($text)
    {
        $this->text = $text;
    }
}
