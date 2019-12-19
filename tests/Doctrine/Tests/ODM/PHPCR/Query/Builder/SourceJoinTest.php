<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class SourceJoinTest extends NodeTestCase
{
    public function getNode($args = [])
    {
        $args[] = 'test-join-type';

        return parent::getNode($args);
    }

    public function provideInterface()
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
