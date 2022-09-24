<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class FromTest extends NodeTestCase
{
    /**
     * @dataProvider provideInterface
     */
    public function testInterface($method, $type, $args = []): void
    {
        $this->markTestSkipped('Joins temporarily disabled');
    }

    public function provideInterface(): array
    {
        return [
            ['document', 'SourceDocument', [
                '/Fqn/To/Class', 'a',
            ]],
            ['joinInner', 'SourceJoin', [
                QOMConstants::JCR_JOIN_TYPE_INNER,
            ]],
            ['joinLeftOuter', 'SourceJoin', [
                QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER,
            ]],
            ['joinRightOuter', 'SourceJoin', [
                QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER,
            ]],
        ];
    }
}
