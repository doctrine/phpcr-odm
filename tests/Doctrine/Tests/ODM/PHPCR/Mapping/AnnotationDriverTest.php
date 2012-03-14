<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    public function testLoadMetadataForNonDocumentThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());
        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $annotationDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);

        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    public function testGetAllClassNamesIsIdempotent()
    {
        $annotationDriver = $this->loadDriverForCMSDocuments();
        $original = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $afterTestReset = $annotationDriver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $rightClassName = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->ensureIsLoaded($rightClassName);

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    public function testGetAllClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceCart';
        $this->ensureIsLoaded($extraneousClassName);

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    protected function loadDriverForCMSDocuments()
    {
        $annotationDriver = $this->loadDriver();
        $annotationDriver->addPaths(array(__DIR__ . '/../../../../../Doctrine/Tests/Models/CMS'));
        return $annotationDriver;
    }

    protected function loadDriver()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        return new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
    }

    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }
}
