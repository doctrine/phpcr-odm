<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class VersioningTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
    }

    /**
     * Test the annotations pertaining to versioning are correctly loaded.
     */
    public function testLoadAnnotations()
    {
        $factory = new ClassMetadataFactory($this->dm);

        // Check the annotation is correctly read if it is present
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\VersionableArticle');
        $this->assertTrue(isset($metadata->versioningType));
        $this->assertEquals('simple', $metadata->versioningType);

        // Check the annotation is not set if it is not present
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\NonVersionableArticle');
        $this->assertFalse(isset($metadata->versioningType));
    }
}