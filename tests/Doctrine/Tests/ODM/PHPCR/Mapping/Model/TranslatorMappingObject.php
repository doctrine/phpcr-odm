<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses translator and translatable.
 */
#[PHPCR\Document(translator: 'attribute')]
class TranslatorMappingObject
{
    /**
     * The path.
     */
    #[PHPCR\Id]
    private $id;

    /**
     * The language this document currently is in.
     */
    #[PHPCR\Locale]
    private $doclocale;

    /**
     * Untranslated property.
     */
    #[PHPCR\Field(type: 'date')]
    private $publishDate;

    /**
     * Translated property.
     */
    #[PHPCR\Field(type: 'string', translated: true)]
    private $topic;

    /**
     * Language specific image.
     */
    #[PHPCR\Field(type: 'binary', translated: true)]
    private $image;
}
