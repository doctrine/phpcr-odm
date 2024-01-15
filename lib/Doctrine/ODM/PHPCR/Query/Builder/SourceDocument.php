<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;

class SourceDocument extends AbstractLeafNode
{
    private string $documentFqn;

    private string $alias;

    public function __construct(AbstractNode $parent, string $documentFqn, string $alias)
    {
        if ('' === $alias) {
            throw new InvalidArgumentException(sprintf(
                'The alias for %s must be a non-empty string.',
                $documentFqn
            ));
        }

        $this->documentFqn = $documentFqn;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getDocumentFqn(): string
    {
        return $this->documentFqn;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE;
    }
}
