<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Translation\LocaleChooser;

use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Exception\MissingTranslationException;

/**
 * Class to get the list of preferred locales.
 *
 * @author brian@liip.ch
 * @author david@liip.ch
 */
class LocaleChooser implements LocaleChooserInterface
{
    /**
     * locale fallback list indexed by source locale
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
     * The current locale to use
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
     * @param array $localePreference array of arrays with a preferred locale order list
     *      for each locale
     * @param string $defaultLocale the default locale to be used if locale is not set
     */
    public function __construct($localePreference, $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
        $this->setLocalePreference($localePreference);
    }

    /**
     * @param array $localePreference array of arrays with a preferred locale order list
     *      for each locale
     *
     * @throws MissingTranslationException
     */
    public function setLocalePreference($localePreference)
    {
        if (! isset($localePreference[$this->defaultLocale])) {
            throw new MissingTranslationException("The supplied list of locales does not contain '$this->defaultLocale'");
        }

        $this->localePreference = $localePreference;
    }

    /**
     * Gets an ordered list of preferred locales.
     *
     * If forLocale is not present in the list of preferred locales, return the preference order for the defaultLocale.
     *
     * @param object        $document  The document object
     * @param ClassMetadata $metadata  The metadata of the document class
     * @param string        $forLocale for which locale you need the locale order, e.g. the current request locale
     *
     * @return array $preferredLocales
     *
     * @throws MissingTranslationException
     */
    public function getPreferredLocalesOrder($document, ClassMetadata $metadata, $forLocale = null)
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
     * {@inheritDoc}
     */
    public function getDefaultLocalesOrder()
    {
        return $this->localePreference[$this->defaultLocale];
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        if (empty($this->locale)) {
            return $this->defaultLocale;
        }

        return $this->locale;
    }

    /**
     * {@inheritDoc}
     *
     * @throws MissingTranslationException if the specified locale is not defined in the $localePreference array.
     */
    public function setLocale($locale)
    {
        if (! isset($this->localePreference[$locale])) {
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
     * {@inheritDoc}
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
}
