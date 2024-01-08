<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * An invalid class with translated fields but no translator.
 */
#[PHPCR\Document]
class TranslatorMappingObjectNoStrategy
{
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
