<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildrenMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceParentMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DepthMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\FieldMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\IsLeafObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\LifecycleCallbackMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MappedSuperclassMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodeMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodenameMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodeTypeMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentDocumentMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentPrivatePropertyMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentWithPrivatePropertyObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceManyMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceOneMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferrersMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReplaceMixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\RepositoryMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\StringExtendedMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\StringMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\UniqueNodeTypeMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\UuidMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\VersionableMappingObject;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractMappingDriverTest extends TestCase
{
    abstract protected function loadDriver(): MappingDriver;

    abstract protected function loadDriverForTestMappingDocuments(): MappingDriver;

    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName();
    }

    /**
     * Returns a ClassMetadata object for the given class, loaded using the driver associated with a concrete child
     * of this class.
     */
    protected function loadMetadataForClassname(string $className): ClassMetadata
    {
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($className);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    public function testLoadMetadataForNonDocumentThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());

        $driver = $this->loadDriver();

        $this->expectException(MappingException::class);
        $driver->loadMetadataForClass('stdClass', $cm);
    }

    public function testGetAllClassNamesIsIdempotent()
    {
        $driver = $this->loadDriverForTestMappingDocuments();
        $original = $driver->getAllClassNames();

        $driver = $this->loadDriverForTestMappingDocuments();
        $afterTestReset = $driver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $rightClassName = FieldMappingObject::class;
        $this->ensureIsLoaded($rightClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    public function testGetAllClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = ECommerceCart::class;
        $this->ensureIsLoaded($extraneousClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers \Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadFieldMapping()
    {
        return $this->loadMetadataForClassName(FieldMappingObject::class);
    }

    /**
     * @depends testLoadFieldMapping
     */
    public function testFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertCount(12, $class->fieldMappings);
        $this->assertTrue(isset($class->mappings['string']));
        $this->assertEquals('string', $class->mappings['string']['type']);
        $this->assertTrue(isset($class->mappings['binary']));
        $this->assertEquals('binary', $class->mappings['binary']['type']);
        $this->assertTrue(isset($class->mappings['long']));
        $this->assertEquals('long', $class->mappings['long']['type']);
        $this->assertTrue(isset($class->mappings['int']));
        $this->assertEquals('long', $class->mappings['int']['type']);
        $this->assertTrue(isset($class->mappings['decimal']));
        $this->assertEquals('decimal', $class->mappings['decimal']['type']);
        $this->assertTrue(isset($class->mappings['double']));
        $this->assertEquals('double', $class->mappings['double']['type']);
        $this->assertTrue(isset($class->mappings['float']));
        $this->assertEquals('double', $class->mappings['float']['type']);
        $this->assertTrue(isset($class->mappings['date']));
        $this->assertEquals('date', $class->mappings['date']['type']);
        $this->assertTrue(isset($class->mappings['boolean']));
        $this->assertEquals('boolean', $class->mappings['boolean']['type']);
        $this->assertTrue(isset($class->mappings['name']));
        $this->assertEquals('name', $class->mappings['name']['type']);
        $this->assertTrue(isset($class->mappings['path']));
        $this->assertEquals('path', $class->mappings['path']['type']);
        $this->assertTrue(isset($class->mappings['uri']));
        $this->assertEquals('uri', $class->mappings['uri']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testIdentifier(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('id', $class->identifier);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testStringFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('string', $class->mappings['string']['property']);
        $this->assertEquals('string', $class->mappings['string']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testBinaryFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('binary', $class->mappings['binary']['property']);
        $this->assertEquals('binary', $class->mappings['binary']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testLongFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('long', $class->mappings['long']['property']);
        $this->assertEquals('long', $class->mappings['long']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testIntFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('int', $class->mappings['int']['property']);
        $this->assertEquals('long', $class->mappings['int']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testDecimalFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('decimal', $class->mappings['decimal']['property']);
        $this->assertEquals('decimal', $class->mappings['decimal']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testDoubleFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('double', $class->mappings['double']['property']);
        $this->assertEquals('double', $class->mappings['double']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testFloatFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('float', $class->mappings['float']['property']);
        $this->assertEquals('double', $class->mappings['float']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testDateFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('date', $class->mappings['date']['property']);
        $this->assertEquals('date', $class->mappings['date']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testBooleanFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('boolean', $class->mappings['boolean']['property']);
        $this->assertEquals('boolean', $class->mappings['boolean']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testNameFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('name', $class->mappings['name']['property']);
        $this->assertEquals('name', $class->mappings['name']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testPathFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('path', $class->mappings['path']['property']);
        $this->assertEquals('path', $class->mappings['path']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     */
    public function testUriFieldMappings(ClassMetadata $class): ClassMetadata
    {
        $this->assertEquals('uri', $class->mappings['uri']['property']);
        $this->assertEquals('uri', $class->mappings['uri']['type']);

        return $class;
    }

    public function testLoadNodenameMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(NodenameMappingObject::class);
    }

    /**
     * @depends testLoadNodenameMapping
     */
    public function testNodenameMapping(ClassMetadata $class)
    {
        $this->assertTrue(isset($class->nodename));
        $this->assertEquals('namefield', $class->nodename);
    }

    public function testLoadParentDocumentMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ParentDocumentMappingObject::class);
    }

    /**
     * @depends testLoadParentDocumentMapping
     */
    public function testParentDocumentMapping(ClassMetadata $class)
    {
        $this->assertTrue(isset($class->parentMapping));
        $this->assertEquals('parent', $class->parentMapping);
    }

    public function testLoadDepthMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(DepthMappingObject::class);
    }

    /**
     * @depends testLoadDepthMapping
     */
    public function testDepthMapping(ClassMetadata $class)
    {
        $this->assertNotNull($class->depthMapping);
        $this->assertSame('depth', $class->depthMapping);
    }

    public function testParentWithPrivatePropertyMapping()
    {
        $class = $this->loadMetadataForClassname(ParentWithPrivatePropertyObject::class);
        $this->assertSame('foo', $class->mappings['foo']['property']);
        $this->assertSame('string', $class->mappings['foo']['type']);

        $class = $this->loadMetadataForClassname(ParentPrivatePropertyMappingObject::class);

        $this->assertNotNull($class->identifier);
        $this->assertEmpty($class->fieldMappings);

        $session = $this->createMock(SessionInterface::class);
        $dm = DocumentManager::create($session);
        $dm->getConfiguration()->setMetadataDriverImpl($this->loadDriver());

        $cmf = new ClassMetadataFactory($dm);
        $class = $cmf->getMetadataFor(ParentPrivatePropertyMappingObject::class);

        $this->assertInstanceOf(ClassMetadata::class, $class);
        $this->assertEquals('foo', $class->mappings['foo']['property']);
        $this->assertEquals('string', $class->mappings['foo']['type']);
    }

    public function testLoadChildMapping()
    {
        return $this->loadMetadataForClassname(ChildMappingObject::class);
    }

    /**
     * @depends testLoadChildMapping
     */
    public function testChildMapping(ClassMetadata $class)
    {
        $this->assertInternalType('array', $class->childMappings);
        $this->assertCount(2, $class->childMappings);
        $this->assertArrayHasKey('child1', $class->mappings);
        $this->assertSame('first', $class->mappings['child1']['nodeName']);
        $this->assertArrayHasKey('child2', $class->mappings);
        $this->assertSame('second', $class->mappings['child2']['nodeName']);
    }

    public function testLoadChildrenMapping()
    {
        return $this->loadMetadataForClassname(ChildrenMappingObject::class);
    }

    /**
     * @depends testLoadChildrenMapping
     */
    public function testChildrenMapping(ClassMetadata $class)
    {
        $this->assertInternalType('array', $class->childrenMappings);
        $this->assertCount(2, $class->childrenMappings);
        $this->assertArrayHasKey('all', $class->mappings);
        $this->assertArrayNotHasKey('filter', $class->mappings['all']);
        $this->assertArrayHasKey('some', $class->mappings);
        $this->assertSame(['*some*'], $class->mappings['some']['filter']);
        $this->assertSame(2, $class->mappings['some']['fetchDepth']);
        $this->assertSame(3, $class->mappings['some']['cascade']);
    }

    public function testLoadRepositoryMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(RepositoryMappingObject::class);
    }

    /**
     * @depends testLoadRepositoryMapping
     */
    public function testRepositoryMapping(ClassMetadata $class)
    {
        $this->assertSame(DocumentRepository::class, $class->customRepositoryClassName);
        $this->assertTrue($class->isIdGeneratorRepository());
    }

    public function testLoadVersionableMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(VersionableMappingObject::class);
    }

    /**
     * @depends testLoadVersionableMapping
     */
    public function testVersionableMapping(ClassMetadata $class)
    {
        $this->assertSame('simple', $class->versionable);
        $this->assertSame('versionName', $class->versionNameField);
        $this->assertSame('versionCreated', $class->versionCreatedField);
    }

    public function testLoadReferenceableMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ReferenceableMappingObject::class);
    }

    /**
     * @depends testLoadReferenceableMapping
     */
    public function testReferenceableMapping(ClassMetadata $class)
    {
        $this->assertTrue($class->referenceable);
    }

    public function testLoadUniqueNodeTypeMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(UniqueNodeTypeMappingObject::class);
    }

    /**
     * @depends testLoadUniqueNodeTypeMapping
     */
    public function testUniqueNodeTypeMapping(ClassMetadata $class)
    {
        $this->assertTrue($class->uniqueNodeType);
    }

    public function testLoadNodeTypeMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(NodeTypeMappingObject::class);
    }

    /**
     * @depends testLoadNodeTypeMapping
     */
    public function testNodeTypeMapping(ClassMetadata $class)
    {
        $this->assertSame('nt:test', $class->nodeType);
    }

    public function testLoadMappedSuperclassTypeMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(MappedSuperclassMappingObject::class);
    }

    /**
     * @depends testLoadMappedSuperclassTypeMapping
     */
    public function testMappedSuperclassTypeMapping(ClassMetadata $class)
    {
        $this->assertTrue($class->isMappedSuperclass);
        $this->assertSame('phpcr:test', $class->nodeType);
        $this->assertSame(DocumentRepository::class, $class->customRepositoryClassName);
        $this->assertSame('children', $class->translator);
        $this->assertSame(['mix:one', 'mix:two'], $class->mixins);
        $this->assertSame('simple', $class->versionable);
        $this->assertTrue($class->referenceable);
        $this->assertSame(
            'id',
            $class->identifier,
            'A driver should always be able to give mapping for a mapped superclass,'.PHP_EOL.
            'and let classes mapped with other drivers inherit this mapping entirely.'
        );

        return $class;
    }

    public function testLoadMappedSuperclassChildTypeMapping(): ClassMetadata
    {
        $parentClass = $this->loadMetadataForClassname(ClassInheritanceParentMappingObject::class);

        $mappingDriver = $this->loadDriver();
        $subClass = new ClassMetadata($className = ClassInheritanceChildMappingObject::class);
        $subClass->initializeReflection(new RuntimeReflectionService());
        $subClass->mapId($parentClass->mappings[$parentClass->identifier], $parentClass);

        $mappingDriver->loadMetadataForClass($className, $subClass);

        return $subClass;
    }

    /**
     * @depends testLoadMappedSuperclassChildTypeMapping
     */
    public function testMappedSuperclassChildTypeMapping(ClassMetadata $class)
    {
        $this->assertSame(
            'id',
            $class->identifier,
            'The id mapping should be inherited'
        );
    }

    public function testLoadNodeMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(NodeMappingObject::class);
    }

    /**
     * @depends testLoadNodeMapping
     */
    public function testNodeMapping(ClassMetadata $class)
    {
        $this->assertSame('node', $class->node);
    }

    public function testLoadReferenceOneMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ReferenceOneMappingObject::class);
    }

    /**
     * @depends testLoadReferenceOneMapping
     */
    public function testReferenceOneMapping(ClassMetadata $class)
    {
        $this->assertCount(2, $class->referenceMappings);
        $this->assertTrue(isset($class->mappings['referenceOneWeak']));
        $this->assertCount(2, $class->getAssociationNames());
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneHard'));

        $referenceOneWeak = $class->mappings['referenceOneWeak'];
        $this->assertEquals('referenceOneWeak', $referenceOneWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneWeak['targetDocument']);
        $this->assertEquals('weak', $referenceOneWeak['strategy']);
        $this->assertEquals(ReferenceOneMappingObject::class, $referenceOneWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneWeak['type']);

        $referenceOneHard = $class->mappings['referenceOneHard'];
        $this->assertEquals('referenceOneHard', $referenceOneHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneHard['targetDocument']);
        $this->assertEquals('hard', $referenceOneHard['strategy']);
        $this->assertEquals(ReferenceOneMappingObject::class, $referenceOneHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneHard['type']);
    }

    public function testLoadReferenceManyMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ReferenceManyMappingObject::class);
    }

    /**
     * @depends testLoadReferenceManyMapping
     */
    public function testReferenceManyMapping(ClassMetadata $class)
    {
        $this->assertCount(2, $class->referenceMappings);
        $this->assertTrue(isset($class->mappings['referenceManyWeak']));
        $this->assertCount(2, $class->getAssociationNames());

        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyHard'));

        $referenceManyWeak = $class->mappings['referenceManyWeak'];
        $this->assertEquals('referenceManyWeak', $referenceManyWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyWeak['targetDocument']);
        $this->assertEquals('weak', $referenceManyWeak['strategy']);
        $this->assertEquals(ReferenceManyMappingObject::class, $referenceManyWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyWeak['type']);

        $referenceManyHard = $class->mappings['referenceManyHard'];
        $this->assertEquals('referenceManyHard', $referenceManyHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyHard['targetDocument']);
        $this->assertEquals('hard', $referenceManyHard['strategy']);
        $this->assertEquals(ReferenceManyMappingObject::class, $referenceManyHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyHard['type']);
    }

    public function testLoadReferrersMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ReferrersMappingObject::class);
    }

    /**
     * @depends testLoadReferrersMapping
     */
    public function testReferrersMapping(ClassMetadata $class)
    {
        $filtered = $class->mappings['filteredReferrers'];
        $this->assertEquals('referrers', $filtered['type']);
        $this->assertEquals('filteredReferrers', $filtered['fieldName']);
        $this->assertEquals('referenceManyWeak', $filtered['referencedBy']);
    }

    /**
     * @depends testLoadReferrersMapping
     */
    public function testMixedReferrersMapping(ClassMetadata $class)
    {
        $all = $class->mappings['allReferrers'];
        $this->assertEquals('mixedreferrers', $all['type']);
        $this->assertEquals('allReferrers', $all['fieldName']);
        $this->assertNull($all['referenceType']);

        $hard = $class->mappings['hardReferrers'];
        $this->assertEquals('mixedreferrers', $hard['type']);
        $this->assertEquals('hardReferrers', $hard['fieldName']);
        $this->assertEquals('hard', $hard['referenceType']);

        $weak = $class->mappings['weakReferrers'];
        $this->assertEquals('mixedreferrers', $weak['type']);
        $this->assertEquals('weakReferrers', $weak['fieldName']);
        $this->assertEquals('weak', $weak['referenceType']);
    }

    public function testLoadTranslatorMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(TranslatorMappingObject::class);
    }

    /**
     * @depends testLoadTranslatorMapping
     */
    public function testTranslatorMapping(ClassMetadata $class)
    {
        $this->assertEquals('attribute', $class->translator);
        $this->assertEquals('doclocale', $class->localeMapping);
        $this->assertCount(2, $class->translatableFields);
        $this->assertContains('topic', $class->translatableFields);
        $this->assertContains('image', $class->translatableFields);
    }

    public function testLoadMixinMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(MixinMappingObject::class);
    }

    /**
     * @depends testLoadMixinMapping
     */
    public function testMixinMapping(ClassMetadata $class)
    {
        $this->assertEquals(1, count($class->mixins));
        $this->assertContains('mix:lastModified', $class->mixins);
    }

    public function testLoadReplaceMixinMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ReplaceMixinMappingObject::class);
    }

    /**
     * @depends testLoadReplaceMixinMapping
     */
    public function testReplaceMixinMapping(ClassMetadata $class)
    {
        $this->assertCount(1, $class->mixins);
        $this->assertContains('mix:lastModified', $class->mixins);
    }

    public function testLoadLifecycleCallbackMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(LifecycleCallbackMappingObject::class);
    }

    /**
     * @depends testLoadLifecycleCallbackMapping
     */
    public function testLifecycleCallbackMapping(ClassMetadata $class)
    {
        $this->assertCount(7, $class->lifecycleCallbacks);
        $this->assertEquals('preRemoveFunc', $class->lifecycleCallbacks['preRemove'][0]);
        $this->assertEquals('postRemoveFunc', $class->lifecycleCallbacks['postRemove'][0]);
        $this->assertEquals('prePersistFunc', $class->lifecycleCallbacks['prePersist'][0]);
        $this->assertEquals('postPersistFunc', $class->lifecycleCallbacks['postPersist'][0]);
        $this->assertEquals('preUpdateFunc', $class->lifecycleCallbacks['preUpdate'][0]);
        $this->assertEquals('postUpdateFunc', $class->lifecycleCallbacks['postUpdate'][0]);
        $this->assertEquals('postLoadFunc', $class->lifecycleCallbacks['postLoad'][0]);
    }

    public function testStringExtendedMapping()
    {
        $className = StringMappingObject::class;
        $this->loadMetadataForClassname($className);

        $className = StringExtendedMappingObject::class;
        $session = $this->createMock(SessionInterface::class);
        $dm = DocumentManager::create($session);
        $dm->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $cmf = new ClassMetadataFactory($dm);
        $class = $cmf->getMetadataFor($className);

        $this->assertInstanceOf(ClassMetadata::class, $class);
        $this->assertEquals('stringAssoc', $class->mappings['stringAssoc']['fieldName']);
        $this->assertEquals('string', $class->mappings['stringAssoc']['type']);
        $this->assertTrue($class->mappings['stringAssoc']['translated']);
        $this->assertTrue($class->mappings['stringAssoc']['multivalue']);
        $this->assertEquals('stringAssocKeys', $class->mappings['stringAssoc']['assoc']);
        $this->assertEquals('stringAssocNulls', $class->mappings['stringAssoc']['assocNulls']);
    }

    /**
     * @depends testLoadUuidMapping
     */
    public function testUuidMapping(ClassMetadata $class)
    {
        $this->assertEquals('uuid', $class->uuidFieldName);
        $this->assertEquals('string', $class->mappings['uuid']['type']);
        $this->assertEquals('jcr:uuid', $class->mappings['uuid']['property']);
    }

    public function testLoadUuidMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(UuidMappingObject::class);
    }

    /**
     * A document that is not referenceable must not have a uuid mapped.
     */
    public function testUuidMappingNonReferenceable(): ClassMetadata
    {
        return $this->loadMetadataForClassname(UuidMappingObject::class);
    }

    public function testLoadChildClassesMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(ChildClassesObject::class);
    }

    /**
     * @depends testLoadChildClassesMapping
     */
    public function testChildClassesMapping(ClassMetadata $class)
    {
        $this->assertEquals(['stdClass'], $class->getChildClasses());
    }

    public function testLoadIsLeafMapping(): ClassMetadata
    {
        return $this->loadMetadataForClassname(IsLeafObject::class);
    }

    /**
     * @depends testLoadIsLeafMapping
     */
    public function testIsLeafMapping(ClassMetadata $class)
    {
        $this->assertTrue($class->isLeaf());
    }
}
