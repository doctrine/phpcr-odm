<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use PHPCR\NodeInterface;

class SourceJoinTest extends NodeTestCase
{
    public function getNode($args = []): NodeInterface
    {
        $args[] = 'test-join-type';

        return parent::getNode($args);
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
