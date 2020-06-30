<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * @group mapping
 */
class XmlDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver(): MappingDriver
    {
        $location = __DIR__.'/Model/xml';

        return new XmlDriver($location);
    }

    protected function loadDriverForTestMappingDocuments(): MappingDriver
    {
        return $this->loadDriver();
    }
}
