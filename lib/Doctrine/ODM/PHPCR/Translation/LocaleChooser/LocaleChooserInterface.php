<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\Persistence\Mapping\ClassMetadata;

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
 * @author Brian King <brian@liip.ch>
 */
interface LocaleChooserInterface
{
    /**
     * Set the preferences.
     *
     * For example:
     *
     * array(
     *      'en' => array('fr', 'de'),
     *      'fr' => array('en'),
     *      'de' => array('en),
     * )
     *
     * @param array $localePreference array of arrays with a preferred locale
     *                                order list for each locale
     *
     * @throws MissingTranslationException if no entry for the default locale
     *                                     is found in $localePreference
     */
    public function setLocalePreference($localePreference);

    /**
     * Set or update the order of fallback locales for the selected locale.
     *
     * @param string $locale  the locale to update the fallback order for
     * @param array  $order   an order of locales to try as fallback
     * @param bool   $replace whether to append existing locales to the end or
     *                        replace the whole fallback order
     */
    public function setFallbackLocales($locale, array $order, $replace = false);

    /**
     * Gets an ordered list of locales to try as fallback for a locale.
     *
     * Example return value with param $forLocale = 'en':
     *     array('fr', 'de')
     *
     * @param object        $document  The document object
     * @param ClassMetadata $metadata  The metadata of the document class
     * @param string|null   $forLocale Locale for which you want the fallback
     *                                 order, e.g. the current request locale.
     *                                 If null, the default locale is to be used.
     *
     * @throws MissingTranslationException
     *
     * @return array $preferredLocales
     */
    public function getFallbackLocales($document, ClassMetadata $metadata, $forLocale = null);

    /**
     * Get the locale of the current session.
     *
     * @return string locale
     */
    public function getLocale();

    /**
     * Set the locale of the current session.
     *
     * @param string $locale
     */
    public function setLocale($locale);

    /**
     * Get the ordered list of locales for the default locale without any
     * context.
     *
     * This list has to include the default locale as first element.
     *
     * @return array preferred locale order for the default locale
     */
    public function getDefaultLocalesOrder();

    /**
     * Get the default locale of this application. This should never change,
     * regardless of the current session or context.
     *
     * @return string defaultLocale
     */
    public function getDefaultLocale();
}

interface_exists(ClassMetadata::class);
