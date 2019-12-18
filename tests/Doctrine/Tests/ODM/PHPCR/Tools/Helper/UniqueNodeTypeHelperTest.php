<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Helper;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Tools\Helper\UniqueNodeTypeHelper;
use PHPUnit\Framework\TestCase;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

/**
 * Verify the behavior of the UniqueNodeTypeHelper class that is used
 * to confirm that any documents set to use unique node types do not
 * conflict with any other mappings.
 */
class UniqueNodeTypeHelperTest extends TestCase
{
    /**
     * Configure a mocked DocumentManager that will return the supplied
     * set of metadata.
     *
     * @param ClassMetadata[] $metadata
     */
    public function configureDocumentManager(array $metadata): DocumentManager
    {
        $classMetadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getAllMetadata'))
            ->getMock();
        $classMetadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue($metadata));

        $documentManager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMetadataFactory'))
            ->getMock();
        $documentManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($classMetadataFactory));

        return $documentManager;
    }

    /**
     * Verify that a MappingException is correctly thrown when more than
     * one document uses the same node type, but one is marked as unique.
     */
    public function testCheckNodeTypeMappingsWithDuplicate()
    {
        $metadataA = new ClassMetadata('Doctrine\PHPCR\Models\ClassA');
        $metadataA->setNodeType('nt:unstructured');

        $metadataB = new ClassMetadata('Doctrine\PHPCR\Models\ClassB');
        $metadataB->setNodeType('custom:type');
        $metadataB->setUniqueNodeType(true);

        $metadataC = new ClassMetadata('Doctrine\PHPCR\Models\ClassC');
        $metadataC->setNodeType('nt:unstructured');
        $metadataC->setUniqueNodeType(true);

        $documentManager = $this->configureDocumentManager(array(
            $metadataA,
            $metadataB,
            $metadataC
        ));

        $uniqueNodeTypeHelper = new UniqueNodeTypeHelper();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The class "Doctrine\PHPCR\Models\ClassC" is mapped with uniqueNodeType set to true, but the node type "nt:unstructured" is used by "Doctrine\PHPCR\Models\ClassA" as well.');
        $uniqueNodeTypeHelper->checkNodeTypeMappings($documentManager);
    }

    /**
     * Verify that no exception results from a correctly-mapped set
     * of documents.
     */
    public function testCheckNodeTypeMappingsWithoutDuplicate()
    {
        $metadataA = new ClassMetadata('Doctrine\PHPCR\Models\ClassA');
        $metadataA->setNodeType('nt:unstructured');

        $metadataB = new ClassMetadata('Doctrine\PHPCR\Models\ClassB');
        $metadataB->setNodeType('custom:type');
        $metadataB->setUniqueNodeType(true);

        $metadataC = new ClassMetadata('Doctrine\PHPCR\Models\ClassC');
        $metadataA->setNodeType('nt:unstructured');

        $documentManager = $this->configureDocumentManager(array(
            $metadataA,
            $metadataB,
            $metadataC
        ));

        $uniqueNodeTypeHelper = new UniqueNodeTypeHelper();
        $uniqueNodeTypeHelper->checkNodeTypeMappings($documentManager);
    }
}
