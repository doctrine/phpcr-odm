<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Builder;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;

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
}
