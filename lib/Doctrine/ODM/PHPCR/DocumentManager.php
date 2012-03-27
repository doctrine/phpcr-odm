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
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\ChildTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;

use PHPCR\SessionInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Util\UUIDHelper;
use PHPCR\PropertyType;
use PHPCR\Util\QOM\QueryBuilder;
use PHPCR\PathNotFoundException;

/**
 * Document Manager
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
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

    /**
     * @var \Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface
     */
    protected $translationStrategy;

    /**
     * @var \Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface
     */
    protected $localeChooserStrategy;

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

        // initialize default translation strategies
        $this->translationStrategy = array(
            'attribute' => new AttributeTranslationStrategy,
            'child'     => new ChildTranslationStrategy,
        );
    }

    /**
     * Add or replace a translation strategy
     *
     * note that you do not need to set the default strategies attribute and child unless you want to replace them.
     *
     * @param string $key The name of the translation strategy.
     * @param \Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface $strategy the strategy that implements this label
     *
     */
    public function setTranslationStrategy($key, TranslationStrategyInterface $strategy)
    {
        $this->translationStrategy[$key] = $strategy;
    }

    /**
     * Get the translation strategy based on the strategy short name.
     *
     * @param string $key The name of the translation strategy
     *
     * @return Translation\TranslationStrategy\TranslationStrategyInterface
     *
     * @throws InvalidArgumentException if there is no strategy registered with the given key
     */
    public function getTranslationStrategy($key)
    {
        if (! isset($this->translationStrategy[$key])) {
            throw new \InvalidArgumentException("You must set a valid translator strategy for a document that contains translatable fields ($key is not a valid strategy or was not previously registered)");
        }
        return $this->translationStrategy[$key];
    }

    /**
     * Get the assigned language chooser strategy. This function considers the document is translatable
     * and thus must have an injected strategy. So don't call this on non-translatable documents
     * since it will ALWAYS fail!
     * @return Translation\LocaleChooser\LocaleChooserInterface
     */
    public function getLocaleChooserStrategy()
    {
        if (is_null($this->localeChooserStrategy)) {
            throw new \InvalidArgumentException("You must configure a language chooser strategy when having documents with the translatable annotation");
        }
        return $this->localeChooserStrategy;
    }

    /**
     * Set the locale chooser strategy for multilanguage documents.
     *
     * Note that there can be only one strategy per session. This is required if you have multilanguage
     * documents and not used if you don't have multilanguage.
     *
     * @param \Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface $strategy
     */
    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy)
    {
        $this->localeChooserStrategy = $strategy;
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
     * If the document is translatable, then the language chooser strategy is used to load the best
     * suited language for the translatable fields.
     *
     * @param null|string $className
     * @param string $id
     * @return object
     */
    public function find($className, $id)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                $node = $this->session->getNodeByIdentifier($id);
            } else {
                $document = $this->unitOfWork->getDocumentById($id);
                if ($document) {
                    $this->unitOfWork->validateClassName($document, $className);
                    return $document;
                }
                $node = $this->session->getNode($id);
            }
        } catch (PathNotFoundException $e) {
            return null;
        }

        $hints = array('fallback' => true);
        return $this->unitOfWork->createDocument($className, $node, $hints);
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
        $documents = array();

        if (UUIDHelper::isUUID(reset($ids))) {
            $nodes = $this->session->getNodesByIdentifier($ids);
        } else {
            foreach ($ids as $key => $id) {
                $document = $this->unitOfWork->getDocumentById($id);
                if ($document) {
                    try {
                        $this->unitOfWork->validateClassName($document, $className);
                        $documents[$id] = $document;
                        unset($ids[$key]);
                    } catch (\InvalidArgumentException $e) {
                        // ignore on class mismatch
                    }
                }
            }

            $nodes = $this->session->getNodes($ids);
        }

        foreach ($nodes as $node) {
            $documents[$node->getPath()] = $this->unitOfWork->createDocument($className, $node);
        }

        return new ArrayCollection($documents);
    }

    /**
     * Load the document from the content repository in the given language.
     * If $fallback is set to true, then the language chooser strategy is used to load the best suited
     * language for the translatable fields.
     *
     * If no translations can be found either using the fallback mechanism or not, an error is thrown.
     *
     * Note that this will be the same object as you got with a previous find/findTranslation call - we can't
     * allow copies of objects to exist
     *
     * @param $className
     * @param $id
     * @param $locale The language to try to load
     * @param bool $fallback Set to true if the language fallback mechanism should be used
     * @return object
     */
    public function findTranslation($className, $id, $locale, $fallback = true)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                $node = $this->session->getNodeByIdentifier($id);
            } else {
                $document = $this->unitOfWork->getDocumentById($id);
                if ($document) {
                    $this->unitOfWork->validateClassName($document, $className);
                    $class = $this->getClassMetadata(get_class($document));
                    $this->unitOfWork->doLoadTranslation($document, $class, $locale, $fallback);
                    return $document;
                }
                $node = $this->session->getNode($id);
            }
        } catch (PathNotFoundException $e) {
            return null;
        }

        $hints = array('locale' => $locale, 'fallback' => $fallback);
        return $this->unitOfWork->createDocument($className, $node, $hints);
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
    public function quote($val, $type = PropertyType::STRING)
    {
        if (null !== $type) {
            $val = PropertyType::convertType($val, $type);
        }

        return "'".str_replace("'", "''", $val)."'";
    }

    /**
     * Escape the illegal characters for inclusion in a fulltext statement. Escape Character is \\.
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
     * @param  string $string
     * @return string Escaped String
     */
    public function escapeFullText($string)
    {
        $illegalCharacters = array(
            '!' => '\\!', '(' => '\\(', ':' => '\\:', '^' => '\\^',
            '[' => '\\[', ']' => '\\]', '{' => '\\{', '}' => '\\}',
            '\"' => '\\\"', '?' => '\\?',
        );
        return strtr($string, $illegalCharacters);
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
     * Create the fluent query builder.
     *
     * After building your query, use DocumentManager::getDocumentsByQuery with the
     * query returned by QueryBuilder::getQuery()
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        $qm = $this->session->getWorkspace()->getQueryManager();
        return new QueryBuilder($qm->getQOMFactory());
    }

    /**
     * Get document results from a PHPCR query instance
     *
     * @param  \PHPCR\Query\QueryInterface $query the query instance as acquired through createQuery()
     * @param  string $documentName document class
     *
     * @return array of document instances
     */
    public function getDocumentsByQuery(QueryInterface $query, $className = null)
    {
        $this->errorIfClosed();

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
     *
     * For translatable documents has to determine the locale:
     *
     *   - If there is a non-empty @Locale that field value is used
     *   - If the document was previously loaded from the DocumentManager it has a non-empty @Locale
     *   - Otherwise its a new document. The language chooser strategy is asked for the default language and that is used to store. The field is updated with the locale.
     */
    public function persist($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleInsert($document);
    }

    /**
     * Bind the translatable fields of the document in the specified locale.
     *
     * This method will update the @Locale field if it does not match the $locale argument.
     *
     * @param $document the document to persist a translation of
     * @param $locale the locale this document currently has
     *
     * @throws PHPCRException if the document is not translatable
     */
    public function bindTranslation($document, $locale)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->bindTranslation($document, $locale);
    }

    /**
     * Get the list of locales that exist for the specified document,
     * including those not yet flushed, but bound
     *
     * @param $document the document to get the locales for
     *
     * @return array of strings with all locales existing for this particular document
     *
     * @throws PHPCRException if the document is not translatable
     */
    public function getLocalesFor($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        return $this->unitOfWork->getLocalesFor($document);
    }

    /**
     * Move the previously persisted document and all its children in the tree
     *
     * Note that this does not update the @Id fields of child documents and
     * neither fields with @Child/Children annotations. If you want to continue
     * working with the manager after a move, you are probably safest calling
     * DocumentManager::clear and re-loading the documents you need to use.
     *
     * @param object $document an already registered document
     * @param string $targetPath the target path including the nodename
     */
    public function move($document, $targetPath)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleMove($document, $targetPath);
    }

    /**
     * Remove the previously persisted document and all its children from the tree
     *
     * Be aware of the PHPCR tree structure: this removes all nodes with a path under
     * the path of this object, even if there are no @Parent / @Child annotations
     * that make the relationship explicit.
     *
     * @param object $document
     */
    public function remove($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleRemove($document);
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
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');
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
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

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
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

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
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

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
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        return $this->unitOfWork->getReferrers($document, $type, $name);
    }

    /**
     * Flush all current changes, that is save them within the phpcr session
     * and commit that session to permanent storage.
     *
     * @param object|array|null $document
     */
    public function flush($document = null)
    {
        if (null !== $document && !is_object($document) && !is_array($document)) {
             throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->commit($document);
    }

    /**
     * Gets a reference to the document identified by the given type and identifier
     * without actually loading it.
     *
     * If partial objects are allowed, this method will return a partial object that only
     * has its identifier populated. Otherwise a proxy is returned that automatically
     * loads itself on first access.
     *
     * @param string $documentName
     * @param string|object $id
     * @return mixed|object The document reference.
     */
    public function getReference($documentName, $id)
    {
        $class = $this->metadataFactory->getMetadataFor($documentName);

        // Check identity map first, if its already in there just return it.
        $document = $this->unitOfWork->getDocumentById($id);
        if ($document) {
            return $document;
        }

        $document = $this->proxyFactory->getProxy($class->name, $id);
        $this->unitOfWork->registerDocument($document, $id);

        return $document;
    }

    /**
     * Create a new version of the document that has been previously persisted
     * and flushed.
     *
     * The state that is stored is the one from the last flush, not from the
     * current document state.
     *
     * The document is made read only until you call checkout again.
     *
     * @see checkpoint
     *
     * @param object $document
     */
    public function checkin($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkin($document);
    }

    /**
     * Make a checked in document writable again.
     *
     * @param object $document
     */
    public function checkout($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkout($document);
    }

    /**
     * Do a checkin operation followed immediately by a checkout operation.
     *
     * A new version is created and the writable document stays in checked out state
     *
     * @param object $document The document
     *
     * @return void
     */
    public function checkpoint($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkpoint($document);
    }

    /**
     * Restores the current checked out document to the values of the given
     * version in storage and refreshes the document object.
     *
     * Note that this does not change anything on the version history.
     *
     * The restore is immediately propagated to the backend.
     *
     * @see findVersionByName
     *
     * @param string $DocumentVersion the version to be restored
     * @param bool $removeExisting how to handle conflicts with unique
     *      identifiers. If true, existing documents with the identical
     *      identifier will be replaced, otherwise an exception is thrown.
     */
    public function restoreVersion($documentVersion, $removeExisting = true)
    {
        $this->errorIfClosed();
        $this->unitOfWork->restoreVersion($documentVersion, $removeExisting);
    }

    /**
     * Delete the specified version to clean up the history.
     *
     * Note that you can not remove the currently active version, only old
     * versions.
     *
     * @param $documentVersion The version document as returned by findVersionByName
     *
     * @return void
     *
     * @throws \PHPCR\RepositoryException when trying to remove the root version or the last version
     */
    public function removeVersion($documentVersion)
    {
        $this->errorIfClosed();
        $this->unitOfWork->removeVersion($documentVersion);
    }

    /**
     * Get the version history information for a document
     *
     * labels will be an empty array.
     *
     * @param object $document the document of which to get the version history
     * @param int $limit an optional limit to only get the latest $limit information
     *
     * @return array of <versionname> => array("name" => <versionname>, "labels" => <array of labels>, "created" => <DateTime>)
     *         oldest version first
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->errorIfClosed();
        return $this->unitOfWork->getAllLinearVersions($document, $limit);
    }

    /**
     * Returns a read-only, detached document instance of the document at the
     * specified path with the specified version name.
     *
     * The id of the returned document representing this version is not the id
     * of the original document.
     *
     * @param null|string $className
     * @param string $id id of the document
     * @param string $versionName the version name as given by getLinearPredecessors
     *
     * @return the detached document or null if the document is not found
     *
     * @throws InvalidArgumentException if there is a document with $id but no
     *      version with $name
     * @throws UnsupportedRepositoryOperationException if the implementation
     *      does not support versioning
     */
    public function findVersionByName($className, $id, $versionName)
    {
        $this->errorIfClosed();
        return $this->unitOfWork->findVersionByName($className, $id, $versionName);
    }

    /**
     * @param  object $document
     * @return bool
     */
    public function contains($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

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
     * @param object $document
     */
    public function initializeObject($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }

        $this->unitOfWork->initializeObject($document);
    }
}
