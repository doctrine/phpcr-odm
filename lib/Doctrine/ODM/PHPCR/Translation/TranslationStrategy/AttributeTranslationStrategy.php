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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

use Doctrine\ODM\PHPCR\Translation\Translation;

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
        $nullFields = array();
        foreach ($data as $field => $propValue) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            $mapping = $metadata->mappings[$field];
            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (isset($mapping['assoc'])) {
                    $node->setProperty($this->getTranslatedPropertyName($locale, $mapping['assoc']), array_keys($propValue));
                    $propValue = array_values($propValue);
                }
            }

            $node->setProperty($propName, $propValue);

            if (null === $propValue) {
                $nullFields[] = $field;
            }
        }
        if (empty($nullFields)) {
            $nullFields = null;
        }
        $node->setProperty($this->prefix . ':' . $locale . self::NULLFIELDS, $nullFields); // no '-' to avoid nameclashes
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        if ($node->hasProperty($this->prefix . ':' . $locale . self::NULLFIELDS)) {
            $nullFields = $node->getPropertyValue($this->prefix . ':' . $locale . self::NULLFIELDS);
            $nullFields = array_flip($nullFields);
        } else {
            $nullFields = array();
        }
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            if (isset($nullFields[$field])) {
                $value = null;
            } elseif ($node->hasProperty($propName)) {
                $value = $node->getPropertyValue($propName);
                $mapping = $metadata->mappings[$field];
                if (true === $mapping['multivalue'] && isset($mapping['assoc'])) {
                    $keysPropName = $this->getTranslatedPropertyName($locale, $mapping['assoc']);
                    if ($node->hasProperty($keysPropName)) {
                        $value = array_combine((array) $node->getPropertyValue($keysPropName), (array) $value);
                    }
                }
            } else {
                // Could not find the translation in the given language
                return false;
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
            $propName = $this->getTranslatedPropertyName($locale, $field);
            if ($node->hasProperty($propName)) {
                $prop = $node->getProperty($propName);
                $prop->remove();

                $mapping = $metadata->mappings[$field];
                if (true === $mapping['multivalue'] && isset($mapping['assoc'])) {
                    $keysPropName = $this->getTranslatedPropertyName($locale, $mapping['assoc']);
                    if ($node->hasProperty($keysPropName)) {
                        $prop = $node->getProperty($keysPropName);
                        $prop->remove();
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        // Do nothing: if the node is removed then all it's translated properties will be removed
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $locales = array();
        foreach ($node->getProperties("*{$this->prefix}*") as $prop) {
            $matches = null;
            if (preg_match('/' . $this->prefix . ':(..)-[^-]*/', $prop->getName(), $matches)) {
                if (is_array($matches) && count($matches) > 1 && !in_array($matches[1], $locales)) {
                    $locales[] = $matches[1];
                }
            }
        }

        return $locales;
    }
}
