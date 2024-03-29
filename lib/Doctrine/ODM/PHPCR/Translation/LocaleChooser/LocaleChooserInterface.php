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
    public function setLocalePreference(array $localePreference): void;

    /**
     * Set or update the order of fallback locales for the selected locale.
     *
     * @param string $locale  the locale to update the fallback order for
     * @param array  $order   an order of locales to try as fallback
     * @param bool   $replace whether to append existing locales to the end or
     *                        replace the whole fallback order
     */
    public function setFallbackLocales(string $locale, array $order, bool $replace): void;

    /**
     * Gets an ordered list of locales to try as fallback for a locale.
     *
     * Example return value with param $forLocale = 'en':
     *     array('fr', 'de')
     *
     * @param ClassMetadata $metadata  The metadata of the document class
     * @param string|null   $forLocale Locale for which you want the fallback
     *                                 order, e.g. the current request locale.
     *                                 If null, the default locale is to be used.
     *
     * @return string[] $preferredLocales
     *
     * @throws MissingTranslationException
     */
    public function getFallbackLocales(?object $document, ClassMetadata $metadata, string $forLocale = null): array;

    /**
     * Get the locale of the current session.
     */
    public function getLocale(): string;

    /**
     * Set the locale of the current session.
     */
    public function setLocale(string $locale): void;

    /**
     * Get the ordered list of locales for the default locale without any
     * context.
     *
     * This list has to include the default locale as first element.
     *
     * @return string[] preferred locale order for the default locale
     */
    public function getDefaultLocalesOrder(): array;

    /**
     * Get the default locale of this application. This should never change,
     * regardless of the current session or context.
     */
    public function getDefaultLocale(): string;
}

interface_exists(ClassMetadata::class);
