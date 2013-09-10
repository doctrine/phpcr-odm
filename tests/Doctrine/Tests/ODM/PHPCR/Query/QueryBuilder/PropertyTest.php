<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode;

class PropertyTest extends LeafNodeTestCase
{
    public function provideNode()
    {
        return array(
            array('Property', array('a.FooBar'), array(
                'getSelectorName' => 'a',
                'getPropertyName' => 'FooBar',
            )),
        );
    }
}

