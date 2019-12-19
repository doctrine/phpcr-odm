<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;

/**
 * Class to get the list of preferred locales.
 *
 * @author brian@liip.ch
 * @author david@liip.ch
 */
class LocaleChooser implements LocaleChooserInterface
{
    /**
     * locale fallback list indexed by source locale.
     *
     * example:
     *  array(
     *    'en' => array('en', 'de', 'fr'),
     *    'fr' => array('fr', 'de', 'en'),
     *    'de' => array('de', 'fr', 'en'),
     *  )
     */
    protected $localePreference;

    /**
     * The current locale to use.
     *
     * @var string
     */
    protected $locale;

    /**
     * The default locale of the system used for getDefaultLocalesOrder
     * and as fallback if locale is not set.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * @param array  $localePreference array of arrays with a preferred locale order list
     *                                 for each locale
     * @param string $defaultLocale    the default locale to be used if locale is not set
     */
    public function __construct($localePreference, $defaultLocale)
    {
        $this->setLocalePreferenceAndDefaultLocale($localePreference, $defaultLocale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalePreference($localePreference)
    {
        if (!isset($localePreference[$this->defaultLocale])) {
            throw new MissingTranslationException("The supplied list of locales does not contain '$this->defaultLocale'");
        }

        $this->localePreference = $localePreference;
    }

    /**
     * Update the localePreferences and the defaultLocale at once.
     *
     * Update both parameters at once to be able to specify a new defaultLocale that was previously
     * not contained in the localePreferences.
     *
     * @param array  $localePreference array of arrays with a preferred locale order list
     *                                 for each locale
     * @param string $defaultLocale    the default locale to be used if locale is not set
     */
    public function setLocalePreferenceAndDefaultLocale($localePreference, $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
        $this->setLocalePreference($localePreference);
    }

    /**
     * {@inheritdoc}
     */
    public function setFallbackLocales($locale, array $order, $replace = false)
    {
        if (!$replace && isset($this->localePreference[$locale])) {
            foreach ($this->localePreference[$locale] as $oldLocale) {
                if (!in_array($oldLocale, $order)) {
                    $order[] = $oldLocale;
                }
            }
        }

        $this->localePreference[$locale] = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackLocales($document, ClassMetadata $metadata, $forLocale = null)
    {
        if (is_null($forLocale)) {
            return $this->localePreference[$this->getLocale()];
        }
        if (array_key_exists($forLocale, $this->localePreference)) {
            return $this->localePreference[$forLocale];
        }

        throw new MissingTranslationException("There is no language fallback for language '$forLocale'");
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocalesOrder()
    {
        $locales = $this->localePreference[$this->defaultLocale];
        array_unshift($locales, $this->defaultLocale);

        return $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        if (empty($this->locale)) {
            return $this->defaultLocale;
        }

        return $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MissingTranslationException if the specified locale is not defined in the $localePreference array
     */
    public function setLocale($locale)
    {
        if (!isset($this->localePreference[$locale])) {
            $localeBase = substr($locale, 0, 2);

            // Strip region from locale if not configured
            if (empty($this->localePreference[$localeBase])) {
                throw new MissingTranslationException(
                    "The locale '$locale' is not present in the list of available locales"
                );
            }

            $locale = $localeBase;
        }

        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
}
