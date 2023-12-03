<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * Translatable document that does not provide an explicit locale field.
 *
 * !!! WARNING !!!
 *
 * This class is invalid as it uses an invalid key for the @PHPCRODM\Document(translator) annotation.
 * This class is supposed to throw an exception when it is read by the ODM !!!
 */

#[PHPCR\Document(translator: 'attribute')]
class NoLocalePropertyArticle
{
    #[PHPCR\Id]
    public $id;

    // untranslated:

    #[PHPCR\Field(type: 'date')]
    public $publishDate;

    // untranslated:

    #[PHPCR\Field(type: 'string')]
    public $author;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $topic;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $text;
}
