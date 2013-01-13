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
     * @PHPCRODM\Date
     */
    private $publishDate;

    /**
     * Translated property
     * @PHPCRODM\String(translated=true)
     */
    private $topic;

    /**
     * Language specific image
     * @PHPCRODM\Binary(translated=true)
     */
    private $image;
}