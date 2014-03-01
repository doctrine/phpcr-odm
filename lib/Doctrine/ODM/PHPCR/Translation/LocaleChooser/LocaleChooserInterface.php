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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;

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
     *      order list for each locale
     *
     * @throws MissingTranslationException if no entry for the default locale
     *      is found in $localePreference
     */
    public function setLocalePreference($localePreference);

    /**
     * Set or update the order of fallback locales for the selected locale.
     *
     * @param string $locale  The locale to update the fallback order for.
     * @param array  $order   An order of locales to try as fallback.
     * @param bool   $replace Whether to append existing locales to the end or
     *                        replace the whole fallback order.
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
     * @return array $preferredLocales
     *
     * @throws MissingTranslationException
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
