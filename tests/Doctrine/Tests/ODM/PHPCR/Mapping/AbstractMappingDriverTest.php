<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractMappingDriverTest extends TestCase
{
    /**
     * @return MappingDriver
     */
    abstract protected function loadDriver();

    /**
     * @return MappingDriver
     */
    abstract protected function loadDriverForTestMappingDocuments();

    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName();
    }

    /**
     * Returns a ClassMetadata object for the given class, loaded using the driver associated with a concrete child
     * of this class.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function loadMetadataForClassname($className)
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
        $rightClassName = Model\FieldMappingObject::class;
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
        $className = Model\FieldMappingObject::class;

        return $this->loadMetadataForClassName($className);
    }

    /**
     * @depends testLoadFieldMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testFieldMappings($class)
    {
        $this->assertCount(12, $class->fieldMappings);
        $this->assertArrayHasKey('string', $class->mappings);
        $this->assertEquals('string', $class->mappings['string']['type']);
        $this->assertArrayHasKey('binary', $class->mappings);
        $this->assertEquals('binary', $class->mappings['binary']['type']);
        $this->assertArrayHasKey('long', $class->mappings);
        $this->assertEquals('long', $class->mappings['long']['type']);
        $this->assertArrayHasKey('int', $class->mappings);
        $this->assertEquals('long', $class->mappings['int']['type']);
        $this->assertArrayHasKey('decimal', $class->mappings);
        $this->assertEquals('decimal', $class->mappings['decimal']['type']);
        $this->assertArrayHasKey('double', $class->mappings);
        $this->assertEquals('double', $class->mappings['double']['type']);
        $this->assertArrayHasKey('float', $class->mappings);
        $this->assertEquals('double', $class->mappings['float']['type']);
        $this->assertArrayHasKey('date', $class->mappings);
        $this->assertEquals('date', $class->mappings['date']['type']);
        $this->assertArrayHasKey('boolean', $class->mappings);
        $this->assertEquals('boolean', $class->mappings['boolean']['type']);
        $this->assertArrayHasKey('name', $class->mappings);
        $this->assertEquals('name', $class->mappings['name']['type']);
        $this->assertArrayHasKey('path', $class->mappings);
        $this->assertEquals('path', $class->mappings['path']['type']);
        $this->assertArrayHasKey('uri', $class->mappings);
        $this->assertEquals('uri', $class->mappings['uri']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->identifier);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->mappings['string']['property']);
        $this->assertEquals('string', $class->mappings['string']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testBinaryFieldMappings($class)
    {
        $this->assertEquals('binary', $class->mappings['binary']['property']);
        $this->assertEquals('binary', $class->mappings['binary']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testLongFieldMappings($class)
    {
        $this->assertEquals('long', $class->mappings['long']['property']);
        $this->assertEquals('long', $class->mappings['long']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testIntFieldMappings($class)
    {
        $this->assertEquals('int', $class->mappings['int']['property']);
        $this->assertEquals('long', $class->mappings['int']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testDecimalFieldMappings($class)
    {
        $this->assertEquals('decimal', $class->mappings['decimal']['property']);
        $this->assertEquals('decimal', $class->mappings['decimal']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testDoubleFieldMappings($class)
    {
        $this->assertEquals('double', $class->mappings['double']['property']);
        $this->assertEquals('double', $class->mappings['double']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testFloatFieldMappings($class)
    {
        $this->assertEquals('float', $class->mappings['float']['property']);
        $this->assertEquals('double', $class->mappings['float']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testDateFieldMappings($class)
    {
        $this->assertEquals('date', $class->mappings['date']['property']);
        $this->assertEquals('date', $class->mappings['date']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testBooleanFieldMappings($class)
    {
        $this->assertEquals('boolean', $class->mappings['boolean']['property']);
        $this->assertEquals('boolean', $class->mappings['boolean']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     */
    public function testNameFieldMappings($class)
    {
        $this->assertEquals('name', $class->mappings['name']['property']);
        $this->assertEquals('name', $class->mappings['name']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testPathFieldMappings($class)
    {
        $this->assertEquals('path', $class->mappings['path']['property']);
        $this->assertEquals('path', $class->mappings['path']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testUriFieldMappings($class)
    {
        $this->assertEquals('uri', $class->mappings['uri']['property']);
        $this->assertEquals('uri', $class->mappings['uri']['type']);

        return $class;
    }

    public function testLoadNodenameMapping()
    {
        $className = Model\NodenameMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodenameMapping
     *
     * @param ClassMetadata $class
     */
    public function testNodenameMapping($class)
    {
        $this->assertObjectHasAttribute('nodename', $class);
        $this->assertEquals('namefield', $class->nodename);
    }

    public function testLoadParentDocumentMapping()
    {
        $className = Model\ParentDocumentMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadParentDocumentMapping
     *
     * @param ClassMetadata $class
     */
    public function testParentDocumentMapping($class)
    {
        $this->assertObjectHasAttribute('parentMapping', $class);
        $this->assertEquals('parent', $class->parentMapping);
    }

    public function testLoadDepthMapping()
    {
        $className = Model\DepthMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadDepthMapping
     *
     * @param ClassMetadata $class
     */
    public function testDepthMapping($class)
    {
        $this->assertObjectHasAttribute('depthMapping', $class);
        $this->assertEquals('depth', $class->depthMapping);
    }

    public function testParentWithPrivatePropertyMapping()
    {
        $className = Model\ParentWithPrivatePropertyObject::class;
        $class = $this->loadMetadataForClassname($className);
        $this->assertEquals('foo', $class->mappings['foo']['property']);
        $this->assertEquals('string', $class->mappings['foo']['type']);

        $className = Model\ParentPrivatePropertyMappingObject::class;
        $class = $this->loadMetadataForClassname($className);

        $this->assertObjectHasAttribute('identifier', $class);
        $this->assertEmpty($class->fieldMappings);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $dm = DocumentManager::create($session);
        $dm->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $cmf = new ClassMetadataFactory($dm);
        $class = $cmf->getMetadataFor($className);

        $this->assertEquals('foo', $class->mappings['foo']['property']);
        $this->assertEquals('string', $class->mappings['foo']['type']);
    }

    /**
     * @return ClassMetadata
     */
    public function testLoadChildMapping()
    {
        $className = Model\ChildMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadChildMapping
     *
     * @param ClassMetadata $class
     */
    public function testChildMapping($class)
    {
        $this->assertObjectHasAttribute('childMappings', $class);
        $this->assertCount(2, $class->childMappings);
        $this->assertArrayHasKey('child1', $class->mappings);
        $this->assertEquals('first', $class->mappings['child1']['nodeName']);
        $this->assertArrayHasKey('child2', $class->mappings);
        $this->assertEquals('second', $class->mappings['child2']['nodeName']);
    }

    public function testLoadChildrenMapping()
    {
        $className = Model\ChildrenMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadChildrenMapping
     *
     * @param ClassMetadata $class
     */
    public function testChildrenMapping($class)
    {
        $this->assertObjectHasAttribute('childrenMappings', $class);
        $this->assertCount(2, $class->childrenMappings);
        $this->assertArrayHasKey('all', $class->mappings);
        $this->assertArrayNotHasKey('filter', $class->mappings['all']);
        $this->assertArrayHasKey('some', $class->mappings);
        $this->assertEquals(['*some*'], $class->mappings['some']['filter']);
        $this->assertEquals(2, $class->mappings['some']['fetchDepth']);
        $this->assertEquals(3, $class->mappings['some']['cascade']);
    }

    public function testLoadRepositoryMapping()
    {
        $className = Model\RepositoryMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadRepositoryMapping
     *
     * @param ClassMetadata $class
     */
    public function testRepositoryMapping($class)
    {
        $this->assertEquals(Model\DocumentRepository::class, $class->customRepositoryClassName);
        $this->assertTrue($class->isIdGeneratorRepository());
    }

    public function testLoadVersionableMapping()
    {
        $className = Model\VersionableMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadVersionableMapping
     *
     * @param ClassMetadata $class
     */
    public function testVersionableMapping($class)
    {
        $this->assertEquals('simple', $class->versionable);
        $this->assertEquals('versionName', $class->versionNameField);
        $this->assertEquals('versionCreated', $class->versionCreatedField);
    }

    public function testLoadReferenceableMapping()
    {
        $className = Model\ReferenceableMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceableMapping
     *
     * @param ClassMetadata $class
     */
    public function testReferenceableMapping($class)
    {
        $this->assertTrue($class->referenceable);
    }

    public function testLoadUniqueNodeTypeMapping()
    {
        $className = Model\UniqueNodeTypeMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadUniqueNodeTypeMapping
     *
     * @param ClassMetadata $class
     */
    public function testUniqueNodeTypeMapping($class)
    {
        $this->assertTrue($class->uniqueNodeType);
    }

    public function testLoadNodeTypeMapping()
    {
        $className = Model\NodeTypeMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodeTypeMapping
     *
     * @param ClassMetadata $class
     */
    public function testNodeTypeMapping($class)
    {
        $this->assertEquals('nt:test', $class->nodeType);
    }

    public function testLoadMappedSuperclassTypeMapping()
    {
        $className = Model\MappedSuperclassMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadMappedSuperclassTypeMapping
     *
     * @param ClassMetadata $class
     */
    public function testMappedSuperclassTypeMapping($class)
    {
        $this->assertTrue($class->isMappedSuperclass);
        $this->assertEquals('phpcr:test', $class->nodeType);
        $this->assertEquals(Model\DocumentRepository::class, $class->customRepositoryClassName);
        $this->assertEquals('children', $class->translator);
        $this->assertEquals(['mix:one', 'mix:two'], $class->mixins);
        $this->assertEquals('simple', $class->versionable);
        $this->assertTrue($class->referenceable);
        $this->assertEquals(
            'id',
            $class->identifier,
            'A driver should always be able to give mapping for a mapped superclass,'.PHP_EOL.
            'and let classes mapped with other drivers inherit this mapping entirely.'
        );

        return $class;
    }

    public function testLoadMappedSuperclassChildTypeMapping()
    {
        $parentClass = $this->loadMetadataForClassname(
            Model\ClassInheritanceParentMappingObject::class
        );

        $mappingDriver = $this->loadDriver();
        $subClass = new ClassMetadata(
            $className = Model\ClassInheritanceChildMappingObject::class
        );
        $subClass->initializeReflection(new RuntimeReflectionService());
        $subClass->mapId($parentClass->mappings[$parentClass->identifier], $parentClass);

        $mappingDriver->loadMetadataForClass($className, $subClass);

        return $subClass;
    }

    /**
     * @depends testLoadMappedSuperclassChildTypeMapping
     *
     * @param ClassMetadata $class
     */
    public function testMappedSuperclassChildTypeMapping($class)
    {
        $this->assertEquals(
            'id',
            $class->identifier,
            'The id mapping should be inherited'
        );
    }

    public function testLoadNodeMapping()
    {
        $className = Model\NodeMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodeMapping
     *
     * @param ClassMetadata $class
     */
    public function testNodeMapping($class)
    {
        $this->assertEquals('node', $class->node);
    }

    public function testLoadReferenceOneMapping()
    {
        $className = Model\ReferenceOneMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceOneMapping
     *
     * @param ClassMetadata $class
     */
    public function testReferenceOneMapping($class)
    {
        $this->assertCount(2, $class->referenceMappings);
        $this->assertArrayHasKey('referenceOneWeak', $class->mappings);
        $this->assertCount(2, $class->getAssociationNames());
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneHard'));

        $referenceOneWeak = $class->mappings['referenceOneWeak'];
        $this->assertEquals('referenceOneWeak', $referenceOneWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneWeak['targetDocument']);
        $this->assertEquals('weak', $referenceOneWeak['strategy']);
        $this->assertEquals(Model\ReferenceOneMappingObject::class, $referenceOneWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneWeak['type']);

        $referenceOneHard = $class->mappings['referenceOneHard'];
        $this->assertEquals('referenceOneHard', $referenceOneHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneHard['targetDocument']);
        $this->assertEquals('hard', $referenceOneHard['strategy']);
        $this->assertEquals(Model\ReferenceOneMappingObject::class, $referenceOneHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneHard['type']);
    }

    public function testLoadReferenceManyMapping()
    {
        $className = Model\ReferenceManyMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceManyMapping
     *
     * @param ClassMetadata $class
     */
    public function testReferenceManyMapping($class)
    {
        $this->assertCount(2, $class->referenceMappings);
        $this->assertArrayHasKey('referenceManyWeak', $class->mappings);
        $this->assertCount(2, $class->getAssociationNames());

        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyHard'));

        $referenceManyWeak = $class->mappings['referenceManyWeak'];
        $this->assertEquals('referenceManyWeak', $referenceManyWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyWeak['targetDocument']);
        $this->assertEquals('weak', $referenceManyWeak['strategy']);
        $this->assertEquals(Model\ReferenceManyMappingObject::class, $referenceManyWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyWeak['type']);

        $referenceManyHard = $class->mappings['referenceManyHard'];
        $this->assertEquals('referenceManyHard', $referenceManyHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyHard['targetDocument']);
        $this->assertEquals('hard', $referenceManyHard['strategy']);
        $this->assertEquals(Model\ReferenceManyMappingObject::class, $referenceManyHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyHard['type']);
    }

    public function testLoadReferrersMapping()
    {
        $className = Model\ReferrersMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferrersMapping
     *
     * @param ClassMetadata $class
     */
    public function testReferrersMapping($class)
    {
        $filtered = $class->mappings['filteredReferrers'];
        $this->assertEquals('referrers', $filtered['type']);
        $this->assertEquals('filteredReferrers', $filtered['fieldName']);
        $this->assertEquals('referenceManyWeak', $filtered['referencedBy']);
    }

    /**
     * @depends testLoadReferrersMapping
     *
     * @param ClassMetadata $class
     */
    public function testMixedReferrersMapping($class)
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

    public function testLoadTranslatorMapping()
    {
        $className = Model\TranslatorMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadTranslatorMapping
     *
     * @param ClassMetadata $class
     */
    public function testTranslatorMapping($class)
    {
        $this->assertEquals('attribute', $class->translator);
        $this->assertEquals('doclocale', $class->localeMapping);
        $this->assertCount(2, $class->translatableFields);
        $this->assertContains('topic', $class->translatableFields);
        $this->assertContains('image', $class->translatableFields);
    }

    public function testLoadMixinMapping()
    {
        $className = Model\MixinMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadMixinMapping
     *
     * @param ClassMetadata $class
     */
    public function testMixinMapping($class)
    {
        $this->assertCount(1, $class->mixins);
        $this->assertContains('mix:lastModified', $class->mixins);
    }

    public function testLoadReplaceMixinMapping()
    {
        $className = Model\ReplaceMixinMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReplaceMixinMapping
     *
     * @param ClassMetadata $class
     */
    public function testReplaceMixinMapping($class)
    {
        $this->assertCount(1, $class->mixins);
        $this->assertContains('mix:lastModified', $class->mixins);
    }

    public function testLoadLifecycleCallbackMapping()
    {
        $className = Model\LifecycleCallbackMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadLifecycleCallbackMapping
     *
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbackMapping($class)
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
        $className = Model\StringMappingObject::class;
        $this->loadMetadataForClassname($className);

        $className = Model\StringExtendedMappingObject::class;
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $dm = DocumentManager::create($session);
        $dm->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $cmf = new ClassMetadataFactory($dm);
        $class = $cmf->getMetadataFor($className);

        $this->assertEquals('stringAssoc', $class->mappings['stringAssoc']['fieldName']);
        $this->assertEquals('string', $class->mappings['stringAssoc']['type']);
        $this->assertTrue($class->mappings['stringAssoc']['translated']);
        $this->assertTrue($class->mappings['stringAssoc']['multivalue']);
        $this->assertEquals('stringAssocKeys', $class->mappings['stringAssoc']['assoc']);
        $this->assertEquals('stringAssocNulls', $class->mappings['stringAssoc']['assocNulls']);
    }

    /**
     * @depends testLoadUuidMapping
     *
     * @param ClassMetadata $class
     */
    public function testUuidMapping($class)
    {
        $this->assertObjectHasAttribute('uuidFieldName', $class);
        $this->assertEquals('uuid', $class->uuidFieldName);
        $this->assertEquals('string', $class->mappings['uuid']['type']);
        $this->assertEquals('jcr:uuid', $class->mappings['uuid']['property']);
    }

    public function testLoadUuidMapping()
    {
        $className = Model\UuidMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * A document that is not referenceable must not have a uuid mapped.
     */
    public function testUuidMappingNonReferenceable()
    {
        $className = Model\UuidMappingObject::class;

        return $this->loadMetadataForClassname($className);
    }

    public function testLoadChildClassesMapping()
    {
        $className = Model\ChildClassesObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadChildClassesMapping
     *
     * @param ClassMetadata $class
     */
    public function testChildClassesMapping($class)
    {
        $this->assertEquals(['stdClass'], $class->getChildClasses());
    }

    public function testLoadIsLeafMapping()
    {
        $className = Model\IsLeafObject::class;

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadIsLeafMapping
     *
     * @param ClassMetadata $class
     */
    public function testIsLeafMapping($class)
    {
        $this->assertTrue($class->isLeaf());
    }
}
