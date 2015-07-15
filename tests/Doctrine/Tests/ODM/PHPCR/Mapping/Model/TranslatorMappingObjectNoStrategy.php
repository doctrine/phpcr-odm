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
     * @PHPCRODM\Property(type="date")
     */
    private $publishDate;

    /**
     * Translated property
     * @PHPCRODM\Property(type="string", translated=true)
     */
    private $topic;

    /**
     * Language specific image
     * @PHPCRODM\Property(type="binary", translated=true)
     */
    private $image;
}
