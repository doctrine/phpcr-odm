<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;

/**
 * @group mapping
 */
class AnnotationDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver()
    {
        $reader = new AnnotationReader();

        return new AnnotationDriver($reader);
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
