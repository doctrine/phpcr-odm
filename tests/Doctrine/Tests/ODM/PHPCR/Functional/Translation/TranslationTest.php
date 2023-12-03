<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Translation\Translation;
use Doctrine\Tests\Models\Translation\Article;
use Doctrine\Tests\Models\Translation\NoLocalePropertyArticle;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\WorkspaceInterface;

class TranslationTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var WorkspaceInterface
     */
    private $workspace;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
    }

    // This test should succeed if the system node types have been registered.
    // Use bin/phpcr doctrine:phpcr:register-system-node-types to register the system node types.
    public function testVariantNamespaceRegistered(): void
    {
        $nr = $this->workspace->getNamespaceRegistry();
        $this->assertEquals(Translation::LOCALE_NAMESPACE_URI, $nr->getURI(Translation::LOCALE_NAMESPACE));
    }

    /**
     * Test the annotations pertaining to translations are correctly loaded.
     */
    public function testLoadAnnotations(): void
    {
        $factory = new ClassMetadataFactory($this->dm);
        $metadata = $factory->getMetadataFor(Article::class);
        $this->assertInstanceOf(ClassMetadata::class, $metadata);

        $this->assertFieldMetadataEquals(false, $metadata, 'author', 'translated');
        $this->assertFieldMetadataEquals(false, $metadata, 'publishDate', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'topic', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'text', 'translated');
        $this->assertFieldMetadataEquals(true, $metadata, 'assoc', 'translated');

        $this->assertObjectHasProperty('translator', $metadata);
        $this->assertEquals('attribute', $metadata->translator);

        $this->assertObjectHasProperty('localeMapping', $metadata);
        $this->assertEquals('locale', $metadata->localeMapping);
    }

    /**
     * Test loading of a translatable document missing the Locale annotation.
     */
    public function testLoadMissingLocaleAnnotation(): void
    {
        $factory = new ClassMetadataFactory($this->dm);

        $this->expectException(MappingException::class);
        $factory->getMetadataFor(NoLocalePropertyArticle::class);
    }

    /**
     * Check the given $metadata contain a field mapping for $field that contains the $key and having the value $expectedValue.
     */
    protected function assertFieldMetadataEquals(string $expectedValue, ClassMetadata $metadata, string $field, string $key): void
    {
        $mapping = $metadata->mappings[$field];
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey($key, $mapping);
        $this->assertEquals($expectedValue, $mapping[$key]);
    }
}
