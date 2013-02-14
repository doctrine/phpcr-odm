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

/**
 * Stores the class mapping in the phpcr:class attribute.
 *
 * If no class is found, use 'Doctrine\ODM\PHPCR\Document\Generic'
 */
class DocumentClassMapper implements DocumentClassMapperInterface
{
    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function writeMetadata(DocumentManager $dm, NodeInterface $node, $className)
    {
        if ('Doctrine\\ODM\\PHPCR\\Document\\Generic' !== $className) {
            $node->setProperty('phpcr:class', $className, PropertyType::STRING);

            $class = $dm->getClassMetadata($className);
            $node->setProperty('phpcr:classparents',
                $class->getParentClasses(),
                PropertyType::STRING
            );
        }
    }

    /**
     * {@inheritDoc}
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
