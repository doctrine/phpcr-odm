<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint the evaluates to true if the named field on the
 * aliased document evaluates to true against the given full text
 * search expression.
 *
 * See: http://docs.jboss.org/jbossdna/0.7/manuals/reference/html/jcr-query-and-search.html#fulltext-search-query-language
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintFullTextSearch extends AbstractLeafNode
{
    private string $alias;
    private string $field;
    private string $fullTextSearchExpression;

    public function __construct(AbstractNode $parent, string $field, string $fullTextSearchExpression)
    {
        [$alias, $field] = $this->explodeField($field);
        $this->alias = $alias;
        $this->field = $field;
        $this->fullTextSearchExpression = $fullTextSearchExpression;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getFullTextSearchExpression(): string
    {
        return $this->fullTextSearchExpression;
    }
}
