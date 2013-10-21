<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * @group mapping
 */
class AnnotationDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        return new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
    }

    protected function loadDriverForTestMappingDocuments()
    {
        $annotationDriver = $this->loadDriver();
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        return $annotationDriver;
    }

    /**
     * Overwriting private parent properties isn't supported with annotations
     */
    public function testParentWithPrivatePropertyMapping()
    {
        return;
    }
}
