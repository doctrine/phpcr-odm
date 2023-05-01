<?php

namespace Doctrine\ODM\PHPCR\Tools\Helper;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;

/**
 * Provides unique node type mapping verification.
 */
final class UniqueNodeTypeHelper
{
    /**
     * Check each mapped PHPCR-ODM document for the given document manager,
     * throwing an exception if any document is set to use a unique node
     * type but the node type is re-used. Returns an array of debug information.
     *
     * @throws MappingException
     */
    public function checkNodeTypeMappings(DocumentManagerInterface $documentManager): array
    {
        $knownNodeTypes = [];
        $debugInformation = [];
        /** @var ClassMetadata[] $allMetadata */
        $allMetadata = $documentManager->getMetadataFactory()->getAllMetadata();

        foreach ($allMetadata as $classMetadata) {
            if ($classMetadata->hasUniqueNodeType() && array_key_exists($classMetadata->getNodeType(), $knownNodeTypes)) {
                throw new MappingException(sprintf(
                    'The class "%s" is mapped with uniqueNodeType set to true, but the node type "%s" is used by "%s" as well.',
                    $classMetadata->name,
                    $classMetadata->getNodeType(),
                    $knownNodeTypes[$classMetadata->getNodeType()]
                ));
            }

            $knownNodeTypes[$classMetadata->getNodeType()] = $classMetadata->name;

            $debugInformation[$classMetadata->name] = [
                'unique_node_type' => $classMetadata->hasUniqueNodeType(),
                'node_type' => $classMetadata->getNodeType(),
            ];
        }

        return $debugInformation;
    }
}
