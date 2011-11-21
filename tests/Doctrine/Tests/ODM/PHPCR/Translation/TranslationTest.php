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

    /**
     * Test the annotations pertaining to translations are correctly loaded.
     */
    public function testLoadAnnotations()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Translation\Article');

        $this->assertFieldMetadataEquals(false, $metadata, 'author', 'translated');
        $this->assertFieldMetadataEquals(false, $metadata, 'publishDate', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'topic', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'text', 'translated');

        $this->assertTrue(isset($metadata->translator));
        $this->assertEquals('Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy', $metadata->translator);

        $this->assertTrue(isset($metadata->localeMapping['fieldName']));
        $this->assertEquals('locale',$metadata->localeMapping['fieldName']);
    }

    /**
     * Test loading of invalid translation annotations.
     * @expectedException Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testLoadInvalidAnnotation()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Translation\InvalidMapping');
    }

    /**
     * Assertion shortcut:
     * Check the given $metadata contain a field mapping for $field that contains the $key and having the value $expectedValue.
     * @param $expectedValue The expected value
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The class metadata to test
     * @param $field The name of the field's mapping to test
     * @param $key The key expected to be in the field mapping
     */
    protected function assertFieldMetadataEquals($expectedValue, ClassMetadata $metadata, $field, $key)
    {
        $mapping = $metadata->getFieldMapping($field);
        $this->assertEquals($expectedValue, $mapping[$key]);
    }
}
