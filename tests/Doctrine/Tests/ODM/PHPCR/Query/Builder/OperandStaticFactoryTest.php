<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class OperandStaticFactoryTest extends NodeTestCase
{
    public function provideInterface(): array
    {
        return [
            ['literal', 'OperandStaticLiteral', [
                'value',
            ]],
            ['parameter', 'OperandStaticParameter', [
                'variable_name',
            ]],
        ];
    }
}
