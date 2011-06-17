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

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * An DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class DocumentRepository implements ObjectRepository
{
    /**
     * @var string
     */
    protected $documentName;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * Initializes a new <tt>DocumentRepository</tt>.
     *
     * @param DocumentManager $dm The DocumentManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($dm, Mapping\ClassMetadata $class)
    {
        $this->documentName = $class->name;
        $this->dm = $dm;
        $this->class = $class;
    }

    /**
     * Create a document given class, data and the doc-id and revision
     *
     * @param   $uow Doctrine\ODM\PHPCR\UnitOfWork $uow
     * @param string $documentName
     * @param \PHPCR\NodeInterface $node
     * @param array $hints
     * @return object
     */
    public function createDocument($uow, $documentName, $node, array &$hints = array())
    {
        return $uow->createDocument($documentName, $node, $hints);
    }

    /**
     * Find a single document by its id
     *
     * @param string $id document id
     * @return object $document
     */
    public function find($id)
    {
        $uow = $this->dm->getUnitOfWork();

        try {
            $node = $this->dm->getPhpcrSession()->getNode($id);
        } catch (\PHPCR\PathNotFoundException $e) {
            return null;
        }

        $hints = array();
        return $this->createDocument($uow, $this->documentName, $node, $hints);
    }

    /**
     * Find many document by id
     *
     * @param array $ids document ids
     * @return array of document objects
     */
    public function findMany(array $ids)
    {
        $uow = $this->dm->getUnitOfWork();

        $documents = array();
        foreach ($ids as $id) {
            // TODO: catch exception and return null when not found?
            $node = $this->dm->getPhpcrSession()->getNode($id);
            $hints = array();
            $documents[] = $this->createDocument($uow, $this->documentName, $node, $hints);
        }
        return $documents;
    }

    /**
     * Finds all documents in the repository.
     *
     * @return array The entities.
     */
    public function findAll()
    {
        return $this->findBy(array());
    }

    /**
     * Finds document by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @throws UnexpectedValueException
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return mixed The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->uow->getDocumentPersister($this->documentName)->loadAll($criteria);
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array $criteria
     * @return object
     */
    public function findOneBy(array $criteria)
    {
        return $this->uow->getDocumentPersister($this->documentName)->load($criteria);
    }

    /**
     * Get all the Predecessors of a certain document.
     *
     * @param object $document
     * @return array of documents
     */
    public function getPredecessors($document)
    {
        $uow = $this->dm->getUnitOfWork();
        $predecessorNodes = $uow->getPredecessors($document);
        $objects = $hints = array();
        foreach ($predecessorNodes as $node) {
            $objects[] = $this->createDocument($uow, $this->documentName, $node, $hints);
        }
        return $objects;
    }

    /**
     * @param  object $document
     * @return void
     */
    public function refresh($document)
    {
        // TODO: call session->refresh(true) before fetching the node once Jackalope implements it

        $uow = $this->dm->getUnitOfWork();
        $node = $this->dm->getPhpcrSession()->getNode($uow->getDocumentId($document));

        $hints = array('refresh' => true);
        $this->createDocument($uow, $this->documentName, $node, $hints);
    }

    /**
     * @return string
     */
    public function getDocumentName()
    {
        return $this->documentName;
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    protected function quote($val)
    {
        return "'".str_replace("'", "''", $val)."'";
    }

    /**
     * Create a Query
     *
     * @param  string $type (see \PHPCR\Query\QueryInterface for list of supported types)
     * @return PHPCR\Query\QueryResultInterface
     */
    public function createQuery($statement, $type)
    {
        if (\PHPCR\Query\QueryInterface::JCR_SQL2 === $type) {
            // TODO maybe it would be better to convert to OQM here
            // this might make it possible to more cleanly apply the following changes

            if (0 === strpos($statement, 'SELECT *')) {
                $statement = str_replace('SELECT *', 'SELECT '.implode(', ', $this->class->getFieldNames()), $statement);
            }

            $aliasFilter = '[nt:unstructured].[phpcr:alias] = '.$this->quote($this->class->alias);
            if (false !== strpos($statement, 'WHERE')) {
                $statement = str_replace('WHERE', "WHERE $aliasFilter AND ", $statement);
            } elseif (false !== strpos($statement, 'ORDER BY')) {
                $statement = str_replace('ORDER BY', " WHERE $aliasFilter ORDER BY", $statement);
            } else {
                $statement.= " WHERE $aliasFilter";
            }
        }

        return $this->dm->createQuery($statement, $type);
    }

    /**
     * Get documents from a PHPCR query instance
     *
     * @param  \PHPCR\Query\QueryResultInterface $result
     * @return array of document instances
     */
    public function getDocumentsByQuery(\PHPCR\Query\QueryInterface $query)
    {
        return $this->dm->getDocumentsByQuery($query, $this->documentName);
    }
}
