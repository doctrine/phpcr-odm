<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver;

/**
 * @group mapping
 */
class YamlDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return YamlDriver
     */
    protected function loadDriver()
    {
        $location = __DIR__ . '/Model/yml';

        return new YamlDriver($location);
    }

    protected function loadDriverForTestMappingDocuments()
    {
        return $this->loadDriver();
    }
}
