<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Translatable document that does not provide an explicit locale field.
 *
 * !!! WARNING !!!
 *
 * This class is invalid as it uses an invalid key for the @PHPCRODM\Document(translator) annotation.
 * This class is supposed to throw an exception when it is read by the ODM !!!
 *
 */

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class NoLocalePropertyArticle
{
    /** @PHPCRODM\Id */
    public $id;

    // untranslated:
    /** @PHPCRODM\Field(type="date") */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\Field(type="string") */
    public $author;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $topic;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $text;
}
