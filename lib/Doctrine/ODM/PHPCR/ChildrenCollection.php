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
    private $originalNodeNames = array();
    private $ignoreUntranslated = true;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm                 The DocumentManager the collection will be associated with.
     * @param object          $document           Document instance
     * @param string          $filter             filter string
     * @param integer         $fetchDepth         optional fetch depth if supported by the PHPCR session
     * @param boolean         $ignoreUntranslated if to ignore children that are not translated to the current locale
     */
    public function __construct(DocumentManager $dm, $document, $filter = null, $fetchDepth = null, $ignoreUntranslated = true)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->filter = $filter;
        $this->fetchDepth = $fetchDepth;
        $this->ignoreUntranslated = $ignoreUntranslated;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->collection = $this->dm->getChildren($this->document, $this->filter, $this->fetchDepth, $this->ignoreUntranslated);
            $this->originalNodeNames = $this->collection->getKeys();
        }
    }

    /**
     * Return the ordered list of node names of children that existed when the collection was initialized
     *
     * @return array
     */
    public function getOriginalNodeNames()
    {
        $this->initialize();

        return $this->originalNodeNames;
    }

    /**
     * @return ArrayCollection The collection
     */
    public function unwrap()
    {
        if (!$this->initialized) {
            return new ArrayCollection();
        }

        return parent::unwrap();
    }
}
