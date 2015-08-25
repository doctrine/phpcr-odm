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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\NodeInterface;
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * Translation strategy that stores the translations in attributes of the same node.
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 */
class AttributeTranslationStrategy extends AbstractTranslationStrategy
{
    const NULLFIELDS = 'nullfields';

    /**
     * {@inheritdoc}
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        // no need to validate non-nullable condition, the UoW does that for all fields
        $nullFields = array();
        foreach ($data as $field => $propValue) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);

            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (isset($mapping['assoc'])) {
                    $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                    $propValue = $this->dm->getUnitOfWork()->processAssoc($node, $transMapping, $propValue);
                }
            }

            $node->setProperty($propName, $propValue);

            if (null === $propValue) {
                $nullFields[] = $mapping['property'];
            }
        }
        if (empty($nullFields)) {
            $nullFields = null;
        }
        $node->setProperty($this->prefix . ':' . $locale . self::NULLFIELDS, $nullFields); // no '-' to avoid name clashes
    }

    /**
     * Helper method to detect if there is any translated field at all, to
     * not null all fields if the locale does not exist.
     *
     * @param NodeInterface $node
     * @param ClassMetadata $metadata
     * @param string        $locale
     *
     * @return bool Whether the node has any attribute of the desired locale.
     */
    private function checkHasFields(NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        if ($node->hasProperty($this->prefix . ':' . $locale . self::NULLFIELDS)) {
            return true;
        }

        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);
            if ($node->hasProperty($propName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        if (!$this->checkHasFields($node, $metadata, $locale)) {
            return false;
        }

        $properties = $node->getPropertiesValues(null, false);

        // we have a translation, now update the document fields
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);
            if ($node->hasProperty($propName)) {
                $value = $node->getPropertyValue($propName);
                if (true === $mapping['multivalue']) {
                    if (isset($mapping['assoc'])) {
                        $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                        $value = $this->dm->getUnitOfWork()->createAssoc($properties, $transMapping);
                    } else {
                        $value = (array) $value;
                    }
                }
            } else {
                // A null field or a missing field
                $value = ($metadata->mappings[$field]['multivalue']) ? array() : null;
            }

            $metadata->reflFields[$field]->setValue($document, $value);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);

            if ($node->hasProperty($propName)) {
                $prop = $node->getProperty($propName);
                $prop->remove();

                $mapping = $metadata->mappings[$field];
                if (true === $mapping['multivalue'] && isset($mapping['assoc'])) {
                    $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                    $this->dm->getUnitOfWork()->removeAssoc($node, $transMapping);
                }
            }
        }

        if ($node->hasProperty($this->prefix . ':' . $locale . self::NULLFIELDS)) {
            $node->setProperty($this->prefix . ':' . $locale . self::NULLFIELDS, null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        foreach ($this->getLocalesFor($document, $node, $metadata) as $locale) {
            foreach ($metadata->translatableFields as $field) {
                $node->setProperty(
                    $this->getTranslatedPropertyName($locale, $metadata->mappings[$field]['property']),
                    null
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $locales = array();
        foreach ($node->getProperties($this->prefix . ':*') as $prop) {
            $matches = null;
            if (preg_match('/' . $this->prefix . ':([a-zA-Z1-9_]+)-/', $prop->getName(), $matches)) {
                if (is_array($matches) && count($matches) > 1 && !in_array($matches[1], $locales)) {
                    $locales[] = $matches[1];
                }
            }
        }

        return $locales;
    }

    /**
     * {@inheritDoc}
     *
     * Translated properties are on the same node, but have a different name.
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale)
    {
        return array($alias, $this->getTranslatedPropertyName($locale, $propertyName));
    }

    /**
     * {@inheritDoc}
     *
     * Nothing to do, the properties are on the same node.
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    ) {
        // do nothing
    }
}
