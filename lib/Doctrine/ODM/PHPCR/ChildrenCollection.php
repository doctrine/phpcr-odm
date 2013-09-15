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

use Doctrine\Common\Collections\ArrayCollection;
use PHPCR\Util\PathHelper;

/**
 * Children collection class
 *
 * This class represents a collection of children of a document which phpcr
 * names match a optional filter
 *
 */
class ChildrenCollection extends PersistentCollection
{
    private $document;
    private $filter;
    private $fetchDepth;
    private $originalNodeNames;
    private $node;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm                 The DocumentManager the collection will be associated with.
     * @param object          $document           The parent document instance
     * @param string|array    $filter             filter string or array of filter string
     * @param null|int        $fetchDepth         optional fetch depth
     * @param string          $locale             the locale to use during the loading of this collection
     */
    public function __construct(DocumentManager $dm, $document, $filter = null, $fetchDepth = null, $locale = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->filter = $filter;
        $this->fetchDepth = $fetchDepth;
        $this->locale = $locale;
    }

    private function getNode($fetchDepth)
    {
        if (null === $this->node) {
            $path = $this->dm->getUnitOfWork()->getDocumentId($this->document);
            $this->node = $this->dm->getPhpcrSession()->getNode($path, $fetchDepth);
        }

        return $this->node;
    }

    private function getChildren($childNodes)
    {
        $uow = $this->dm->getUnitOfWork();
        $locale = $this->locale ?: $uow->getCurrentLocale($this->document);

        $childDocuments = array();
        foreach ($childNodes as $childNode) {
            $childDocuments[$childNode->getName()] = $uow->getOrCreateProxyFromNode($childNode, $locale);
        }

        return $childDocuments;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->getOriginalNodeNames();
            $fetchDepth = $this->fetchDepth > 0 ? $this->fetchDepth + 1 : null;
            $childNodes = $this->getNode($fetchDepth)->getNodes($this->filter);
            $this->collection = new ArrayCollection($this->getChildren($childNodes));
            $this->initialized = true;
        }
    }

    /** {@inheritDoc} */
    public function contains($element)
    {
        if (!$this->initialized) {
            $uow = $this->dm->getUnitOfWork();

            // Shortcut for new documents
            $documentState = $uow->getDocumentState($element);

            if ($documentState === UnitOfWork::STATE_NEW) {
                return false;
            }

            // Document is scheduled for inclusion
            if ($documentState === UnitOfWork::STATE_MANAGED && $uow->isScheduledForInsert($element)) {
                return false;
            }

            $documentId = $uow->getDocumentId($element);
            if (PathHelper::getParentPath($documentId) !== PathHelper::getParentPath($uow->getDocumentId($this->document))) {
                return false;
            }

            $nodeName = PathHelper::getNodeName($documentId);
            return in_array($nodeName, $this->getOriginalNodeNames());
        }

        return parent::contains($element);
    }

    /** {@inheritDoc} */
    public function containsKey($key)
    {
        if (!$this->initialized) {
            return in_array($key, $this->getOriginalNodeNames());
        }

        return parent::containsKey($key);
    }

    /** {@inheritDoc} */
    public function count()
    {
        if (!$this->initialized) {
            return count($this->getOriginalNodeNames());
        }

        return parent::count();
    }

    /** {@inheritDoc} */
    public function isEmpty()
    {
        if (!$this->initialized) {
            return !$this->count();
        }

        return parent::isEmpty();
    }

    /** {@inheritDoc} */
    public function slice($offset, $length = null)
    {
        if (!$this->initialized) {
            $nodeNames = $this->getOriginalNodeNames();
            if (!is_numeric($offset)) {
                $offset = array_search($offset, $nodeNames);
                if (false === $offset) {
                    return new ArrayCollection();
                }
            }

            $nodeNames = array_slice($nodeNames, $offset, $length);
            $parentPath = $this->getNode($this->fetchDepth)->getPath();
            array_walk($nodeNames, function (&$nodeName) use ($parentPath) {
                $nodeName = "$parentPath/$nodeName";
            });

            $childNodes = $this->dm->getPhpcrSession()->getNodes($nodeNames);
            return $this->getChildren($childNodes);
        }

        if (!is_numeric($offset)) {
            $nodeNames = $this->collection->getKeys();
            $offset = array_search($offset, $nodeNames);
            if (false === $offset) {
                return new ArrayCollection();
            }
        }

        return parent::slice($offset, $length);
    }

    /**
     * Return the ordered list of node names of children that existed when the collection was initialized
     *
     * @return array
     */
    public function getOriginalNodeNames()
    {
        if (null === $this->originalNodeNames) {
            $this->originalNodeNames = (array) $this->getNode($this->fetchDepth)->getNodeNames($this->filter);
        }

        return $this->originalNodeNames;
    }

    /**
     * Reset originalNodeNames and mark the collection as non dirty
     */
    public function takeSnapshot()
    {
        if (is_array($this->originalNodeNames)) {
            if ($this->initialized) {
                $this->originalNodeNames = $this->collection->getKeys();
            } else {
                $this->originalNodeNames = null;
            }
        }

        parent::takeSnapshot();
    }
}
