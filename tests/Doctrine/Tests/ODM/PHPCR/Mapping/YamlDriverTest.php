<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver;

/**
 * @group mapping
 */
class YamlDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver(): MappingDriver
    {
        $location = __DIR__.'/Model/yml';

        return new YamlDriver($location);
    }

    protected function loadDriverForTestMappingDocuments(): MappingDriver
    {
        return $this->loadDriver();
    }
}
