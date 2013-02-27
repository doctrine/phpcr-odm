<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

use Doctrine\ODM\PHPCR\Translation\Translation;

class TranslationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\WorkspaceInterface
     */
    private $workspace;

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
    }

    // This test should succeed if the system node types have been registered.
    // Use bin/phpcr doctrine:phpcr:register-system-node-types to register the system node types.
    public function testVariantNamespaceRegistered()
    {
        $nr = $this->workspace->getNamespaceRegistry();
        $this->assertEquals(Translation::LOCALE_NAMESPACE_URI, $nr->getURI(Translation::LOCALE_NAMESPACE));
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
        $this->assertEquals('attribute', $metadata->translator);

        $this->assertTrue(isset($metadata->localeMapping));
        $this->assertEquals('locale',$metadata->localeMapping);
    }

    /**
     * Test loading of a translatable document missing the @Locale annotation.
     * @expectedException Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testLoadMissingLocaleAnnotation()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $factory->getMetadataFor('Doctrine\Tests\Models\Translation\NoLocalePropertyArticle');
    }

    /**
     * Assertion shortcut:
     * Check the given $metadata contain a field mapping for $field that contains the $key and having the value $expectedValue.
     * @param string $expectedValue The expected value
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The class metadata to test
     * @param string $field The name of the field's mapping to test
     * @param string $key The key expected to be in the field mapping
     */
    protected function assertFieldMetadataEquals($expectedValue, ClassMetadata $metadata, $field, $key)
    {
        $mapping = $metadata->mappings[$field];
        $this->assertInternalType('array', $mapping);
        $this->assertTrue(array_key_exists($key, $mapping));
        $this->assertEquals($expectedValue, $mapping[$key]);
    }
}
