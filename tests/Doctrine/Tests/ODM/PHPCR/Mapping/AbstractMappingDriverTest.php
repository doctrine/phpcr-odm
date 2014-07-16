<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

abstract class AbstractMappingDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    abstract protected function loadDriver();
    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    abstract protected function loadDriverForTestMappingDocuments();

    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }

    /**
     * Returns a ClassMetadata object for the given class, loaded using the driver associated with a concrete child
     * of this class.
     *
     * @param string $className
     * @return \Doctrine\ODM\PHPCR\Mapping\ClassMetadata
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

        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');
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
        $rightClassName = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\FieldMappingObject';
        $this->ensureIsLoaded($rightClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    public function testGetAllClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceCart';
        $this->ensureIsLoaded($extraneousClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadFieldMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\FieldMappingObject';
        return $this->loadMetadataForClassName($className);
    }

    /**
     * @depends testLoadFieldMapping
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
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
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->identifier);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->mappings['string']['property']);
        $this->assertEquals('string', $class->mappings['string']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testBinaryFieldMappings($class)
    {
        $this->assertEquals('binary', $class->mappings['binary']['property']);
        $this->assertEquals('binary', $class->mappings['binary']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testLongFieldMappings($class)
    {
        $this->assertEquals('long', $class->mappings['long']['property']);
        $this->assertEquals('long', $class->mappings['long']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIntFieldMappings($class)
    {
        $this->assertEquals('int', $class->mappings['int']['property']);
        $this->assertEquals('long', $class->mappings['int']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDecimalFieldMappings($class)
    {
        $this->assertEquals('decimal', $class->mappings['decimal']['property']);
        $this->assertEquals('decimal', $class->mappings['decimal']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDoubleFieldMappings($class)
    {
        $this->assertEquals('double', $class->mappings['double']['property']);
        $this->assertEquals('double', $class->mappings['double']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testFloatFieldMappings($class)
    {
        $this->assertEquals('float', $class->mappings['float']['property']);
        $this->assertEquals('double', $class->mappings['float']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDateFieldMappings($class)
    {
        $this->assertEquals('date', $class->mappings['date']['property']);
        $this->assertEquals('date', $class->mappings['date']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testBooleanFieldMappings($class)
    {
        $this->assertEquals('boolean', $class->mappings['boolean']['property']);
        $this->assertEquals('boolean', $class->mappings['boolean']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
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
     * @param ClassMetadata $class
     */
    public function testPathFieldMappings($class)
    {
        $this->assertEquals('path', $class->mappings['path']['property']);
        $this->assertEquals('path', $class->mappings['path']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testUriFieldMappings($class)
    {
        $this->assertEquals('uri', $class->mappings['uri']['property']);
        $this->assertEquals('uri', $class->mappings['uri']['type']);

        return $class;
    }

    public function testLoadNodenameMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodenameMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodenameMapping
     * @param ClassMetadata $class
     */
    public function testNodenameMapping($class)
    {
        $this->assertTrue(isset($class->nodename));
        $this->assertEquals('namefield', $class->nodename);
    }

    public function testLoadParentDocumentMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentDocumentMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadParentDocumentMapping
     * @param ClassMetadata $class
     */
    public function testParentDocumentMapping($class)
    {
        $this->assertTrue(isset($class->parentMapping));
        $this->assertEquals('parent', $class->parentMapping);
    }

    public function testParentWithPrivatePropertyMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentWithPrivatePropertyObject';
        $class = $this->loadMetadataForClassname($className);
        $this->assertEquals('foo', $class->mappings['foo']['property']);
        $this->assertEquals('string', $class->mappings['foo']['type']);

        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ParentPrivatePropertyMappingObject';
        $class = $this->loadMetadataForClassname($className);

        $this->assertTrue(isset($class->identifier));
        $this->assertEmpty($class->fieldMappings);

        $session = $this->getMock('PHPCR\SessionInterface');
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);
        $dm->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $cmf = new ClassMetadataFactory($dm);
        $class = $cmf->getMetadataFor($className);

        $this->assertEquals('foo', $class->mappings['foo']['property']);
        $this->assertEquals('string', $class->mappings['foo']['type']);
    }

    public function testLoadChildMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadChildMapping
     * @param ClassMetadata $class
     */
    public function testChildMapping($class)
    {
        $this->assertTrue(isset($class->childMappings));
        $this->assertCount(2, $class->childMappings);
        $this->assertTrue(isset($class->mappings['child1']));
        $this->assertEquals('first', $class->mappings['child1']['nodeName']);
        $this->assertTrue(isset($class->mappings['child2']));
        $this->assertEquals('second', $class->mappings['child2']['nodeName']);
    }

    public function testLoadChildrenMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildrenMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadChildrenMapping
     * @param ClassMetadata $class
     */
    public function testChildrenMapping($class)
    {
        $this->assertTrue(isset($class->childrenMappings));
        $this->assertCount(2, $class->childrenMappings);
        $this->assertTrue(isset($class->mappings['all']));
        $this->assertFalse(isset($class->mappings['all']['filter']));
        $this->assertTrue(isset($class->mappings['some']));
        $this->assertEquals(array('*some*'), $class->mappings['some']['filter']);
        $this->assertEquals(2, $class->mappings['some']['fetchDepth']);
        $this->assertEquals(3, $class->mappings['some']['cascade']);
    }

    public function testLoadRepositoryMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\RepositoryMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadRepositoryMapping
     * @param ClassMetadata $class
     */
    public function testRepositoryMapping($class)
    {
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository', $class->customRepositoryClassName);
        $this->assertTrue($class->isIdGeneratorRepository());
    }

    public function testLoadVersionableMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\VersionableMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadVersionableMapping
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
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceableMapping
     * @param ClassMetadata $class
     */
    public function testReferenceableMapping($class)
    {
        $this->assertTrue($class->referenceable);
    }

    public function testLoadNodeTypeMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodeTypeMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodeTypeMapping
     * @param ClassMetadata $class
     */
    public function testNodeTypeMapping($class)
    {
        $this->assertEquals('nt:test', $class->nodeType);
    }

    public function testLoadMappedSuperclassTypeMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\MappedSuperclassMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadMappedSuperclassTypeMapping
     * @param ClassMetadata $class
     */
    public function testMappedSuperclassTypeMapping($class)
    {
        $this->assertTrue($class->isMappedSuperclass);
        $this->assertEquals("phpcr:test", $class->nodeType);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository', $class->customRepositoryClassName);
        $this->assertEquals("children", $class->translator);
        $this->assertEquals(array('mix:one', 'mix:two'), $class->mixins);
        $this->assertEquals("simple", $class->versionable);
        $this->assertTrue($class->referenceable);
        $this->assertEquals(
            'id',
            $class->identifier,
            'A driver should always be able to give mapping for a mapped superclass,' . PHP_EOL.
            'and let classes mapped with other drivers inherit this mapping entirely.'
        );

        return $class;
    }

    public function testLoadMappedSuperclassChildTypeMapping()
    {
        $parentClass = $this->loadMetadataForClassname(
            'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceParentMappingObject'
        );

        $mappingDriver = $this->loadDriver();
        $subClass = new ClassMetadata(
            $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildMappingObject'
        );
        $subClass->initializeReflection(new RuntimeReflectionService());
        $subClass->mapId($parentClass->mappings[$parentClass->identifier], $parentClass);

        $mappingDriver->loadMetadataForClass($className, $subClass);

        return $subClass;
    }

    /**
     * @depends testLoadMappedSuperclassChildTypeMapping
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
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\NodeMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadNodeMapping
     * @param ClassMetadata $class
     */
    public function testNodeMapping($class)
    {
        $this->assertEquals('node', $class->node);
    }

    public function testLoadReferenceOneMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceOneMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceOneMapping
     * @param ClassMetadata $class
     */
    public function testReferenceOneMapping($class)
    {
        $this->assertEquals(2, count($class->referenceMappings));
        $this->assertTrue(isset($class->mappings['referenceOneWeak']));
        $this->assertCount(2, $class->getAssociationNames());
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceOneHard'));

        $referenceOneWeak = $class->mappings['referenceOneWeak'];
        $this->assertEquals('referenceOneWeak', $referenceOneWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneWeak['targetDocument']);
        $this->assertEquals('weak', $referenceOneWeak['strategy']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceOneMappingObject', $referenceOneWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneWeak['type']);

        $referenceOneHard = $class->mappings['referenceOneHard'];
        $this->assertEquals('referenceOneHard', $referenceOneHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceOneHard['targetDocument']);
        $this->assertEquals('hard', $referenceOneHard['strategy']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceOneMappingObject', $referenceOneHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $referenceOneHard['type']);
    }

    public function testLoadReferenceManyMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceManyMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferenceManyMapping
     * @param ClassMetadata $class
     */
    public function testReferenceManyMapping($class)
    {
        $this->assertEquals(2, count($class->referenceMappings));
        $this->assertTrue(isset($class->mappings['referenceManyWeak']));
        $this->assertCount(2, $class->getAssociationNames());

        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyWeak'));
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $class->getAssociationTargetClass('referenceManyHard'));

        $referenceManyWeak = $class->mappings['referenceManyWeak'];
        $this->assertEquals('referenceManyWeak', $referenceManyWeak['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyWeak['targetDocument']);
        $this->assertEquals('weak', $referenceManyWeak['strategy']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceManyMappingObject', $referenceManyWeak['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyWeak['type']);

        $referenceManyHard = $class->mappings['referenceManyHard'];
        $this->assertEquals('referenceManyHard', $referenceManyHard['fieldName']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\myDocument', $referenceManyHard['targetDocument']);
        $this->assertEquals('hard', $referenceManyHard['strategy']);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceManyMappingObject', $referenceManyHard['sourceDocument']);
        $this->assertEquals(ClassMetadata::MANY_TO_MANY, $referenceManyHard['type']);
    }

    public function testLoadReferrersMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferrersMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadReferrersMapping
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
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadTranslatorMapping
     * @param ClassMetadata $class
     */
    public function testTranslatorMapping($class)
    {
        $this->assertEquals('attribute', $class->translator);
        $this->assertEquals('doclocale', $class->localeMapping);
        $this->assertEquals(2, count($class->translatableFields));
        $this->assertContains('topic', $class->translatableFields);
        $this->assertContains('image', $class->translatableFields);
    }

    public function testLoadMixinMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadMixinMapping
     * @param ClassMetadata $class
     */
    public function testMixinMapping($class)
    {
        $this->assertEquals(1, count($class->mixins));
        $this->assertContains('mix:lastModified', $class->mixins);
    }

    public function testLoadLifecycleCallbackMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\LifecycleCallbackMappingObject';

        return $this->loadMetadataForClassname($className);
    }

    /**
     * @depends testLoadLifecycleCallbackMapping
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbackMapping($class)
    {
        $this->assertEquals(7, count($class->lifecycleCallbacks));
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
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\StringMappingObject';
        $this->loadMetadataForClassname($className);

        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\StringExtendedMappingObject';
        $session = $this->getMock('PHPCR\SessionInterface');
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);
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
     * @param   $class
     */
    public function testUuidMapping($class)
    {
        $this->assertTrue(isset($class->uuidFieldName));
        $this->assertEquals('uuid', $class->uuidFieldName);
        $this->assertEquals('string', $class->mappings['uuid']['type']);
        $this->assertEquals('jcr:uuid', $class->mappings['uuid']['property']);
    }

    public function testLoadUuidMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\UuidMappingObject';

        return $this->loadMetadataForClassname($className);
    }
}
