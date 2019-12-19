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

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @see        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 */
abstract class AbstractTranslationStrategy implements TranslationStrategyInterface
{
    /**
     * @var DocumentManagerInterface
     */
    protected $dm;

    /**
     * Prefix to namespace properties or child nodes.
     *
     * @var string
     */
    protected $prefix = Translation::LOCALE_NAMESPACE;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Set the namespace alias for translation extra properties
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Determine the locale specific property name.
     *
     * @param string $locale
     * @param string $propertyName the untranslated property name
     *
     * @return string the property name with the translation namespace
     */
    public function getTranslatedPropertyName($locale, $propertyName)
    {
        return sprintf('%s:%s-%s', $this->prefix, $locale, $propertyName);
    }

    /**
     * Determine the locale specific property names for an assoc property
     *
     * @param string $locale
     * @param array  $mapping the mapping for the property
     *
     * @return string the property name with the translation namespace
     */
    public function getTranslatedPropertyNameAssoc($locale, $mapping)
    {
        return [
            'property' => $this->getTranslatedPropertyName($locale, $mapping['property']),
            'assoc' => $this->getTranslatedPropertyName($locale, $mapping['assoc']),
            'assocNulls' => $this->getTranslatedPropertyName($locale, $mapping['assocNulls']),
        ];
    }
}
