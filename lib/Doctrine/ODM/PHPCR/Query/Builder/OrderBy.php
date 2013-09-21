<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for order by.
 *
 * Query results can be ordered by any dynamic operand
 * in either ascending or descending order.
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
     * Add ascending ordering:
     *
     *   $qb->orderBy()
     *     ->ascending()->propertyValue('prop_1', 'alias_1')->end()
     *
     * @factoryMethod
     * @return Ordering
     */
    public function ascending()
    {
        return $this->addChild(new Ordering($this, QOMConstants::JCR_ORDER_ASCENDING));
    }

    /**
     * Add descending ordering:
     *
     *   $qb->orderBy()
     *     ->descending()->propertyValue('prop_1', 'alias_1')->end()
     *
     * @factoryMethod
     * @return Ordering
     */
    public function descending()
    {
        return $this->addChild(new Ordering($this, QOMConstants::JCR_ORDER_DESCENDING));
    }

    public function getNodeType()
    {
        return self::NT_ORDER_BY;
    }
}
