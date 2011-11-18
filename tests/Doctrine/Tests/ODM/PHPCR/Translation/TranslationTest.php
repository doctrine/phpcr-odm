<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

class TranslationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
    }

    // This test should succeed if the system node types have been registered.
    public function testVariantNamespaceRegistered()
    {
        $nr = $this->workspace->getNamespaceRegistry();
        $this->assertEquals('http://www.doctrine-project.org/projects/phpcr_odm/phpcr_variant', $nr->getURI('phpcr_variant'));
    }

}
