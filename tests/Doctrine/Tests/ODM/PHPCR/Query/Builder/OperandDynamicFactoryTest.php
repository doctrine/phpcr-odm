<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class OperandDynamicFactoryTest extends NodeTestCase
{
    public function provideInterface(): array
    {
        return [
            ['fullTextSearchScore', 'OperandDynamicFullTextSearchScore', [
                'alias',
            ]],
            ['length', 'OperandDynamicFullTextSearchScore', [
                'alias.field',
            ]],
            ['lowerCase', 'OperandDynamicLowerCase', [
            ]],
            ['upperCase', 'OperandDynamicUpperCase', [
            ]],
            ['name', 'OperandDynamicName', [
                'alias',
            ]],
            ['localName', 'OperandDynamicLocalName', [
                'alias',
            ]],
            ['field', 'OperandDynamicField', [
                'alias.field',
            ]],
        ];
    }
}
