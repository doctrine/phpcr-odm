<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Translatable document that does not provide an explicit locale field.
 *
 * @PHPCRODM\Document(alias="translation_article", translator="attribute")
 */
class Article
{
    /** @PHPCRODM\Id */
    public $id;

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
