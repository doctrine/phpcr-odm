<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\ODM\PHPCR\Query\Builder\ConverterPhpcr;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\Repository\RepositoryFactory;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\ChildTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyType;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\RowInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\QOM\QueryBuilder as PhpcrQueryBuilder;
use PHPCR\Util\UUIDHelper;
use PHPCR\Util\ValueConverter;

/**
 * Document Manager.
 *
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 */
class DocumentManager implements DocumentManagerInterface
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
     * The repository factory used to create dynamic repositories.
     *
     * @var RepositoryFactory
     */
    private $repositoryFactory;

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
     * @var TranslationStrategyInterface[]
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
        $metadataFactoryClassName = $this->config->getClassMetadataFactoryName();
        $this->metadataFactory = new $metadataFactoryClassName($this);
        $this->unitOfWork = new UnitOfWork($this);
        $this->valueConverter = new ValueConverter();
        $this->proxyFactory = new ProxyFactory($this,
            $this->config->getProxyDir(),
            $this->config->getProxyNamespace(),
            $this->config->getAutoGenerateProxyClasses()
        );
        $this->repositoryFactory = $this->config->getRepositoryFactory();

        // initialize default translation strategies
        $this->translationStrategy = [
            AttributeTranslationStrategy::NAME => new AttributeTranslationStrategy($this),
            ChildTranslationStrategy::NAME     => new ChildTranslationStrategy($this),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslationStrategy($key, TranslationStrategyInterface $strategy)
    {
        $this->translationStrategy[$key] = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationStrategy($key)
    {
        if (!isset($this->translationStrategy[$key])) {
            throw new InvalidArgumentException("You must set a valid translator strategy for a document that contains translatable fields ($key is not a valid strategy or was not previously registered)");
        }

        return $this->translationStrategy[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function hasLocaleChooserStrategy()
    {
        return isset($this->localeChooserStrategy);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocaleChooserStrategy()
    {
        if (!isset($this->localeChooserStrategy)) {
            throw new InvalidArgumentException('You must configure a language chooser strategy when having documents with the translatable annotation');
        }

        return $this->localeChooserStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy)
    {
        $this->localeChooserStrategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventManager()
    {
        return $this->evm;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isOpen()
    {
        return !$this->closed;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * {@inheritdoc}
     *
     * Find the Document with the given id.
     *
     * Will return null if the document was not found. A document is considered
     * not found if the data at $id is not instance of of the specified
     * $className. To get the document regardless of its class, pass null.
     *
     * If the document is translatable, then the language chooser strategy is
     * used to load the best suited language for the translatable fields.
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
                try {
                    $id = $this->session->getNodeByIdentifier($id)->getPath();
                } catch (ItemNotFoundException $e) {
                    return;
                }
            } elseif (strpos($id, '/') !== 0) {
                $id = '/'.$id;
            }

            $document = $this->unitOfWork->getDocumentById($id);
            if ($document) {
                try {
                    $this->unitOfWork->validateClassName($document, $className);

                    return $document;
                } catch (ClassMismatchException $e) {
                    return;
                }
            }
            $node = $this->session->getNode($id);
        } catch (PathNotFoundException $e) {
            return;
        }

        $hints = ['fallback' => true];

        try {
            return $this->unitOfWork->getOrCreateDocument($className, $node, $hints);
        } catch (ClassMismatchException $e) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMany($className, array $ids)
    {
        $uuids = [];
        foreach ($ids as $key => $id) {
            if (UUIDHelper::isUUID($id)) {
                $uuids[$id] = $key;
            } elseif (strpos($id, '/') !== 0) {
                $ids[$key] = '/'.$id;
            }
        }

        if (!empty($uuids)) {
            $nodes = $this->session->getNodesByIdentifier(array_keys($uuids));

            /** @var NodeInterface $node */
            foreach ($nodes as $node) {
                $id = $node->getPath();
                $ids[$uuids[$node->getIdentifier()]] = $id;
                unset($uuids[$id]);
            }

            if (!empty($uuids)) {
                // skip not found ids
                $ids = array_diff($ids, array_keys($uuids));
            }
        }

        $nodes = $this->session->getNodes($ids);
        $hints = ['fallback' => true];
        $documents = $this->unitOfWork->getOrCreateDocuments($className, $nodes, $hints);

        return new ArrayCollection($documents);
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslation($className, $id, $locale, $fallback = true)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                try {
                    $id = $this->session->getNodeByIdentifier($id)->getPath();
                } catch (ItemNotFoundException $e) {
                    return;
                }
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
            return;
        }

        $hints = ['locale' => $locale, 'fallback' => $fallback];

        return $this->unitOfWork->getOrCreateDocument($className, $node, $hints);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($val, $type = PropertyType::STRING)
    {
        if (null !== $type) {
            $val = $this->valueConverter->convertType($val, $type);
        }

        return "'".str_replace("'", "''", $val)."'";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeFullText($string)
    {
        $illegalCharacters = [
            '!'  => '\\!', '(' => '\\(', ':' => '\\:', '^' => '\\^',
            '['  => '\\[', ']' => '\\]', '{' => '\\{', '}' => '\\}',
            '\"' => '\\\"', '?' => '\\?', "'" => "''",
        ];

        return strtr($string, $illegalCharacters);
    }

    /**
     * {@inheritdoc}
     */
    public function createPhpcrQuery($statement, $language)
    {
        $qm = $this->session->getWorkspace()->getQueryManager();

        return $qm->createQuery($statement, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($statement, $language)
    {
        $phpcrQuery = $this->createPhpcrQuery($statement, $language);

        return new Query($phpcrQuery, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        $qm = $this->session->getWorkspace()->getQueryManager();
        $qomf = $qm->getQOMFactory();

        $converter = new ConverterPhpcr($this, $qomf);

        $builder = new QueryBuilder();
        $builder->setConverter($converter);

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function createPhpcrQueryBuilder()
    {
        $qm = $this->session->getWorkspace()->getQueryManager();

        return new PhpcrQueryBuilder($qm->getQOMFactory());
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentsByPhpcrQuery(QueryInterface $query, $className = null, $primarySelector = null)
    {
        $this->errorIfClosed();

        $result = $query->execute();

        $ids = [];
        /** @var RowInterface $row */
        foreach ($result->getRows() as $row) {
            $ids[] = $row->getPath($primarySelector);
        }

        return $this->findMany($className, $ids);
    }

    /**
     * {@inheritdoc}
     *
     * No PHPCR node will be created yet, this only happens on flush.
     *
     * For translatable documents has to determine the locale:
     *
     *   - If there is a non-empty Locale mapping that field value is used
     *   - If the document was previously loaded from the DocumentManager it
     *      has a non-empty Locale mapping
     *   - Otherwise its a new document. The language chooser strategy is asked
     *      for the default language and that is used to store. The field is
     *      updated with the locale.
     *
     * @param object $document the document to persist
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function persist($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleInsert($document);
    }

    /**
     * {@inheritdoc}
     */
    public function bindTranslation($document, $locale)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->bindTranslation($document, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, $locale)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->removeTranslation($document, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, $includeFallbacks = false)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        $metadata = $this->getClassMetadata(get_class($document));
        $locales = $this->unitOfWork->getLocalesFor($document);
        if ($includeFallbacks) {
            $fallBackLocales = [];
            foreach ($locales as $locale) {
                $fallBackLocales = array_merge($fallBackLocales, $this->localeChooserStrategy->getFallbackLocales($document, $metadata, $locale));
            }

            $locales = array_unique(array_merge($locales, $fallBackLocales));
        }

        return $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function isDocumentTranslatable($document)
    {
        $metadata = $this->getClassMetadata(get_class($document));

        return $this->unitOfWork->isDocumentTranslatable($metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $targetPath)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        if (strpos($targetPath, '/') !== 0) {
            $targetPath = '/'.$targetPath;
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleMove($document, $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $srcName, $targetName, $before)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleReorder($document, $srcName, $targetName, $before);
    }

    /**
     * {@inheritdoc}
     *
     * Remove the previously persisted document and all its children from the tree
     *
     * Be aware of the PHPCR tree structure: this removes all nodes with a path under
     * the path of this object, even if there are no Parent / Child mappings
     * that make the relationship explicit.
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function remove($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleRemove($document);
    }

    /**
     * {@inheritdoc}
     *
     * Merge the state of the detached object into the persistence context of
     * this ObjectManager and returns the managed copy of the object.
     *
     * This will copy all fields of $document over the fields of the managed
     * document and then cascade the merge to relations as configured.
     *
     * The object passed to merge will *not* become associated/managed with
     * this ObjectManager.
     *
     * @param object $document The document to merge over a persisted document
     *                         with the same id.
     *
     * @throws InvalidArgumentException if $document is not an object.
     *
     * @return object The managed document where $document has been merged
     *                into. This is *not* the same instance as the parameter.
     */
    public function merge($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return $this->unitOfWork->merge($document);
    }

    /**
     * {@inheritdoc}
     *
     * Detaches an object from the ObjectManager
     *
     * If there are any not yet flushed changes on this object (including
     * removal of the object) will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $document The object to detach.
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function detach($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->detach($document);
    }

    /**
     * {@inheritdoc}
     *
     * Refresh the given document by querying the PHPCR to get the current state.
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function refresh($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->refresh($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren($document, $filter = null, $fetchDepth = -1, $locale = null)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return new ChildrenCollection($this, $document, $filter, $fetchDepth, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferrers($document, $type = null, $name = null, $locale = null, $refClass = null)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return new ReferrersCollection($this, $document, $type, $name, $locale, $refClass);
    }

    /**
     * {@inheritdoc}
     *
     * Flush all current changes, that is save them within the phpcr session
     * and commit that session to permanent storage.
     *
     * @param object|array|null $document optionally limit to a specific
     *                                    document or an array of documents
     *
     * @throws InvalidArgumentException if $document is neither null nor a
     *                                  document or an array of documents
     */
    public function flush($document = null)
    {
        if (null !== $document && !is_object($document) && !is_array($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->commit($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($documentName, $id)
    {
        return $this->unitOfWork->getOrCreateProxy($id, $documentName);
    }

    /**
     * {@inheritdoc}
     */
    public function checkin($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkin($document);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkout($document);
    }

    /**
     * {@inheritdoc}
     */
    public function checkpoint($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->checkpoint($document);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreVersion($documentVersion, $removeExisting = true)
    {
        $this->errorIfClosed();
        $this->unitOfWork->restoreVersion($documentVersion, $removeExisting);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVersion($documentVersion)
    {
        $this->errorIfClosed();
        $this->unitOfWork->removeVersion($documentVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();

        return $this->unitOfWork->getAllLinearVersions($document, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function findVersionByName($className, $id, $versionName)
    {
        $this->errorIfClosed();

        return $this->unitOfWork->findVersionByName($className, $id, $versionName);
    }

    /**
     * {@inheritdoc}
     *
     * Check if this repository contains the object
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     *
     * @return bool true if the repository contains the object, false otherwise
     */
    public function contains($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        return $this->unitOfWork->contains($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * {@inheritdoc}
     *
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
            throw new PHPCRException('DocumentManager#clear($className) not yet implemented.');
        }
    }

    /**
     * {@inheritdoc}
     *
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
     * {@inheritdoc}
     */
    public function initializeObject($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->unitOfWork->initializeObject($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeForDocument($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $path = $this->unitOfWork->getDocumentId($document);

        return $this->session->getNode($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentId($document)
    {
        return $this->unitOfWork->getDocumentId($document);
    }
}
