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
 * Referrer collection class
 *
 * This class represents a collection of referrers of a document which phpcr
 * names match a optional name
 *
 */
class ReferrersCollection extends PersistentCollection
{
    private $document;
    private $type;
    private $name;
    private $refClass;
    private $originalReferrerPaths;

    /**
     * @param DocumentManager $dm
     * @param object          $document The document to get referrers of.
     * @param string|null     $type     'weak', 'hard' or null to get both weak
     *                                  and hard references.
     * @param string|null     $name     If set, name of the referencing property.
     * @param string|null     $locale   The locale to use.
     * @param string|null     $refClass Class the referrer document must be
     *                                  instanceof.
     */
    public function __construct(DocumentManager $dm, $document, $type = null, $name = null, $locale = null, $refClass = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->type = $type;
        $this->name = $name;
        $this->locale = $locale;
        $this->refClass = $refClass;
    }

    private function getReferrerNodes()
    {
        $uow = $this->dm->getUnitOfWork();
        $node = $this->dm->getPhpcrSession()->getNode($uow->getDocumentId($this->document));

        switch ($this->type) {
            case 'weak':
                $referrerProperties = $node->getWeakReferences($this->name)->getArrayCopy();
                break;
            case 'hard':
                $referrerProperties = $node->getReferences($this->name)->getArrayCopy();
                break;
            default:
                $referrerProperties = $node->getWeakReferences($this->name)->getArrayCopy();
                $referrerProperties= array_merge($node->getReferences($this->name)->getArrayCopy(), $referrerProperties);
        }

        return $referrerProperties;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $uow = $this->dm->getUnitOfWork();

            $referrerDocuments = array();
            $referrerProperties = $this->getReferrerNodes();

            $locale = $this->locale ?: $uow->getCurrentLocale($this->document);

            foreach ($referrerProperties as $referrerProperty) {
                $referrerNode = $referrerProperty->getParent();
                $document = $uow->getOrCreateProxyFromNode($referrerNode, $locale);
                if (! $this->refClass || $document instanceof $this->refClass) {
                    $referrerDocuments[] = $document;
                    $this->originalReferrerPaths[] = $referrerNode->getPath();
                }
            }

            $this->collection = new ArrayCollection($referrerDocuments);
            $this->initialized = true;
        }
    }

    /**
     * Return the ordered list of referrer that existed when the collection was initialized
     *
     * @return array
     */
    public function getOriginalPaths()
    {
        if (null === $this->originalReferrerPaths) {
            $this->originalReferrerPaths = array();
            $nodes = $this->getReferrerNodes();
            foreach ($nodes as $node) {
                $this->originalReferrerPaths[] = $node->getPath();
            }
        }

        return $this->originalReferrerPaths;
    }

    /**
     * Reset original referrer paths and mark the collection as non dirty
     */
    public function takeSnapshot()
    {
        if (is_array($this->originalReferrerPaths)) {
            if ($this->initialized) {
                foreach ($this->collection->toArray() as $document) {
                    try {
                        $this->originalReferrerPaths[] = $this->dm->getUnitOfWork()->getDocumentId($document);
                    } catch (PHPCRException $e) {
                    }
                }
            } else {
                $this->originalPaths = null;
            }
        }

        parent::takeSnapshot();
    }
}
