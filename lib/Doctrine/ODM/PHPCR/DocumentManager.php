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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use PHPCR\PropertyType;

/**
 * Document Manager
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class DocumentManager implements ObjectManager
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork = null;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory = null;

    /**
     * @var array
     */
    private $repositories = array();

    /**
     * @var EventManager
     */
    private $evm;

    /**
     * Whether the DocumentManager is closed or not.
     *
     * @var bool
     */
    private $closed = false;

    public function __construct(SessionInterface $session, Configuration $config = null, EventManager $evm = null)
    {
        $this->session = $session;
        $this->config = $config ?: new Configuration();
        $this->evm = $evm ?: new EventManager();
        $this->metadataFactory = new ClassMetadataFactory($this);
        $this->unitOfWork = new UnitOfWork($this);
        $this->proxyFactory = new ProxyFactory($this,
            $this->config->getProxyDir(),
            $this->config->getProxyNamespace(),
            $this->config->getAutoGenerateProxyClasses()
        );
    }

    /**
     * Gets the proxy factory used by the DocumentManager to create document proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->evm;
    }

    /**
     * @return \PHPCR\SessionInterface
     */
    public function getPhpcrSession()
    {
        return $this->session;
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param SessionInterface $session
     * @param Configuration $config
     * @param EventManager $evm
     * @return DocumentManager
     */
    public static function create(SessionInterface $session, Configuration $config = null, EventManager $evm = null)
    {
        return new self($session, $config, $evm);
    }

    /**
     * @return ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Throws an exception if the DocumentManager is closed or currently not active.
     *
     * @throws PHPCRException If the DocumentManager is closed.
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw PHPCRException::documentManagerClosed();
        }
    }

    /**
     * Check if the Document manager is open or closed.
     *
     * @return bool
     */
    public function isOpen()
    {
        return !$this->closed;
    }

    /**
     * @param  string $class
     * @return ClassMetadata
     */
    public function getClassMetadata($class)
    {
        return $this->metadataFactory->getMetadataFor($class);
    }

    /**
     * Find the Document with the given id.
     *
     * Will return null if the document wasn't found.
     *
     * @param null|string $className
     * @param string $id
     * @return object
     */
    public function find($className, $id)
    {
        try {
            $node = UUIDHelper::isUUID($id)
                ? $this->session->getNodeByIdentifier($id)
                : $this->session->getNode($id);
        } catch (\PHPCR\PathNotFoundException $e) {
            return null;
        }

        return $this->unitOfWork->createDocument($className, $node);
    }

    /**
     * Finds many documents by id.
     *
     * @param null|string $className
     * @param array $ids
     * @return object
     */
    public function findMany($className, array $ids)
    {
        $nodes = UUIDHelper::isUUID(reset($ids))
            ? $this->session->getNodesByIdentifier($ids)
            : $this->session->getNodes($ids);

        $documents = array();
        foreach ($nodes as $node) {
            $documents[$node->getPath()] = $this->unitOfWork->createDocument($className, $node);
        }

        return new ArrayCollection($documents);
    }

    /**
     * @param  string $className
     * @return Doctrine\ODM\PHPCR\DocumentRepository
     */
    public function getRepository($className)
    {
        $className  = ltrim($className, '\\');
        if (empty($this->repositories[$className])) {
            $class = $this->getClassMetadata($className);
            if ($class->customRepositoryClassName) {
                $repositoryClass = $class->customRepositoryClassName;
            } else {
                $repositoryClass = 'Doctrine\ODM\PHPCR\DocumentRepository';
            }
            $this->repositories[$className] = new $repositoryClass($this, $class);
        }
        return $this->repositories[$className];
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
        if (null !== $type) {
            $val = PropertyType::convertType($val, $type);
        }

        return "'".str_replace("'", "''", $val)."'";
    }

    /**
     * Create a Query from a query string in the specified query language to be
     * used with getDocumentsByQuery()
     *
     * See \PHPCR\Query\QueryInterface for list of generally supported types
     * and check your implementation documentation if you want to use a
     * different language.
     *
     * @param  string $statement the statement in the specified language
     * @param  string $language the query language
     * @return PHPCR\Query\QueryInterface
     */
    public function createQuery($statement, $language)
    {
        $qm = $this->session->getWorkspace()->getQueryManager();
        return $qm->createQuery($statement, $language);
    }

    /**
     * Get document results from a PHPCR query instance
     *
     * @param  \PHPCR\Query\QueryInterface $query the query instance as acquired through createQuery()
     * @param  string $documentName document class
     *
     * @return array of document instances
     */
    public function getDocumentsByQuery(\PHPCR\Query\QueryInterface $query, $className = null)
    {
        $documents = array();

        // get all nodes from the node iterator
        $nodes = $query->execute()->getNodes(true);
        foreach ($nodes as $node) {
            $documents[$node->getPath()] = $this->unitOfWork->createDocument($className, $node);
        }

        return new ArrayCollection($documents);
    }

    /**
     * {@inheritDoc}
     *
     * Persist creates the PHPCR node (but does not map the fields to properties
     * yet) and populates the @Node, @Nodename and @Id annotations too. This
     * means that if you use the raw phpcr session you will already see the
     * nodes in case you need to add children to them.
     * If you need a raw PHPCR session but do not need to see those newly
     * created nodes, it is advised to use a separate session.
     */
    public function persist($object)
    {
        $this->errorIfClosed();
        $this->unitOfWork->scheduleInsert($object);
    }

    public function remove($object)
    {
        $this->errorIfClosed();
        $this->unitOfWork->scheduleRemove($object);
    }

    /**
     * Merges the state of a detached object into the persistence context
     * of this ObjectManager and returns the managed copy of the object.
     * The object passed to merge will not become associated/managed with this ObjectManager.
     *
     * @param object $document
     */
    public function merge($document)
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');

        // TODO: implemenent
        $this->errorIfClosed();
        return $this->getUnitOfWork()->merge($document);
    }

    /**
     * Detaches an object from the ObjectManager, causing a managed object to
     * become detached. Unflushed changes made to the object if any
     * (including removal of the object), will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $document The object to detach.
     * @return void
     */
    public function detach($document)
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');

        // TODO: implemenent
        $this->errorIfClosed();
        $this->getUnitOfWork()->detach($document);
    }

    /**
     * Refresh the given document by querying the PHPCR to get the current state.
     *
     * @param object $document
     * @return object Document instance
     */
    public function refresh($document)
    {
        $this->errorIfClosed();
        $this->session->refresh(true);
        $node = $this->session->getNode($this->unitOfWork->getDocumentId($document));

        $hints = array('refresh' => true);
        return $this->unitOfWork->createDocument(get_class($document), $node, $hints);
    }

    /**
     * Get the child documents of a given document using an optional filter.
     *
     * This methods gets all child nodes as a collection of documents that matches
     * a given filter (same as PHPCR Node::getNodes)
     * @param $document document instance which children should be loaded
     * @param string|array $filter optional filter to filter on childrens names
     * @return a collection of child documents
     */
    public function getChildren($document, $filter = null)
    {
        $this->errorIfClosed();
        return $this->unitOfWork->getChildren($document, $filter);
    }

    /**
     * Get the documents that refer a given document using an optional name.
     *
     * This methods gets all nodes as a collection of documents that refer the
     * given document and matches a given name.
     * @param $document document instance which referrers should be loaded
     * @param string|array $name optional name to match on referrers names
     * @return a collection of referrer documents
     */
    public function getReferrers($document, $type = null, $name = null)
    {
        $this->errorIfClosed();
        return $this->unitOfWork->getReferrers($document, $type, $name);
    }

    /**
     * Flush all current changes, that is save them within the phpcr session
     * and commit that session to permanent storage.
     */
    public function flush()
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit();
    }

    /**
     * Check in the Object, this makes the node read only and creates a new version.
     *
     * @param object $document
     */
    public function checkIn($object)
    {
        $this->errorIfClosed();
        $this->unitOfWork->checkIn($object);
    }

    /**
     * Check Out in the Object, this makes the node writable again.
     *
     * @param object $document
     */
    public function checkOut($object)
    {
        $this->errorIfClosed();
        $this->unitOfWork->checkOut($object);
    }

    /**
     * Restores an Object to a certain version.
     *
     * @param string $version The version to be restored e.g. 1.0 or 1.1
     * @param object $document.
     * @param bool $removeExisting Should the existing version be removed.
     */
    public function restore($version, $object, $removeExisting = true)
    {
        $this->errorIfClosed();
        $this->unitOfWork->restore($version, $object, $removeExisting);
        $this->refresh($object);
    }

    /**
     * Gets the DocumentRepository and gets the Predeccors of the Object.
     *
     * @param  string $className
     * @param  object $document
     * @return array of \PHPCR\Version\Version objects
     */

    public function getPredecessors($className, $object)
    {
         return $this->getRepository($className)->getPredecessors($object);
    }

    /**
     * @param  object $document
     * @return bool
     */
    public function contains($document)
    {
        return $this->unitOfWork->contains($document);
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * Clears the DocumentManager. All entities that are currently managed
     * by this DocumentManager become detached.
     *
     * @param string $className
     */
    public function clear($className = null)
    {
        if ($className === null) {
            $this->unitOfWork->clear();
        } else {
            //TODO
            throw new PHPCRException("DocumentManager#clear(\$className) not yet implemented.");
        }
    }

    /**
     * Closes the DocumentManager. All entities that are currently managed
     * by this DocumentManager become detached. The DocumentManager may no longer
     * be used after it is closed.
     */
    public function close()
    {
        $this->clear();
        $this->closed = true;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * This method is a no-op for other objects
     *
     * @param object $obj
     */
    public function initializeObject($obj)
    {
        $this->unitOfWork->initializeObject($obj);
    }
}
