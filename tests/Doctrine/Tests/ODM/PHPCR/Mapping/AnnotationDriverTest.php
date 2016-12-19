<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

/**
 * @group mapping
 */
class AnnotationDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
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
