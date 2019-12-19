<?php

namespace Doctrine\ODM\PHPCR\Tools\Helper;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\MappingException;

/**
 * Provides unique node type mapping verification.
 */
class UniqueNodeTypeHelper
{
    /**
     * Check each mapped PHPCR-ODM document for the given document manager,
     * throwing an exception if any document is set to use a unique node
     * type but the node type is re-used. Returns an array of debug information.
     *
     * @param DocumentManagerInterface $documentManager the document manager to check mappings for
     *
     * @throws MappingException
     *
     * @return array
     */
    public function checkNodeTypeMappings(DocumentManagerInterface $documentManager)
    {
        $knownNodeTypes = [];
        $debugInformation = [];
        $allMetadata = $documentManager->getMetadataFactory()->getAllMetadata();

        foreach ($allMetadata as $classMetadata) {
            if ($classMetadata->hasUniqueNodeType() && isset($knownNodeTypes[$classMetadata->getNodeType()])) {
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
