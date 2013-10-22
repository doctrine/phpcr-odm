<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;

class SourceDocumentTest extends LeafNodeTestCase
{
    public function provideNode()
    {
        return array(
            array('SourceDocument', array('FooBar', 'a'), array(
                'getDocumentFqn' => 'FooBar',
                'getAlias' => 'a',
            )),
        );
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testEmptyAlias()
    {
        new SourceDocument($this->parent, 'My\Fqn', '');
    }
}
