<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class FieldTest extends LeafNodeTestCase
{
    public function provideNode()
    {
        return [
            ['Field', ['a.FooBar'], [
                'getAlias' => 'a',
                'getField' => 'FooBar',
            ]],
        ];
    }
}
