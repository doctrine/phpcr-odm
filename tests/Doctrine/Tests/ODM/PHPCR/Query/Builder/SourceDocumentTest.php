<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;

class SourceDocumentTest extends LeafNodeTestCase
{
    public function provideNode(): array
    {
        return [
            ['SourceDocument', ['FooBar', 'a'], [
                'getDocumentFqn' => 'FooBar',
                'getAlias' => 'a',
            ]],
        ];
    }

    public function testEmptyAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SourceDocument($this->parent, 'My\Fqn', '');
    }
}
