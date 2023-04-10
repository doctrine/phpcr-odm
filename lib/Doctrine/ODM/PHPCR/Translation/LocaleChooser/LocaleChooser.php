<?php

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Class to get the list of preferred locales.
 *
 * @author brian@liip.ch
 * @author david@liip.ch
 */
final class LocaleChooser implements LocaleChooserInterface
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
    private array $localePreference;

    /**
     * The current locale to use.
     */
    private ?string $locale = null;

    /**
     * The default locale of the system used for getDefaultLocalesOrder
     * and as fallback if locale is not set.
     */
    private string $defaultLocale;

    /**
     * @param array  $localePreference array of arrays with a preferred locale order list
     *                                 for each locale
     * @param string $defaultLocale    the default locale to be used if locale is not set
     */
    public function __construct(array $localePreference, string $defaultLocale)
    {
        $this->setLocalePreferenceAndDefaultLocale($localePreference, $defaultLocale);
    }

    public function setLocalePreference(array $localePreference): void
    {
        if (!array_key_exists($this->defaultLocale, $localePreference)) {
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
    public function setLocalePreferenceAndDefaultLocale(array $localePreference, string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
        $this->setLocalePreference($localePreference);
    }

    public function setFallbackLocales(string $locale, array $order, bool $replace): void
    {
        if (!$replace && array_key_exists($locale, $this->localePreference)) {
            foreach ($this->localePreference[$locale] as $oldLocale) {
                if (!in_array($oldLocale, $order, true)) {
                    $order[] = $oldLocale;
                }
            }
        }

        $this->localePreference[$locale] = $order;
    }

    public function getFallbackLocales(?object $document, ClassMetadata $metadata, string $forLocale = null): array
    {
        if (is_null($forLocale)) {
            return $this->localePreference[$this->getLocale()];
        }
        if (array_key_exists($forLocale, $this->localePreference)) {
            return $this->localePreference[$forLocale];
        }

        throw new MissingTranslationException("There is no language fallback for language '$forLocale'");
    }

    public function getDefaultLocalesOrder(): array
    {
        $locales = $this->localePreference[$this->defaultLocale];
        array_unshift($locales, $this->defaultLocale);

        return $locales;
    }

    public function getLocale(): string
    {
        return $this->locale ?: $this->defaultLocale;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MissingTranslationException if the specified locale is not defined in the $localePreference array
     */
    public function setLocale(string $locale): void
    {
        if (!array_key_exists($locale, $this->localePreference)) {
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

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
}

interface_exists(ClassMetadata::class);
