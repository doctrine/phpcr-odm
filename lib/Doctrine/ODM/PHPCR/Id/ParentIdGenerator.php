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

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class ParentIdGenerator extends IdGenerator
{
    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     * @return string
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        $parent = $cm->getFieldValue($document, $cm->parentMapping);
        $name = $cm->getFieldValue($document, $cm->nodename);
        $id = $cm->getFieldValue($document, $cm->identifier);

        if ((empty($parent) || empty($name)) && empty($id)) {
            throw new \RuntimeException('ID could not be determined. Make sure the document has a property with Doctrine\ODM\PHPCR\Mapping\Annotations\ParentDocument and Doctrine\ODM\PHPCR\Mapping\Annotations\Nodename annotation and that the property is set to the path where the document is to be stored.');
        }

        // use assigned ID by default
        if (!$parent || empty($name)) {
            return $id;
        }

        // determine ID based on the path and the node name
        $id = $dm->getUnitOfWork()->getDocumentId($parent);
        if (!$id) {
            throw new \RuntimeException('Parent ID could not be determined. Make sure to persist the parent document before persisting this document.');
        }
        // edge case parent is root
        if ('/' === $id) {
            $id = '';
        }
        return $id . '/' . $name;
    }
}
