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
use Doctrine\Common\Collections\ArrayCollection;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;
use PHPCR\Query\QueryInterface;

/**
 * A DocumentRepository serves as a repository for documents with generic as well as
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
    const QUERY_REPLACE_WITH_FIELDNAMES = 1;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * @var Doctrine\ODM\PHPCR\UnitOfWork
     */
    protected $uow;

    /**
     * @var string
     */
    protected $className;

    /**
     * Initializes a new <tt>DocumentRepository</tt>.
     *
     * @param DocumentManager $dm The DocumentManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($dm, Mapping\ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->class = $class;

        $this->uow = $this->dm->getUnitOfWork();
        $this->className = $class->name;
    }

    /**
     * Find a single document by its id
     *
     * The id may either be a PHPCR path or UUID
     *
     * @param string $id document id
     * @return object document or null
     */
    public function find($id)
    {
        return $this->dm->find($this->className, $id);
    }

    /**
     * Find many document by id
     *
     * The ids may either be PHPCR paths or UUID's, but all must be of the same type
     *
     * @param array $ids document ids
     * @return array of document objects
     */
    public function findMany(array $ids)
    {
        return $this->dm->findMany($this->className, $ids);
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
        $qb = $this->dm->createQueryBuilder();
        $qf = $qb->getQOMFactory();

        $qb->from($qf->selector($this->class->nodeType));
        $qb->andWhere($qf->comparison($qf->propertyValue('phpcr:class'), Constants::JCR_OPERATOR_EQUAL_TO, $qf->literal($this->className)));
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }
        if ($orderBy) {
            foreach ($orderBy as $ordering) {
                $qb->addOrderBy($qf->propertyValue($ordering));
            }
        }
        foreach ($criteria as $field => $value) {
            $qb->andWhere($qf->comparison($qf->propertyValue($field), Constants::JCR_OPERATOR_EQUAL_TO, $qf->literal($value)));
        }

        return $this->getDocumentsByQuery($qb->getQuery());
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array $criteria
     * @return object
     */
    public function findOneBy(array $criteria)
    {
        $documents = $this->findBy($criteria, null, 1);
        return $documents->isEmpty() ? null : $documents->first();
    }

    /**
     * @param  object $document
     * @return object Document instance
     */
    public function refresh($document)
    {
        return $this->dm->refresh($document);
    }

    /**
     * @param object $document
     * @return void
     */
    public function refreshDocumentForProxy($document)
    {
        $this->uow->refreshDocumentForProxy($this->className, $document);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
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

    /**
     * Quote a string for inclusion in an SQL2 query
     *
     * @see \PHPCR\PropertyType
     * @param  string $val
     * @param  int $type
     * @return string
     */
    public function quote($val, $type = null)
    {
        return $this->dm->quote($val, $type);
    }

    /**
     * Escape the illegal characters for inclusion in an SQL2 query. Escape Character is \\.
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
     * @param  string $string
     * @return string Escaped String
     */
    public function escapeFullText($string)
    {
        return $this->dm->escapeFullText($string);
    }

    /**
     * Create a Query
     *
     * @param  string $statement the SQL2 statement
     * @param  string $language (see QueryInterface for list of supported types)
     * @param  bool $replaceWithFieldnames if * should be replaced with Fieldnames automatically
     * @return PHPCR\Query\QueryResultInterface
     */
    public function createQuery($statement, $language, $options = 0)
    {
        $cb = $this->dm->createQueryBuilder()->setFromQuery($statement, $language);
        if ($options & self::QUERY_REPLACE_WITH_FIELDNAMES) {
            $columns = $cb->getColumns();
            if (1 === count($columns)) {
                $column = reset($columns);
                if ('*' === $column->getColumnName() && null == $column->getPropertyName()) {
                    $cb->setColumns(array());
                    foreach ($this->class->getFieldNames() as $name) {
                        $cb->addSelect($name);
                    }
                }
            }
        }

        $factory = $cb->getQOMFactory();

        $comparison = $factory->comparison(
            $factory->propertyValue('phpcr:class'), Constants::JCR_OPERATOR_EQUAL_TO, $factory->literal($this->className)
        );

        $cb->andWhere($comparison);

        return $cb->getQuery();
    }

    /**
     * Get documents from a PHPCR query instance
     *
     * @param  \PHPCR\Query\QueryResultInterface $result
     * @return array of document instances
     */
    public function getDocumentsByQuery(\PHPCR\Query\QueryInterface $query)
    {
        return $this->dm->getDocumentsByQuery($query, $this->className);
    }
}
