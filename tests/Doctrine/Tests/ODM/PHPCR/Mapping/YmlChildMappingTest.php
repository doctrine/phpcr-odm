<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver;

class YmlChildMappingTest extends ChildMappingTest
{
    protected function loadDriver()
    {
        return new YamlDriver(array(__DIR__."/yml"));
    }

}
