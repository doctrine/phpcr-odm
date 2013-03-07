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
    Doctrine\ODM\PHPCR\Translation\Translation,
    PHPCR\NodeInterface;

/**
 * Translation strategy that stores the translations in a child nodes of the current node.
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 */
class ChildTranslationStrategy extends AttributeTranslationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        parent::saveTranslation($data, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);

        return parent::loadTranslation($document, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        $translationNode->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $locales = $this->getLocalesFor($document, $node, $metadata);
        foreach ($locales as $locale) {
            $this->removeTranslation($document, $node, $metadata, $locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $translations = $node->getNodes(Translation::LOCALE_NAMESPACE . ':*');
        $locales = array();
        foreach ($translations as $name => $node) {
            if ($p = strpos($name, ':')) {
                $locales[] = substr($name, $p+1);
            }
        }

        return $locales;
    }

    protected function getTranslationNode(NodeInterface $parentNode, $locale)
    {
        $name = Translation::LOCALE_NAMESPACE . ":$locale";
        if (!$parentNode->hasNode($name)) {
            $node = $parentNode->addNode($name);
        } else {
            $node = $parentNode->getNode($name);
        }

        return $node;
    }

    protected function getTranslatedPropertyName($locale, $fieldName)
    {
        return $fieldName;
    }
}
