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
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        $parent = $cm->getFieldValue($document, $cm->parentMapping);
        $name = $cm->getFieldValue($document, $cm->nodename);
        $id = $cm->getFieldValue($document, $cm->identifier);

        if (empty($id)) {
            if (empty($name) && empty($parent)) {
                throw IdException::noIdentificationParameters($document, $cm->parentMapping, $cm->nodename);
            }

            if ($name && empty($parent)) {
                throw IdException::noIdNoParent($document, $cm->parentMapping);
            }

            if (empty($name) && $parent) {
                throw IdException::noIdNoName($document, $cm->nodename);
            }
        }

        // use assigned ID by default
        if (empty($parent) || empty($name)) {
            return $id;
        }

        // determine ID based on the path and the node name

        // get the id of the parent document
        $id = $dm->getUnitOfWork()->getDocumentId($parent);
        if (!$id) {
            throw IdException::parentIdCouldNotBeDetermined($document, $cm->parentMapping, $parent);
        }

        // edge case parent is root
        if ('/' === $id) {
            $id = '';
        }

        return $id . '/' . $name;
    }
}
