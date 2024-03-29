<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to true if the dynamic child
 * operand and the static child operand evaluate to true when operated
 * upon by the given operator.
 *
 * The ConstraintFactory will specify the corresponding operator
 * for each of "eq", "gte", "gt", "lte", "lt", "like", etc.
 *
 * A dynamic operand is an operand whose value is derived from the
 * persisted object set.
 *
 * A static operand is a non-changing value, either a literal or a bound
 * property.
 *
 * Comparisons can only be made one dynamic and one static operand. When
 * comparing the values between joined tables you will need to use
 * the JoinConditionFactory.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintComparison extends OperandFactory
{
    private string $operator;

    public function __construct(AbstractNode $parent, string $operator)
    {
        $this->operator = $operator;
        parent::__construct($parent);
    }

    public function getCardinalityMap(): array
    {
        return [
            self::NT_OPERAND_DYNAMIC => ['1', '1'],
            self::NT_OPERAND_STATIC => ['1', '1'],
        ];
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
