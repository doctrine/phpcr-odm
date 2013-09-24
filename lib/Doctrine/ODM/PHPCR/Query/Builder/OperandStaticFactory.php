<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory node for static operands.
 *
 * Note that this class is not used by the query builder and
 * is only featured here to help with tests.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandStaticFactory extends OperandFactory
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_STATIC => array(1, 1),
        );
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_FACTORY;
    }
}
