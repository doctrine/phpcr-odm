<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver;

class XmlPropertyMappingTest extends PropertyMappingTest
{
    protected function loadDriver()
    {
        return new XmlDriver(array(__DIR__."/xml"));
    }

}
