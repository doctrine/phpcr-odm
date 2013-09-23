<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

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
    protected $alias;
    protected $field;
    protected $fullTextSearchExpression;

    Public function __construct(AbstractNode $parent, $field, $fullTextSearchExpression)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->alias = $alias;
        $this->field = $field;
        $this->fullTextSearchExpression = $fullTextSearchExpression;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getFullTextSearchExpression()
    {
        return $this->fullTextSearchExpression;
    }
}
