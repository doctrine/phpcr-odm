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

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\Common\Persistence\Event\PreUpdateEventArgs as BasePreUpdateEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\DocumentManager;

class PreUpdateEventArgs extends BasePreUpdateEventArgs
{
    /**
     * @var array
     */
    private $documentChangeSet;

    /**
     * Constructor.
     *
     * @param object          $document
     * @param DocumentManager $objectManager
     * @param array           $changeSet
     */
    public function __construct($document, DocumentManager $documentManager, array &$changeSet)
    {
        $fieldChangeSet = array();
        if (isset($changeSet['fields'])) {
            $fieldChangeSet = &$changeSet['fields'];
        }

        parent::__construct($document, $documentManager, $fieldChangeSet);

        $this->documentChangeSet = &$changeSet;
    }

    /**
     * Retrieves the document changeset.
     *
     * Currently this structure contains 2 keys:
     *  'fields' - changes to field values
     *  'reorderings' - changes in the order of collections
     *
     * @return array
     */
    public function getDocumentChangeSet()
    {
        return $this->documentChangeSet;
    }
}
