<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class OperandStaticFactoryTest extends NodeTestCase
{
    public function provideInterface()
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
