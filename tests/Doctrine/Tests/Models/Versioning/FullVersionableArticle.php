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

    /** @PHPCRODM\Field(type="string") */
    public $author;

    /** @PHPCRODM\Field(type="string") */
    public $topic;

    /** @PHPCRODM\Field(type="string") */
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
