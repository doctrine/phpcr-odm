<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Interface to get the list of preferred locales.
 *
 * This is to facilitate falling  back to another locale in case there is no
 * translation present for the desired locale.
 *
 * We distinguish the current locale (getLocale) and the system default locale.
 * The idea is that there is a default that never changes, which is also used
 * for getDefaultLocalesOrder. In some situations, you want a fixed order of
 * available languages, regardless of the current users preferences.
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
     * Get the locale of the current session.
     *
     * @return string locale
     */
    function getLocale();

    /**
     * Get the ordered list of locales for the default locale without any
     * context
     *
     * @return array preferred locale order for the default locale
     */
    function getDefaultLocalesOrder();

    /**
     * Get the default locale of this application. This should never change,
     * regardless of the current session or context.
     *
     * @return string defaultLocale
     */
    function getDefaultLocale();
}
