<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for order by.
 *
 * Query results can be ordered by any dynamic operand
 * in either asc or desc order.
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OrderBy extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_ORDERING => array(0, null)
        );
    }

    /**
     * Add asc ordering:
     *
     * <code>
     *   $qb->orderBy()
     *     ->asc()->propertyValue('prop_1', 'alias_1')->end()
     * </code>
     *
     * @factoryMethod
     * @return Ordering
     */
    public function asc()
    {
        return $this->addChild(new Ordering($this, QOMConstants::JCR_ORDER_ASCENDING));
    }

    /**
     * Add desc ordering:
     *
     * <code>
     *   $qb->orderBy()
     *     ->desc()->propertyValue('prop_1', 'alias_1')->end()
     * </code>
     *
     * @factoryMethod
     * @return Ordering
     */
    public function desc()
    {
        return $this->addChild(new Ordering($this, QOMConstants::JCR_ORDER_DESCENDING));
    }

    public function getNodeType()
    {
        return self::NT_ORDER_BY;
    }
}
