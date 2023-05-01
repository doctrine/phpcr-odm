<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\SessionInterface;

/**
 * Translation strategy that stores the translations in a child nodes of the current node.
 *
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author David Buchmann <david@liip.ch>
 */
class ChildTranslationStrategy extends AttributeTranslationStrategy implements TranslationNodesWarmer
{
    /**
     * Identifier of this strategy.
     */
    public const NAME = 'child';

    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, ?string $locale): void
    {
        if (null === $locale) {
            throw new PHPCRException('locale may not be null');
        }
        $translationNode = $this->getTranslationNode($node, $locale);
        parent::saveTranslation($data, $translationNode, $metadata, $locale);
    }

    public function loadTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): bool
    {
        $translationNode = $this->getTranslationNode($node, $locale, false);
        if (!$translationNode) {
            return false;
        }

        return parent::loadTranslation($document, $translationNode, $metadata, $locale);
    }

    public function removeTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): void
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        $translationNode->remove();
    }

    public function removeAllTranslations(object $document, NodeInterface $node, ClassMetadata $metadata): void
    {
        $locales = $this->getLocalesFor($document, $node, $metadata);
        foreach ($locales as $locale) {
            $this->removeTranslation($document, $node, $metadata, $locale);
        }
    }

    public function getLocalesFor(object $document, NodeInterface $node, ClassMetadata $metadata): array
    {
        $translations = $node->getNodes(Translation::LOCALE_NAMESPACE.':*');
        $locales = [];
        foreach ($translations as $name => $translationNode) {
            if ($p = strpos($name, ':')) {
                $locales[] = substr($name, $p + 1);
            }
        }

        return $locales;
    }

    /**
     * Get the child node with the translation. If create is true, the child
     * node is created if not existing.
     *
     * @param bool $create whether to create the node if it is
     *                     not yet existing
     *
     * @return bool|NodeInterface the node or false if $create is false and
     *                            the node is not existing
     */
    private function getTranslationNode(NodeInterface $parentNode, string $locale, bool $create = true)
    {
        $name = Translation::LOCALE_NAMESPACE.":$locale";
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
     * {@inheritdoc}
     *
     * We namespace the property by putting it in a different node, the name
     * itself does not change.
     */
    public function getTranslatedPropertyName(string $locale, string $propertyName): string
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     *
     * We need to select the field on the joined child node.
     */
    public function getTranslatedPropertyPath(string $alias, string $propertyName, string $locale): array
    {
        $childAlias = sprintf('_%s_%s', $locale, $alias);

        return [$childAlias, $this->getTranslatedPropertyName($locale, $propertyName)];
    }

    /**
     * {@inheritdoc}
     *
     * Join document with translation children, and filter on the right child
     * node.
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        string $alias,
        string $locale
    ): void {
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
            $qomf->literal(Translation::LOCALE_NAMESPACE.":$locale")
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

    public function getTranslationsForNodes(iterable $nodes, array $locales, SessionInterface $session)
    {
        $absolutePaths = [];

        foreach ($locales as $locale) {
            foreach ($nodes as $node) {
                $absolutePaths[] = $node->getPath().'/'.Translation::LOCALE_NAMESPACE.':'.$locale;
            }
        }

        return $session->getNodes($absolutePaths);
    }
}
