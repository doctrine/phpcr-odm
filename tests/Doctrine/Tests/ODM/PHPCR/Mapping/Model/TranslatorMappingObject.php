<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses translator and translatable
 *
 * @PHPCRODM\Document(translator="attribute")
 */
class TranslatorMappingObject
{
    /**
     * The path
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * The language this document currently is in
     * @PHPCRODM\Locale
     */
    private $doclocale;

    /**
     * Untranslated property
     * @PHPCRODM\Field(type="date")
     */
    private $publishDate;

    /**
     * Translated property
     * @PHPCRODM\Field(type="string", translated=true)
     */
    private $topic;

    /**
     * Language specific image
     * @PHPCRODM\Field(type="binary", translated=true)
     */
    private $image;
}
