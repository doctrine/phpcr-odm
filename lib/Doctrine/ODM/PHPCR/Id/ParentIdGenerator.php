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
            throw new \Exception("Parent and name not set and no id found. Make sure your document has a field with @PHPCRODM\\ParentDocument and @PHPCRODM\\Nodename annotation and that you set thoses fields to the place where you want to store the document.");
        }

        if (!$parent) {
            return $id;
        }

        $parent_cm = $dm->getClassMetadata(get_class($parent));
        $id = $parent_cm->reflFields[$parent_cm->identifier]->getValue($parent);
        if (!$id) {
            throw new \Exception("No parent id found. Make sure your parent document has a field with @PHPCRODM\\Id annotation and that this field is set.");
        }
        return $id . '/' . $name;
    }
}
