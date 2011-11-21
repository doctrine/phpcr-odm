<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="translation_article", translator="attribute")
 */
class Article
{
    /** @PHPCRODM\Id */
    public $id;

    /**
     * @PHPCRODM\Locale
     */
    public $locale = 'en';

    // untranslated:
    /** @PHPCRODM\Date */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\String */
    public $author;

    /** @PHPCRODM\String(translated=true) */
    public $topic;

    /** @PHPCRODM\String(translated=true) */
    public $text;
}
