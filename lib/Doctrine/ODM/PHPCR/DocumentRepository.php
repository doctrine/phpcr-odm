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

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\PHPCR\Query\Query;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;
use PHPCR\Query\QueryInterface;

/**
 * A DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
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
     * @param DocumentManager $dm            The DocumentManager to use.
     * @param ClassMetadata   $classMetadata The class descriptor.
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
     *
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
     *
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
     * @param  array      $criteria
     * @param  array|null $orderBy
     * @param  int|null   $limit
     * @param  int|null   $offset
     *
     * @return array The objects matching the criteria.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->dm->createQueryBuilder();

        $qb->from($this->className);

        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }
        if ($orderBy) {
            foreach ($orderBy as $ordering) {
                $qb->addOrderBy($ordering);
            }
        }
        foreach ($criteria as $field => $value) {
            $qb->andWhere(
                $qb->expr()->eq($field, $value)
            );
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @param array $criteria
     *
     * @return object|null The first document matching the criterias or null if
     *      none found
     */
    public function findOneBy(array $criteria)
    {
        $documents = $this->findBy($criteria, null, 1);

        return $documents->isEmpty() ? null : $documents->first();
    }

    /**
     * Refresh a document with the data from PHPCR.
     *
     * @param  object $document
     *
     * @return object The document instance
     */
    public function refresh($document)
    {
        return $this->dm->refresh($document);
    }

    /**
     * @param object $document
     */
    public function refreshDocumentForProxy($document)
    {
        $this->uow->refreshDocumentForProxy($this->className, $document);
    }

    /**
     * Get the document class name this repository is for.
     *
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
     * @return \Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Quote a string for inclusion in an SQL2 query
     *
     * @param string $val
     * @param int    $type
     *
     * @return string the quoted value
     *
     * @see \PHPCR\PropertyType
     */
    public function quote($val, $type = null)
    {
        return $this->dm->quote($val, $type);
    }

    /**
     * Escape the illegal characters for inclusion in an SQL2 query. Escape Character is \\.
     *
     * @param string $string
     *
     * @return string Escaped String
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
     */
    public function escapeFullText($string)
    {
        return $this->dm->escapeFullText($string);
    }

    /**
     * Create a Query
     *
     * @param string $statement             the SQL2 statement
     * @param string $language              (see QueryInterface for list of supported types)
     * @param bool   $replaceWithFieldnames if * should be replaced with Fieldnames automatically
     *
     * @return Query
     */
    public function createQuery($statement, $language, $options = 0)
    {
        // TODO: refactor this to use the odm query builder
        $qb = $this->dm->createPhpcrQueryBuilder()->setFromQuery($statement, $language);
        if ($options & self::QUERY_REPLACE_WITH_FIELDNAMES) {
            $columns = $qb->getColumns();
            if (1 === count($columns)) {
                $column = reset($columns);
                if ('*' === $column->getColumnName() && null == $column->getPropertyName()) {
                    $qb->setColumns(array());
                    foreach ($this->class->getFieldNames() as $name) {
                        $qb->addSelect($name);
                    }
                }
            }
        }

        $factory = $qb->getQOMFactory();

        $comparison = $factory->comparison(
            $factory->propertyValue('phpcr:class'), Constants::JCR_OPERATOR_EQUAL_TO, $factory->literal($this->className)
        );

        $qb->andWhere($comparison);

        return new Query($qb->getQuery(), $this->getDocumentManager());
    }

    /**
     * Create a QueryBuilder that is prepopulated for this repositories document
     *
     * The returned query builder will be prepopulated with the criteria
     * required to search for this repositories document class.
     *
     * NOTE: When adding criteria to the query builder you should
     *       use ->andWhere(...) as ->where(...) will overwrite
     *       the class criteria.
     *
     * @return \Doctrine\ODM\PHPCR\Query\QueryBuilderilder
     */
    public function createQueryBuilder()
    {
        $qb = $this->dm->createQueryBuilder();
        $qb->from($this->className);

        return $qb;
    }
}
