<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * @group mapping
 */
class AttributeDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver(): AttributeDriver
    {
        return new AttributeDriver([]);
    }

    protected function loadDriverForTestMappingDocuments(): MappingDriver
    {
        $attributeDriver = $this->loadDriver();
        $attributeDriver->addPaths([__DIR__.'/Model']);

        return $attributeDriver;
    }

    /**
     * Overwriting private parent properties isn't supported with attributes.
     *
     * @doesNotPerformAssertions
     */
    public function testParentWithPrivatePropertyMapping(): void
    {
    }
}
