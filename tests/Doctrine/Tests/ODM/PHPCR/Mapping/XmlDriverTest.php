<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver;

/**
 * @group mapping
 */
class XmlDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return XmlDriver
     */
    protected function loadDriver()
    {
        $location = __DIR__ . '/Model/xml';

        return new XmlDriver($location);
    }

    protected function loadDriverForTestMappingDocuments()
    {
        return $this->loadDriver();
    }
}
