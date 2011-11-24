<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

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
     * @param string $forLocale for which locale you need the locale order, e.g. the current request locale
     *
     * @return array $preferredLocales
     */
    public function getPreferredLocalesOrder($forLocale = null);

    /**
     * Get the ordered list of locales for the default locale
     *
     * @return array preferred locale order for the default locale
     */
    public function getDefaultLocalesOrder();

    /**
     * @return string defaultLocale
     */
    public function getDefaultLocale();
}
