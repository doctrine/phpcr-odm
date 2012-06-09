<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

/**
 * @group mapping 
 */
class XmlDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver()
    {
        $entityClassName = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->ensureIsLoaded($entityClassName);
        
        $location = __DIR__ . '/xml';
        
        return new \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver($location);
    }
}
