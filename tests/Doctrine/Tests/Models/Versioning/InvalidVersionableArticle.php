<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="some_invalid_versioning_type")
 */
class InvalidVersionableArticle
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\String */
    public $author;

    /** @PHPCRODM\String */
    public $topic;

    /** @PHPCRODM\String */
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
