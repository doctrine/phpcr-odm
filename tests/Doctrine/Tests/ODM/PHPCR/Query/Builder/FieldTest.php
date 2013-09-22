<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Builder;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;

class FieldTest extends LeafNodeTestCase
{
    public function provideNode()
    {
        return array(
            array('Field', array('a.FooBar'), array(
                'getAlias' => 'a',
                'getField' => 'FooBar',
            )),
        );
    }
}

