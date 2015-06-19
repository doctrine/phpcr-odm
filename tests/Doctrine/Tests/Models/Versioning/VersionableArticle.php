<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="simple")
 */
class VersionableArticle
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $author;

    /** @PHPCRODM\Field(type="string") */
    public $topic;

    /** @PHPCRODM\Field(type="string", nullable=true) */
    private $text;

    public function getText()
    {
        return $this->text;
    }
    public function setText($text)
    {
        $this->text = $text;
    }
}
