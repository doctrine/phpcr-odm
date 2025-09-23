<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * @group mapping
 */
class AttributeDriverTest extends AbstractMappingDriverTest
{
    /** @param list<string> $paths */
    protected function loadDriver(array $paths = []): AttributeDriver
    {
        // Available in Doctrine Persistence 4.1+
        if (class_exists(FileClassLocator::class)) {
            $paths = FileClassLocator::createFromDirectories($paths);
        }

        return new AttributeDriver($paths);
    }

    protected function loadDriverForTestMappingDocuments(): MappingDriver
    {
        return $this->loadDriver([__DIR__.'/Model']);
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
