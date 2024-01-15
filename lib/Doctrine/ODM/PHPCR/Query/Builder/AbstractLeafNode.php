<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;

/**
 * Special class for leaf nodes. Leaf nodes have no children
 * and always return the parent rather than themselves.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractLeafNode extends AbstractNode
{
    public function getNext(): ?AbstractNode
    {
        return $this->getParent();
    }

    public function getChildren(): array
    {
        throw new RuntimeException(sprintf(
            'Cannot call getChildren on leaf node "%s"',
            $this->getName()
        ));
    }

    public function addChild(AbstractNode $node): static
    {
        throw new RuntimeException(sprintf(
            'Cannot call addChild to leaf node "%s"',
            $this->getName()
        ));
    }

    public function getCardinalityMap(): array
    {
        // no children , no cardinality map...
        return [];
    }

    /**
     * Return the alias name and field name
     * from the given string of form.
     *
     *     [alias].[field_name]
     *
     * e.g. my_alias.first_name
     *
     * @return string[] with exactly 2 elements
     */
    protected function explodeField(string $field): array
    {
        $parts = explode('.', $field);

        if (2 === count($parts)) {
            return $parts;
        }

        throw new RuntimeException(sprintf(
            'Invalid field specification, '.
            'expected string like "[alias].[field_name]", got "%s"',
            $field
        ));
    }
}
