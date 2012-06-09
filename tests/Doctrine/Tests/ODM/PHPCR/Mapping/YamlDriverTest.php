<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

/**
 * @group mapping 
 */
class YamlDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver 
     */
    protected function loadDriver()
    {   
        $location = __DIR__ . '/Model/yml';
        
        return new \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver($location);
    }
    
    protected function loadDriverForTestMappingDocuments()
    {
        return $this->loadDriver();
    }
}
