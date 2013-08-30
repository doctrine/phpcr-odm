<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode;

class SourceDocumentTest extends LeafNodeTestCase
{
    public function provideNode()
    {
        return array(
            array('SourceDocument', array(
                'documentFqn' => 'FooBar',
                'selectorName' => 'a',
            )),
        );
    }
}
