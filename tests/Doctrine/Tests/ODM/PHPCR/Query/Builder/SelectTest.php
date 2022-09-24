<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class SelectTest extends NodeTestCase
{
    public function provideInterface(): array
    {
        return [
            ['field', 'Field', [
                'alias.field',
            ]],
        ];
    }
}
