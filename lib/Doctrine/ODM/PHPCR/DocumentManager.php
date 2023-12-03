<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
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
use Doctrine\Persistence\ObjectRepository;
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
    private SessionInterface $session;
    private Configuration $config;
    private ClassMetadataFactory $metadataFactory;
    private ?UnitOfWork $unitOfWork = null;
    private ?ProxyFactory $proxyFactory = null;
    private RepositoryFactory $repositoryFactory;
    private EventManager $evm;

    /**
     * Whether the DocumentManager is closed or not.
     */
    private bool $closed = false;

    /**
     * @var TranslationStrategyInterface[]
     */
    protected array $translationStrategy;

    protected LocaleChooserInterface $localeChooserStrategy;
    private ValueConverter $valueConverter;

    public function __construct(SessionInterface $session, Configuration $config = null, EventManager $evm = null)
    {
        $this->session = $session;
        $this->config = $config ?: new Configuration();
        $this->evm = $evm ?: new EventManager();
        $metadataFactoryClassName = $this->config->getClassMetadataFactoryName();
        $this->metadataFactory = new $metadataFactoryClassName($this);
        $this->unitOfWork = new UnitOfWork($this);
        $this->valueConverter = new ValueConverter();
        $this->proxyFactory = new ProxyFactory(
            $this,
            $this->config->getProxyDir(),
            $this->config->getProxyNamespace(),
            $this->config->getAutoGenerateProxyClasses()
        );
        $this->repositoryFactory = $this->config->getRepositoryFactory();

        // initialize default translation strategies
        $this->translationStrategy = [
            AttributeTranslationStrategy::NAME => new AttributeTranslationStrategy($this),
            ChildTranslationStrategy::NAME => new ChildTranslationStrategy($this),
        ];
    }

    public function setTranslationStrategy(string $key, TranslationStrategyInterface $strategy): void
    {
        $this->translationStrategy[$key] = $strategy;
    }

    public function getTranslationStrategy(string $key): TranslationStrategyInterface
    {
        if (!array_key_exists($key, $this->translationStrategy)) {
            throw new InvalidArgumentException("You must set a valid translator strategy for a document that contains translatable fields ($key is not a valid strategy or was not previously registered)");
        }

        return $this->translationStrategy[$key];
    }

    public function hasLocaleChooserStrategy(): bool
    {
        return isset($this->localeChooserStrategy);
    }

    public function getLocaleChooserStrategy(): LocaleChooserInterface
    {
        if (!isset($this->localeChooserStrategy)) {
            throw new InvalidArgumentException('You must configure a language chooser strategy when having documents with the translatable mapping');
        }

        return $this->localeChooserStrategy;
    }

    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy): void
    {
        $this->localeChooserStrategy = $strategy;
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->proxyFactory;
    }

    public function getEventManager(): EventManager
    {
        return $this->evm;
    }

    public function getPhpcrSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * Factory method to create a Document Manager.
     */
    public static function create(SessionInterface $session, Configuration $config = null, EventManager $evm = null): DocumentManager
    {
        return new self($session, $config, $evm);
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    /**
     * Throws an exception if the DocumentManager is closed or currently not active.
     *
     * @throws PHPCRException if the DocumentManager is closed
     */
    private function errorIfClosed(): void
    {
        if ($this->closed) {
            throw PHPCRException::documentManagerClosed();
        }
    }

    public function isOpen(): bool
    {
        return !$this->closed;
    }

    public function getClassMetadata($className): ClassMetadata
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
     * @param string|null $className optional object class name to use
     * @param string      $id        the path or uuid of the document to find
     *
     * @return object|null the document if found, otherwise null
     */
    public function find($className, $id): ?object
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                try {
                    $id = $this->session->getNodeByIdentifier($id)->getPath();
                } catch (ItemNotFoundException $e) {
                    return null;
                }
            } elseif (0 !== strpos($id, '/')) {
                $id = '/'.$id;
            }

            $document = $this->unitOfWork->getDocumentById($id);
            if ($document) {
                try {
                    $this->unitOfWork->validateClassName($document, $className);

                    return $document;
                } catch (ClassMismatchException $e) {
                    return null;
                }
            }
            $node = $this->session->getNode($id);
        } catch (PathNotFoundException $e) {
            return null;
        }

        $hints = ['fallback' => true];

        try {
            return $this->unitOfWork->getOrCreateDocument($className, $node, $hints);
        } catch (ClassMismatchException $e) {
            return null;
        }
    }

    public function findMany(?string $className, array $ids): Collection
    {
        // when loading duplicate ID's the resulting response would be a collection of unique ids,
        // but having duplicates would also cause a lot of overhead as well as break translation loading,
        // so pre filter unique, see https://github.com/doctrine/phpcr-odm/pull/795
        $ids = array_unique($ids);
        $uuids = [];
        foreach ($ids as $key => $id) {
            if (UUIDHelper::isUUID($id)) {
                $uuids[$id] = $key;
            } elseif (0 !== strpos($id, '/')) {
                $ids[$key] = '/'.$id;
            }
        }

        if (!empty($uuids)) {
            $nodesByIdentifier = $this->session->getNodesByIdentifier(array_keys($uuids));

            /** @var NodeInterface $node */
            foreach ($nodesByIdentifier as $node) {
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

    public function findTranslation(?string $className, string $id, string $locale, bool $fallback = true): ?object
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                try {
                    $id = $this->session->getNodeByIdentifier($id)->getPath();
                } catch (ItemNotFoundException $e) {
                    return null;
                }
            } elseif (0 !== strpos($id, '/')) {
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

        $hints = ['locale' => $locale, 'fallback' => $fallback];

        return $this->unitOfWork->getOrCreateDocument($className, $node, $hints);
    }

    public function getRepository($className): ObjectRepository
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    public function quote(string $val, int $type = PropertyType::STRING): string
    {
        if (null !== $type) {
            $val = $this->valueConverter->convertType($val, $type);
        }

        return "'".str_replace("'", "''", $val)."'";
    }

    public function escapeFullText(string $string): string
    {
        $illegalCharacters = [
            '!' => '\\!', '(' => '\\(', ':' => '\\:', '^' => '\\^',
            '[' => '\\[', ']' => '\\]', '{' => '\\{', '}' => '\\}',
            '\"' => '\\\"', '?' => '\\?', "'" => "''",
        ];

        return strtr($string, $illegalCharacters);
    }

    public function createPhpcrQuery(string $statement, string $language): QueryInterface
    {
        return $this->session
            ->getWorkspace()
            ->getQueryManager()
            ->createQuery($statement, $language)
        ;
    }

    public function createQuery(string $statement, string $language): Query
    {
        $phpcrQuery = $this->createPhpcrQuery($statement, $language);

        return new Query($phpcrQuery, $this);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        $qm = $this->session->getWorkspace()->getQueryManager();
        $qomf = $qm->getQOMFactory();

        $converter = new ConverterPhpcr($this, $qomf);

        $builder = new QueryBuilder();
        $builder->setConverter($converter);

        return $builder;
    }

    public function createPhpcrQueryBuilder(): PhpcrQueryBuilder
    {
        $qm = $this->session->getWorkspace()->getQueryManager();

        return new PhpcrQueryBuilder($qm->getQOMFactory());
    }

    public function getDocumentsByPhpcrQuery(QueryInterface $query, string $className = null, string $primarySelector = null): Collection
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
     * @throws InvalidArgumentException if $document is not an object
     */
    public function persist($document): void
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleInsert($document);
    }

    public function bindTranslation(object $document, string $locale): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->bindTranslation($document, $locale);
    }

    public function removeTranslation(object $document, string $locale): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->removeTranslation($document, $locale);
    }

    public function getLocalesFor(object $document, bool $includeFallbacks = false): array
    {
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

    public function isDocumentTranslatable(object $document): bool
    {
        $metadata = $this->getClassMetadata(get_class($document));

        return $this->unitOfWork->isDocumentTranslatable($metadata);
    }

    public function move(object $document, string $targetPath): void
    {
        if (0 !== strpos($targetPath, '/')) {
            $targetPath = '/'.$targetPath;
        }

        $this->errorIfClosed();
        $this->unitOfWork->scheduleMove($document, $targetPath);
    }

    public function reorder(object $document, string $srcName, string $targetName, bool $before): void
    {
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
     * @throws InvalidArgumentException if $document is not an object
     */
    public function remove($document): void
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
     * @param object $document the document to merge over a persisted document
     *                         with the same id
     *
     * @return object The managed document where $document has been merged
     *                into. This is *not* the same instance as the parameter.
     *
     * @throws InvalidArgumentException if $document is not an object
     */
    public function merge($document): object
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
     * @param object $document the object to detach
     *
     * @throws InvalidArgumentException if $document is not an object
     */
    public function detach($document): void
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
     * @throws InvalidArgumentException if $document is not an object
     */
    public function refresh($document): void
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->refresh($document);
    }

    public function getChildren(object $document, $filter = null, int $fetchDepth = -1, string $locale = null): ChildrenCollection
    {
        $this->errorIfClosed();

        return new ChildrenCollection($this, $document, $filter, $fetchDepth, $locale);
    }

    public function getReferrers(object $document, string $type = null, string $name = null, string $locale = null, $refClass = null): ReferrersCollection
    {
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
    public function flush($document = null): void
    {
        if (null !== $document && !is_object($document) && !is_array($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->errorIfClosed();
        $this->unitOfWork->commit($document);
    }

    public function getReference(string $documentName, $id)
    {
        return $this->unitOfWork->getOrCreateProxy($id, $documentName);
    }

    public function checkin(object $document): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->checkin($document);
    }

    public function checkout(object $document): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->checkout($document);
    }

    public function checkpoint(object $document): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->checkpoint($document);
    }

    public function restoreVersion(object $documentVersion, bool $removeExisting = true): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->restoreVersion($documentVersion, $removeExisting);
    }

    public function removeVersion(object $documentVersion): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->removeVersion($documentVersion);
    }

    public function getAllLinearVersions(object $document, int $limit = -1): array
    {
        $this->errorIfClosed();

        return $this->unitOfWork->getAllLinearVersions($document, $limit);
    }

    public function findVersionByName(?string $className, string $id, string $versionName): ?object
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
     * @return bool true if the repository contains the object, false otherwise
     *
     * @throws InvalidArgumentException if $document is not an object
     */
    public function contains($document): bool
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        return $this->unitOfWork->contains($document);
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    /**
     * {@inheritdoc}
     *
     * Clears the DocumentManager. All entities that are currently managed
     * by this DocumentManager become detached.
     */
    public function clear($className = null): void
    {
        if (null === $className) {
            $this->unitOfWork->clear();
        } else {
            // TODO
            throw new PHPCRException('DocumentManager#clear($className) not yet implemented.');
        }
    }

    public function close(): void
    {
        $this->clear();
        $this->closed = true;
    }

    public function initializeObject($document): void
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Parameter $document needs to be an object, '.gettype($document).' given');
        }

        $this->unitOfWork->initializeObject($document);
    }

    public function getNodeForDocument(object $document): NodeInterface
    {
        $path = $this->unitOfWork->getDocumentId($document);

        return $this->session->getNode($path);
    }

    public function getDocumentId(object $document): ?string
    {
        return $this->unitOfWork->getDocumentId($document);
    }
}
