<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Interface to get the list of preferred locales.  This is to facilitate falling
 * back to another locale in case there is no translation present for the
 * desired locale.
 *
 * @author brian () liip.ch
 */
interface LocaleChooserInterface
{
    /**
     * Gets an ordered list of preferred locales.
     *
     * Example return value with param $forLocale = 'en':
     *  array('en', 'fr', 'de')
     *
     * @param $document The document object
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata The metadata of the document class
     * @param string $forLocale for which locale you need the locale order, e.g. the current request locale
     *
     * @return array $preferredLocales
     */
    function getPreferredLocalesOrder($document, ClassMetadata $metadata, $forLocale = null);

    /**
     * Get the ordered list of locales for the default locale without any
     * context
     *
     * @return array preferred locale order for the default locale
     */
    function getDefaultLocalesOrder();

    /**
     * @return string defaultLocale
     */
    function getDefaultLocale();
}
