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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\ChildTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Query\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;

use PHPCR\SessionInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Util\UUIDHelper;
use PHPCR\PropertyType;
use PHPCR\Util\QOM\QueryBuilder as PhpcrQueryBuilder;
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
     * @var ClassMetadataFactory
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
     * @var TranslationStrategyInterface
     */
    protected $translationStrategy;

    /**
     * @var LocaleChooserInterface
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
     * Note that you do not need to set the default strategies attribute and
     * child unless you want to replace them.
     *
     * @param string $key The name of the translation strategy.
     * @param TranslationStrategyInterface $strategy the strategy that
     *      is used with this key
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
     * @throws \InvalidArgumentException if there is no strategy registered with the given key
     */
    public function getTranslationStrategy($key)
    {
        if (!isset($this->translationStrategy[$key])) {
            throw new \InvalidArgumentException("You must set a valid translator strategy for a document that contains translatable fields ($key is not a valid strategy or was not previously registered)");
        }

        return $this->translationStrategy[$key];
    }

    /**
     * Get the assigned language chooser strategy previously set with
     * setLocaleChooserStrategy
     *
     * @return LocaleChooserInterface
     */
    public function getLocaleChooserStrategy()
    {
        if (!isset($this->localeChooserStrategy)) {
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
     * Access the underlying PHPCR session this manager is using.
     *
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
     * @param Configuration    $config
     * @param EventManager     $evm
     *
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
     * @return boolean true if open, false if closed
     */
    public function isOpen()
    {
        return !$this->closed;
    }

    /**
     * Get the ClassMetadata object for a class
     *
     * @param string $className document class name to get metadata for
     *
     * @return \Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * Find the Document with the given id.
     *
     * Will return null if the document was not found.
     *
     * If the document is translatable, then the language chooser strategy is
     * used to load the best * suited language for the translatable fields.
     *
     * @param null|string $className optional object class name to use
     * @param string      $id        the path or uuid of the document to find
     *
     * @return object|null the document if found, otherwise null
     */
    public function find($className, $id)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                $id = $this->session->getNodeByIdentifier($id)->getPath();
            } elseif (strpos($id, '/') !== 0) {
                $id = '/'.$id;
            }

            $document = $this->unitOfWork->getDocumentById($id);
            if ($document) {
                $this->unitOfWork->validateClassName($document, $className);

                return $document;
            }

            $node = $this->session->getNode($id);
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
     * @param array       $ids
     *
     * @return object
     */
    public function findMany($className, array $ids)
    {
        $documents = array();

        $uuids = array();
        foreach ($ids as $key => $id) {
            if (UUIDHelper::isUUID($id)) {
                $uuids[$id] = $key;
            } elseif (strpos($id, '/') !== 0) {
                $ids[$key] = '/'.$id;
            }
        }

        if (!empty($uuids)) {
            $nodes = $this->session->getNodesByIdentifier(array_keys($uuids));

            foreach ($nodes as $node) {
                /** @var $node \PHPCR\NodeInterface */
                $ids[$uuids[$node->getIdentifier()]] = $node->getPath();
            }
        }

        $nodes = $this->session->getNodes($ids);

        foreach ($ids as $id) {
            $document = $this->unitOfWork->getDocumentById($id);
            if ($document) {
                try {
                    $this->unitOfWork->validateClassName($document, $className);
                    $documents[$id] = $document;
                } catch (\InvalidArgumentException $e) {
                    // ignore on class mismatch
                }
            } elseif (isset($nodes[$id])) {
                $documents[$id] = $this->unitOfWork->createDocument($className, $nodes[$id]);
            }
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
     * @param null|string $className the class name to find the translation for
     * @param string      $id        the identifier of the class (path or uuid)
     * @param string      $locale    The language to try to load
     * @param boolean     $fallback  Set to true if the language fallback mechanism should be used
     *
     * @return object the translated document
     */
    public function findTranslation($className, $id, $locale, $fallback = true)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                $id = $this->session->getNodeByIdentifier($id)->getPath();
            } elseif (strpos($id, '/') !== 0) {
                $id = '/'.$id;
            }

            $document = $this->unitOfWork->getDocumentById($id);
            if ($document) {
                $this->unitOfWork->validateClassName($document, $className);
                $class = $this->getClassMetadata(get_class($document));
                $this->unitOfWork->doLoadTranslation($document, $class, $locale, $fallback);

                return $document;
            }

            $node = $this->session->getNode($id);
        } catch (PathNotFoundException $e) {
            return null;
        }

        $hints = array('locale' => $locale, 'fallback' => $fallback);

        return $this->unitOfWork->createDocument($className, $node, $hints);
    }

    /**
     * Get the repository for the specified class
     *
     * @param string $className The document class name to get the repository for
     *
     * @return DocumentRepository
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
     * @param string $val
     * @param int    $type
     *
     * @return string
     *
     * @see \PHPCR\PropertyType
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
     * @param string $string
     *
     * @return string Escaped String
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
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
     * Create a PHPCR Query from a query string in the specified query language to be
     * used with getDocumentsByPhpcrQuery()
     *
     * Note that it is better to use {@link createQuery}, which returns a native ODM
     * query object, when working with the ODM.
     *
     * See \PHPCR\Query\QueryInterface for list of generally supported types
     * and check your implementation documentation if you want to use a
     * different language.
     *
     * @param string $statement the statement in the specified language
     * @param string $language  the query language
     *
     * @return PHPCR\Query\QueryInterface
     */
    public function createPhpcrQuery($statement, $language)
    {
        $qm = $this->session->getWorkspace()->getQueryManager();

        return $qm->createQuery($statement, $language);
    }

    /**
     * Create a ODM Query from a query string in the specified query language to be
     * used with getDocumentsByPhpcrQuery()
     *
     * See \PHPCR\Query\QueryInterface for list of generally supported types
     * and check your implementation documentation if you want to use a
     * different language.
     *
     * @param string $statement the statement in the specified language
     * @param string $language  the query language
     *
     * @return \Doctrine\ODM\PHPCR\Query
     */
    public function createQuery($statement, $language)
    {
        $phpcrQuery = $this->createPhpcrQuery($statement, $language);

        return new Query($phpcrQuery, $this);
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

        return new QueryBuilder($this, $qm->getQOMFactory());
    }

    /**
     * Create lower level PHPCR query builder.
     *
     * NOTE: The ODM QueryBuilder (@link createQueryBuilder) is prefered over
     *       the PHPCR QueryBuilder when working with the ODM.
     *
     * @return PHPCR\Util\QOM\QueryBuilder
     */
    public function createPhpcrQueryBuilder()
    {
        $qm = $this->session->getWorkspace()->getQueryManager();

        return new PhpcrQueryBuilder($qm->getQOMFactory());
    }

    /**
     * Get document results from a PHPCR query instance
     *
     * @param PHPCR\Query\QueryInterface $query the query instance as
     *      acquired through createPhpcrQuery()
     * @param string $className document class
     *
     * @return array of document instances
     */
    public function getDocumentsByPhpcrQuery(QueryInterface $query, $className = null)
    {
        $this->errorIfClosed();

        $result = $query->execute();

        $ids = array();
        foreach ($result->getRows() as $row) {
            /** @var $row \PHPCR\Query\RowInterface */
            $ids[] = $row->getPath();
        }

        return $this->findMany($className, $ids);
    }

    /**
     * {@inheritDoc}
     *
     * No PHPCR node will be created yet, this only happens on flush.
     *
     * For translatable documents has to determine the locale:
     *
     *   - If there is a non-empty @Locale that field value is used
     *   - If the document was previously loaded from the DocumentManager it
     *      has a non-empty @Locale
     *   - Otherwise its a new document. The language chooser strategy is asked
     *      for the default language and that is used to store. The field is
     *      updated with the locale.
     *
     * @param object $document the document to persist
     */
    public function persist($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleInsert($document);
    }

    /**
     * Bind the translatable fields of the document in the specified locale.
     *
     * This method will update the @Locale field if it does not match the $locale argument.
     *
     * @param object $document the document to persist a translation of
     * @param string $locale   the locale this document currently has
     *
     * @throws PHPCRException if the document is not translatable
     */
    public function bindTranslation($document, $locale)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->bindTranslation($document, $locale);
    }

    /**
     * Remove the translatable fields of the document in the specified locale
     *
     * @param object $document the document to persist a translation of
     * @param string $locale   the locale this document currently has
     */
    public function removeTranslation($document, $locale)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->removeTranslation($document, $locale);
    }

    /**
     * Get the list of locales that exist for the specified document,
     * including those not yet flushed, but bound
     *
     * @param object  $document         the document to get the locales for
     * @param boolean $includeFallbacks if to include the available language fallbacks
     *
     * @return array of strings with all locales existing for this particular document
     *
     * @throws PHPCRException if the document is not translatable
     */
    public function getLocalesFor($document, $includeFallbacks = false)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        $metadata = $this->getClassMetadata(get_class($document));
        $locales = $this->unitOfWork->getLocalesFor($document);
        if ($includeFallbacks) {
            $fallBackLocales = array();
            foreach ($locales as $locale) {
                $fallBackLocales = array_merge($fallBackLocales, $this->localeChooserStrategy->getPreferredLocalesOrder($document, $metadata, $locale));
            }

            $locales = array_unique(array_merge($locales, $fallBackLocales));
        }

        return $locales;
    }

    /**
     * Determine whether this document is translatable.
     *
     * To be translatable, it needs a translation strategy and have at least
     * one translated field.
     *
     * @param object $document the document to get the locales for
     *
     * @return bool
     */
    public function isDocumentTranslatable($document)
    {
        $metadata = $this->getClassMetadata(get_class($document));

        return $this->unitOfWork->isDocumentTranslatable($metadata);
    }

    /**
     * Move the previously persisted document and all its children in the tree
     *
     * Note that this does not update the @Id fields of child documents and
     * neither fields with @Child/Children annotations. If you want to continue
     * working with the manager after a move, you are probably safest calling
     * DocumentManager::clear and re-loading the documents you need to use.
     *
     * @param object $document   an already registered document
     * @param string $targetPath the target path including the nodename
     */
    public function move($document, $targetPath)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        if (strpos($targetPath, '/') !== 0) {
            $targetPath = '/'.$targetPath;
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleMove($document, $targetPath);
    }

    /**
     * Reorder a child of the given document
     *
     * Note that this does not update the fields with @Child/Children annotations.
     * If you want to continue working with the manager after a reorder, you are probably
     * safest calling DocumentManager::clear and re-loading the documents you need to use.
     *
     * @param object  $document   an already registered document
     * @param string  $srcName    the nodename of the child to be reordered
     * @param string  $targetName the nodename of the target of the reordering
     * @param boolean $before     insert before or after the target
     */
    public function reorder($document, $srcName, $targetName, $before)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleReorder($document, $srcName, $targetName, $before);
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return $this->unitOfWork->merge($document);
    }

    /**
     * Detaches an object from the ObjectManager, causing a managed object to
     * become detached. Unflushed changes made to the object if any
     * (including removal of the object), will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $document The object to detach.
     */
    public function detach($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->detach($document);
    }

    /**
     * Refresh the given document by querying the PHPCR to get the current state.
     *
     * @param object $document
     */
    public function refresh($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->refresh($document);
    }

    /**
     * Get the child documents of a given document using an optional filter.
     *
     * This methods gets all child nodes as a collection of documents that matches
     * a given filter (same as PHPCR Node::getNodes)
     *
     * @param object       $document           document instance which children should be loaded
     * @param string|array $filter             optional filter to filter on children names
     * @param integer      $fetchDepth         optional fetch depth if supported by the PHPCR session
     * @param boolean      $ignoreUntranslated if to ignore children that are not translated to the current locale
     *
     * @return \Doctrine\Common\Collections\Collection collection of child documents
     */
    public function getChildren($document, $filter = null, $fetchDepth = null, $ignoreUntranslated = true)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return $this->unitOfWork->getChildren($document, $filter, $fetchDepth, $ignoreUntranslated);
    }

    /**
     * Get the documents that refer a given document using an optional name.
     *
     * This methods gets all nodes as a collection of documents that refer the
     * given document and matches a given name.
     *
     * @param object       $document document instance which referrers should be loaded
     * @param string|array $name     optional name to match on referrers names
     *
     * @return \Doctrine\Common\Collections\Collection collection of referrer documents
     */
    public function getReferrers($document, $type = null, $name = null)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
     * @param string        $documentName
     * @param string|object $id
     *
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
     */
    public function checkpoint($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
     * @param string $documentVersion the version to be restored
     * @param bool   $removeExisting  how to handle conflicts with unique
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
     * @param object $documentVersion The version document as returned by findVersionByName
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
     * @param int    $limit    an optional limit to only get the latest $limit information
     *
     * @return array of <versionname> => array("name" => <versionname>, "labels" => <array of labels>, "created" => <DateTime>)
     *         oldest version first
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
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
     * @param string      $id          id of the document
     * @param string      $versionName the version name as given by getLinearPredecessors
     *
     * @return object the detached document or null if the document is not found
     *
     * @throws \InvalidArgumentException if there is a document with $id but no
     *      version with $name
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the implementation
     *      does not support versioning
     */
    public function findVersionByName($className, $id, $versionName)
    {
        $this->errorIfClosed();

        return $this->unitOfWork->findVersionByName($className, $id, $versionName);
    }

    /**
     * Check if this repository contains the object
     *
     * @param object $document
     *
     * @return boolean true if the repository contains the object, false otherwise
     */
    public function contains($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        return $this->unitOfWork->contains($document);
    }

    /**
     * Client code should not access the UnitOfWork except in special
     * circumstances. Methods on UnitOfWork might be changed without special
     * notice
     *
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
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->unitOfWork->initializeObject($document);
    }

    /**
     * Return the node of the given object
     *
     * @param object $document
     *
     * @return \PHPCR\NodeInterface
     *
     * @throws \InvalidArgumentException if the document is not an object
     * @throws \PHPCR\PHPCRException     if the document is not managed
     */
    public function getNodeForDocument($document)
    {
        if (!is_object($document)) {
            throw new \InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $path = $this->unitOfWork->getDocumentId($document);

        return $this->session->getNode($path);
    }
}
