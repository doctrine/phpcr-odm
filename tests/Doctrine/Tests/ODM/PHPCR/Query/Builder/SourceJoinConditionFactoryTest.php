<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class SourceJoinConditionFactoryTest extends NodeTestCase
{
    public function provideInterface(): array
    {
        return [
            ['descendant', 'SourceJoinConditionDescendant', [
                'alias_1', 'alias_2',
            ]],
            ['equi', 'SourceJoinConditionEqui', [
                'alias1.property1', 'alias2.property2',
            ]],
            ['child', 'SourceJoinConditionChildDocument', [
                'child_alias', 'parent_alias',
            ]],
            ['same', 'SourceJoinConditionSameDocument', [
                'alias_1', 'alias_2', '/path/to/doc',
            ]],
        ];
    }
}
