<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintFactory;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\Persistence\ObjectRepository;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;

/**
 * A DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Pascal Helfenstein <nicam@nicam.ch>
 */
class DocumentRepository implements ObjectRepository
{
    public const QUERY_REPLACE_WITH_FIELDNAMES = 1;

    protected DocumentManagerInterface $dm;
    protected ClassMetadata $class;
    protected UnitOfWork $uow;
    protected string $className;

    public function __construct(DocumentManagerInterface $dm, ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->class = $class;

        $this->uow = $this->dm->getUnitOfWork();
        $this->className = $class->name;
    }

    /**
     * Find a single document by its id.
     *
     * The id may either be a PHPCR path or UUID
     *
     * @param string $id document id
     */
    public function find($id): ?object
    {
        return $this->dm->find($this->className, $id);
    }

    /**
     * Find many document by id.
     *
     * The ids may either be PHPCR paths or UUID's, but all must be of the same type
     *
     * @param string[] $ids document ids
     *
     * @return array|Collection of document objects
     */
    public function findMany(array $ids): iterable
    {
        return $this->dm->findMany($this->className, $ids);
    }

    /**
     * Finds all documents in the repository.
     *
     * @return array|Collection the entities
     */
    public function findAll(): iterable
    {
        return $this->findBy([]);
    }

    /**
     * Finds document by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an InvalidArgumentException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array|Collection the objects matching the criteria
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): iterable
    {
        $qb = $this->createQueryBuilder('a');

        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }

        $orderByNode = $qb->orderBy();

        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $order = strtolower($order);
                if (!in_array($order, ['asc', 'desc'])) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid order specified by order, expected either "asc" or "desc", got "%s"',
                        $order
                    ));
                }

                $method = 'asc' === $order ? 'asc' : 'desc';

                $orderByNode->$method()->field('a.'.$field);
            }
        }

        $first = true;
        foreach ($criteria as $field => $value) {
            if ($first) {
                $first = false;
                $where = $qb->where();
            } else {
                $where = $qb->andWhere();
            }

            if (is_array($value)) {
                $where = $where->orX();
                foreach ($value as $oneValue) {
                    $this->constraintField($where, $field, $oneValue, 'a');
                }
            } else {
                $this->constraintField($where, $field, $value, 'a');
            }
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Constraints a field for a given value.
     *
     * @param string $field The field searched
     * @param mixed  $value The value to search for
     * @param string $alias The alias used
     */
    protected function constraintField(ConstraintFactory $where, string $field, $value, string $alias): void
    {
        if ($field === $this->class->nodename) {
            $where->eq()->name($alias)->literal($value);
        } else {
            $where->eq()->field($alias.'.'.$field)->literal($value);
        }
    }

    /**
     * Finds a single document by a set of criteria.
     *
     * @return object|null The first document matching the criteria or null if
     *                     none found
     */
    public function findOneBy(array $criteria): ?object
    {
        $documents = $this->findBy($criteria, null, 1);

        return $documents->isEmpty() ? null : $documents->first();
    }

    /**
     * Refresh a document with the data from PHPCR.
     */
    public function refresh(object $document): void
    {
        $this->dm->refresh($document);
    }

    public function refreshDocumentForProxy(Proxy $document): void
    {
        $this->uow->refreshDocumentForProxy($this->className, $document);
    }

    /**
     * Get the document class name this repository is for.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function getDocumentManager(): DocumentManagerInterface
    {
        return $this->dm;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->class;
    }

    /**
     * Quote a string for inclusion in an SQL2 query.
     *
     * @return string the quoted value
     *
     * @see \PHPCR\PropertyType
     */
    public function quote(string $val, int $type = null): string
    {
        return $this->dm->quote($val, $type);
    }

    /**
     * Escape the illegal characters for inclusion in an SQL2 query. Escape Character is \\.
     *
     * @return string Escaped String
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
     */
    public function escapeFullText(string $string): string
    {
        return $this->dm->escapeFullText($string);
    }

    /**
     * Create a Query.
     *
     * @param string $statement the SQL2 statement
     * @param string $language  (see QueryInterface for list of supported types)
     */
    public function createQuery(string $statement, string $language, $options = 0): Query
    {
        // TODO: refactor this to use the odm query builder
        $qb = $this->dm->createPhpcrQueryBuilder()->setFromQuery($statement, $language);
        if ($options & self::QUERY_REPLACE_WITH_FIELDNAMES) {
            $columns = $qb->getColumns();
            if (1 === count($columns)) {
                $column = reset($columns);
                if ('*' === $column->getColumnName() && null === $column->getPropertyName()) {
                    $qb->setColumns([]);
                    foreach ($this->class->getFieldNames() as $name) {
                        $qb->addSelect('a', $name);
                    }
                }
            }
        }

        $factory = $qb->getQOMFactory();

        $comparison = $factory->comparison(
            $factory->propertyValue('a', 'phpcr:class'),
            Constants::JCR_OPERATOR_EQUAL_TO,
            $factory->literal($this->className)
        );

        $qb->andWhere($comparison);

        return new Query($qb->getQuery(), $this->getDocumentManager());
    }

    /**
     * Create a QueryBuilder that is pre-populated for this repositories document.
     *
     * The returned query builder will be pre-populated with the criteria
     * required to search for this repositories document class.
     *
     * NOTE: When adding criteria to the query builder you should
     *       use ->andWhere(...) as ->where(...) will overwrite
     *       the class criteria.
     *
     * @param string $alias name of the alias to use, defaults to 'a'
     */
    public function createQueryBuilder(string $alias): QueryBuilder
    {
        $qb = $this->dm->createQueryBuilder();
        $qb->from($alias)->document($this->className, $alias);

        return $qb;
    }
}
