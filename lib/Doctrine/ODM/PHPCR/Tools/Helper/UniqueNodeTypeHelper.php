<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
     * @param DocumentManagerInterface $documentManager The document manager to check mappings for.
     *
     * @return array
     *
     * @throws MappingException
     */
    public function checkNodeTypeMappings(DocumentManagerInterface $documentManager)
    {
        $knownNodeTypes = array();
        $debugInformation = array();
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

            $debugInformation[$classMetadata->name] = array(
                'unique_node_type' => $classMetadata->hasUniqueNodeType(),
                'node_type' => $classMetadata->getNodeType()
            );
        }

        return $debugInformation;
    }
}
