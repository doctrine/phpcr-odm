<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface,
    Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Class to get the list of preferred locales.
 *
 * @author brian () liip.ch
 */
class LocaleChooser implements LocaleChooserInterface
{
    /**
     * $localePreference example:
     *  array(
     *    'en' => array('en', 'de', 'fr'),
     *    'fr' => array('fr', 'de', 'en'),
     *    'de' => array('de', 'fr', 'en'),
     *  )
     */
    protected $localePreference;
    protected $defaultLocale;

    /**
     * @param array $localePreference array of arrays with a preffered locale order list
     *      for each locale
     * @param string $defaultLocale the default locale
     */
    public function __construct($localePreference, $defaultLocale)
    {
        $this->localePreference = $localePreference;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Gets an ordered list of preferred locales.
     *
     * If forLocale is not present in the list of preferred locales, return the preference order for the defaultLocale.
     *
     * @param $document The document object
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata The metadata of the document class
     * @param string $forLocale for which locale you need the locale order, e.g. the current request locale
     *
     * @return array $preferredLocales
     */
    public function getPreferredLocalesOrder($document, ClassMetadata $metadata, $forLocale = null)
    {
        if (is_null($forLocale)) {
            return $this->localePreference[$this->defaultLocale];
        } elseif (array_key_exists($forLocale, $this->localePreference)) {
            return $this->localePreference[$forLocale];
        }

        throw new \InvalidArgumentException("There is no language fallback for language '$forLocale'");
    }

    /**
     * Get the ordered list of locales for the default locale
     *
     * @return array preferred locale order for the default locale
     */
    public function getDefaultLocalesOrder()
    {
        return $this->getPreferredLocales($this->defaultLocale);
    }

    /**
     * @return string defaultLocale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set the default locale
     *
     * @throws InvalidArgumentException if the specified locale is not defined in the $localePreference array.
     */
    public function setDefaultLocale($locale)
    {
        if (! array_key_exists($locale, $this->localePreference)) {
            throw new \InvalidArgumentException("The locale '$locale' is not present in the list of available locales");
        }

        $this->defaultLocale = $locale;
    }


}
