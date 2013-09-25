<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

/**
 * Constraint which evalues to true if the specified field on
 * the aliased document isset, or alternatively speaking, is not-null.
 *
 * This constraint is equivalent to PHPCR PropertyExistanceInterface QOM
 * interface, which checks to see if a property actually exists.
 *
 * The PHPCR-ODM will remove properties at the PHPCR level when they
 * are null, so the PHPCR concept "property exists" translates to "not null"
 * when we are in the PHPCR-ODM level.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintFieldIsset extends AbstractLeafNode
{
    protected $field;
    protected $alias;

    public function __construct(AbstractNode $parent, $field)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getField() 
    {
        return $this->field;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
}
