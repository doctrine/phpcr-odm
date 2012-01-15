<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class Article
{
    /** @PHPCRODM\Id */
    public $id;
    /**
     * @PHPCRODM\Locale
     */
    public $locale = 'en';
    /**
     * @PHPCRODM\ParentDocument */
    public $parent;

    // untranslated:
    /** @PHPCRODM\Date */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\String */
    public $author;

    /** @PHPCRODM\String(translated=true) */
    public $topic;

    /** @PHPCRODM\String(translated=true) */
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
