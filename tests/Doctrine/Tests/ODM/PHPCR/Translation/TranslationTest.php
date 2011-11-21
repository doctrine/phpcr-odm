<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

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

    public function testLoadAnnotations()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Translation\Article');

        $this->assertFieldMetadataEquals(false, $metadata, 'author', 'translated');
        $this->assertFieldMetadataEquals(false, $metadata, 'publishDate', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'topic', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'text', 'translated');

        $this->assertTrue(isset($metadata->translator));
        $this->assertEquals('attribute', $metadata->translator);

        $this->assertTrue(isset($metadata->localeMapping['fieldName']));
        $this->assertEquals('locale',$metadata->localeMapping['fieldName']);
    }

    protected function assertFieldMetadataEquals($expectedValue, ClassMetadata $metadata, $field, $key)
    {
        $mapping = $metadata->getFieldMapping($field);
        $this->assertEquals($expectedValue, $mapping[$key]);
    }
}
