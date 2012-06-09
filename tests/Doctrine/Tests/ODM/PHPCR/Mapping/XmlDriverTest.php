<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

/**
 * @group mapping 
 */
class XmlDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver 
     */
    protected function loadDriver()
    {   
        $location = __DIR__ . '/xml';
        
        return new \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver($location);
    }
    
    protected function loadDriverForCMSDocuments()
    {
        return $this->loadDriver();
    }
}
