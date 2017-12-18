<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class SelectTest extends NodeTestCase
{
    public function provideInterface()
    {
        return [
            ['field', 'Field', [
                'alias.field',
            ]],
        ];
    }
}
