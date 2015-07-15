<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(versionable="full")
 */
class ExtendedVersionableArticle extends FullVersionableArticle
{
    /** @PHPCRODM\Property(type="string") */
    public $author;

    /** @PHPCRODM\Property(type="string") */
    public $topic;

    /** @PHPCRODM\Property(type="string") */
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
