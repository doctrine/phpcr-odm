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
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SelectorInterface;
use PHPCR\SessionInterface;

/**
 * Translation strategy that stores the translations in a child nodes of the current node.
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 */
class ChildTranslationStrategy extends AttributeTranslationStrategy implements TranslationNodesWarmer
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
        $translationNode = $this->getTranslationNode($node, $locale, false);
        if (!$translationNode) {
            return false;
        }

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

    /**
     * Get the child node with the translation. If create is true, the child
     * node is created if not existing.
     *
     * @param NodeInterface $parentNode
     * @param string        $locale
     * @param boolean       $create      whether to create the node if it is
     *      not yet existing
     *
     * @return boolean|NodeInterface the node or false if $create is false and
     *      the node is not existing.
     */
    protected function getTranslationNode(NodeInterface $parentNode, $locale, $create = true)
    {
        $name = Translation::LOCALE_NAMESPACE . ":$locale";
        if (!$parentNode->hasNode($name)) {
            if (!$create) {
                return false;
            }
            $node = $parentNode->addNode($name);
        } else {
            $node = $parentNode->getNode($name);
        }

        return $node;
    }

    /**
     * {@inheritDoc}
     *
     * We namespace the property by putting it in a different node, the name
     * itself does not change.
     */
    public function getTranslatedPropertyName($locale, $fieldName)
    {
        return $fieldName;
    }

    /**
     * {@inheritDoc}
     *
     * We need to select the field on the joined child node.
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale)
    {
        $childAlias = sprintf('_%s_%s', $locale, $alias);

        return array($childAlias, $this->getTranslatedPropertyName($locale, $propertyName));
    }

    /**
     * {@inheritDoc}
     *
     * Join document with translation children, and filter on the right child
     * node.
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SelectorInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    ) {
        $childAlias = "_{$locale}_{$alias}";

        $selector = $qomf->join(
            $selector,
            $qomf->selector($childAlias, 'nt:base'),
            QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_RIGHT_OUTER,
            $qomf->childNodeJoinCondition($childAlias, $alias)
        );

        $languageConstraint = $qomf->comparison(
            $qomf->nodeName($childAlias),
            QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
            $qomf->literal(Translation::LOCALE_NAMESPACE . ":$locale")
        );

        if ($constraint) {
            $constraint = $qomf->andConstraint(
                $constraint,
                $languageConstraint
            );
        } else {
            $constraint = $languageConstraint;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslationsForNodes($nodes, $locales, SessionInterface $session)
    {
        $absolutePaths = array();

        foreach ($locales as $locale) {
            foreach ($nodes as $node) {
                $absolutePaths[] = $node->getPath().'/'.Translation::LOCALE_NAMESPACE.':'.$locale;
            }
        }

        return $session->getNodes($absolutePaths);
    }
}
