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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

class DocumentNameMapper implements DocumentNameMapperInterface
{
    /**
     * Determine the document name from a given node
     *
     * @param \Doctrine\ODM\PHPCR\DocumentManager
     * @param string $documentName
     * @param \PHPCR\NodeInterface $node
     * @param boolean $writeMetadata
     * @return string
     */
    public function getDocumentName(DocumentManager $dm, $documentName, NodeInterface $node, $writeMetadata)
    {
        $properties = $node->getPropertiesValues();

        if (isset($documentName)) {
            $type = $documentName;
        } else if (isset($properties['phpcr:class'])) {
            $metadata = $dm->getMetadataFactory()->getMetadataFor($properties['phpcr:class']);
            $type = $metadata->name;
        } else if (isset($properties['phpcr:alias'])) {
            $metadata = $dm->getMetadataFactory()->getMetadataForAlias($properties['phpcr:alias']);
            $type = $metadata->name;
        } else {
            throw new \InvalidArgumentException("Missing Doctrine metadata in the Document");
        }

        if ($writeMetadata && empty($properties['phpcr:class'])) {
            $node->setProperty('phpcr:class', $documentName, PropertyType::STRING);
        }

        return $type;
    }
}
