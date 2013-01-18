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

namespace Doctrine\ODM\PHPCR;

use InvalidArgumentException;

use Doctrine\ODM\PHPCR\DocumentManager;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Doctrine\ODM\PHPCR\Mapping\MappingException;

/**
 * Stores the class mapping in the phpcr:class attribute.
 *
 * If no class is found, use 'Doctrine\ODM\PHPCR\Document\Generic'
 */
class DocumentClassMapper implements DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node
     *
     * @param DocumentManager
     * @param NodeInterface $node
     * @param string $className
     *
     * @return string
     *
     * @throws \RuntimeException if no class name could be determined
     */
    public function getClassName(DocumentManager $dm, NodeInterface $node, $className = null)
    {
        if ($node->hasProperty('phpcr:class')) {
            $nodeClassName = $node->getProperty('phpcr:class')->getString();

            if (empty($className) || is_subclass_of($nodeClassName, $className)) {
                $className = $nodeClassName;
            }
        }
            // default to the built in generic document class
        if (empty($className)) {
            $className = 'Doctrine\\ODM\\PHPCR\\Document\\Generic';
        }

        return $className;
    }

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later
     *
     * @param DocumentManager
     * @param NodeInterface $node
     * @param string $className
     */
    public function writeMetadata(DocumentManager $dm, NodeInterface $node, $className)
    {
        if ('Doctrine\\ODM\\PHPCR\\Document\\Generic' !== $className) {
            $node->setProperty('phpcr:class', $className, PropertyType::STRING);

            $this->writeClassParentsMetadata($dm, $node, $className);
        }
    }

    protected function writeClassParentsMetadata(DocumentManager $dm, $node, $className)
    {
        $class = $dm->getClassMetadata($className);
        $refl = $class->getReflectionClass();
        $factory = $dm->getMetadataFactory();

        $parentClasses = array();

        while ($parent = $refl->getParentClass()) {

            // only store mapped documents
            try {
                $factory->getMetadataFor($parent->name);
            } catch (MappingException $e) {
                // if class in heirarchy is not mapped, continue to next in heirarchy
                continue;
            }

            $parentClasses[] = $parent->getName();
            $refl = $parent;
        }

        $node->setProperty('phpcr:classparents', $parentClasses, PropertyType::STRING);
    }

    /**
     * @param DocumentManager
     * @param object $document
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function validateClassName(DocumentManager $dm, $document, $className)
    {
        if (!$document instanceof $className) {
            $class = $dm->getClassMetadata(get_class($document));
            $path = $class->getIdentifierValue($document);
            $msg = "Doctrine metadata mismatch! Requested type '$className' type does not match type '".get_class($document)."' stored in the metadata at path '$path'";
            throw new InvalidArgumentException($msg);
        }
    }
}
