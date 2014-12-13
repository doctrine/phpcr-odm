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

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\RepositoryException;
use PHPCR\Util\NodeHelper;

/**
 * Generate the id using the auto naming strategy
 */
class AutoIdGenerator extends ParentIdGenerator
{
    /**
     * Use the parent field together with an auto generated name to generate the id
     *
     * {@inheritDoc}
     */
    public function generate($document, ClassMetadata $class, DocumentManager $dm, $parent = null)
    {
        if (null === $parent) {
            $parent = $class->parentMapping ? $class->getFieldValue($document, $class->parentMapping) : null;
        }

        $id = $class->getFieldValue($document, $class->identifier);
        if (empty($id) && null === $parent) {
            throw IdException::noIdNoParent($document, $class->parentMapping);
        }

        if (empty($parent)) {
            return $id;
        }

        try {
            $parentNode = $dm->getNodeForDocument($parent);
            $existingNames = (array) $parentNode->getNodeNames();
        } catch (RepositoryException $e) {
            // this typically happens while cascading persisting documents
            $existingNames = array();
        }
        $name = NodeHelper::generateAutoNodeName(
            $existingNames,
            $dm->getPhpcrSession()->getWorkspace()->getNamespaceRegistry()->getNamespaces(),
            '',
            ''
        );

        return $this->buildName($document ,$class, $dm, $parent, $name);
    }
}
