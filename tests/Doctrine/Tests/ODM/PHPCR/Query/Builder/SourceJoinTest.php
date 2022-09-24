<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;

class SourceJoinTest extends NodeTestCase
{
    public function getQueryNode($args = []): AbstractNode
    {
        $args[] = 'test-join-type';

        return parent::getQueryNode($args);
    }

    public function provideInterface(): array
    {
        return [
            ['left', 'SourceJoinLeft', [
                '/Fqn/To/Class', 'a',
            ]],
            ['right', 'SourceJoinRight', [
            ]],
            ['condition', 'SourceJoinConditionFactory', [
            ]],
        ];
    }
}
