<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

/**
 * @group mapping 
 */
class AnnotationDriverTest extends AbstractMappingDriverTest
{
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
}
