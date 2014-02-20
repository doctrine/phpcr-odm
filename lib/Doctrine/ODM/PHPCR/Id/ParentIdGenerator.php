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

/**
 * Generate the id from the nodename and the parent mapping fields. Simply uses
 * the parent id and appends the nodename field.
 */
class ParentIdGenerator extends IdGenerator
{
    /**
     * Use the name and parent fields to generate the id
     *
     * {@inheritDoc}
     */
    public function generate($document, ClassMetadata $class, DocumentManager $dm, $parent = null)
    {
        if (null === $parent) {
            $parent = $class->parentMapping ? $class->getFieldValue($document, $class->parentMapping) : null;
        }

        $name = $class->nodename ? $class->getFieldValue($document, $class->nodename) : null;
        $id = $class->getFieldValue($document, $class->identifier);

        if (empty($id)) {
            if (empty($name) && empty($parent)) {
                throw IdException::noIdentificationParameters($document, $class->parentMapping, $class->nodename);
            }

            if (empty($parent)) {
                throw IdException::noIdNoParent($document, $class->parentMapping);
            }

            if (empty($name)) {
                throw IdException::noIdNoName($document, $class->nodename);
            }
        }

        // use assigned ID by default
        if (empty($parent) || empty($name)) {
            return $id;
        }

        if ($exception = $class->isValidNodename($name)) {
            throw IdException::illegalName($document, $class->nodename, $name);
        }

        // determine ID based on the path and the node name
        return $this->buildName($document, $class, $dm, $parent, $name);
    }

    protected function buildName($document, ClassMetadata $class, DocumentManager $dm, $parent, $name)
    {
        // get the id of the parent document
        $id = $dm->getUnitOfWork()->getDocumentId($parent);
        if (!$id) {
            throw IdException::parentIdCouldNotBeDetermined($document, $class->parentMapping, $parent);
        }

        // edge case parent is root
        if ('/' === $id) {
            $id = '';
        }

        return $id . '/' . $name;
    }
}
