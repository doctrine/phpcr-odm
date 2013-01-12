<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * An invalid class with translated fields but no translator
 *
 * @PHPCRODM\Document()
 */
class TranslatorMappingObjectNoStrategy
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
