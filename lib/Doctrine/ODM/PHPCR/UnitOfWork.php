<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\Event\ListenersInvoker;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;
use Doctrine\ODM\PHPCR\Event\PreUpdateEventArgs;
use Doctrine\ODM\PHPCR\Exception\CascadeException;
use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Id\IdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Tools\Helper\PrefetchHelper;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationNodesWarmer;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Event\OnClearEventArgs;
use Jackalope\Session as JackalopeSession;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Util\NodeHelper;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;

/**
 * The unit of work keeps track of all pending operations.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Pascal Helfenstein <nicam@nicam.ch>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Brian King <brian@liip.ch>
 * @author David Buchmann <david@liip.ch>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class UnitOfWork
{
    /**
     * The document is not persisted, but a valid mapped document.
     */
    public const STATE_NEW = 1;

    /**
     * The document is tracked and will be updated on flush.
     */
    public const STATE_MANAGED = 2;

    /**
     * The document is scheduled for removal.
     */
    public const STATE_REMOVED = 3;

    /**
     * There is a corresponding Node in storage, but this document is not bound to it anymore.
     */
    public const STATE_DETACHED = 4;

    /**
     * Used for versions of documents (these are unmodifiable frozen nodes).
     */
    public const STATE_FROZEN = 5;

    private ?DocumentManagerInterface $dm = null;

    private array $identityMap = [];
    private array $documentIds = [];

    /**
     * Track version history of the version documents we create, indexed by spl_object_hash.
     *
     * @var VersionHistoryInterface[]
     */
    private array $documentHistory = [];

    /**
     * Track version objects of the version documents we create, indexed by spl_object_hash.
     *
     * @var VersionInterface[]
     */
    private array $documentVersion = [];

    private array $documentState = [];

    /**
     * Hashmap of spl_object_hash => locale => hashmap of all translated
     * document fields to store fields until the flush, in case the user is
     * using bindTranslation to store more than one locale in one flush.
     */
    private array $documentTranslations = [];

    /**
     * Hashmap of spl_object_hash => { original => locale , current => locale }
     * The original vs current locale is used to detect if the user changed the
     * mapped locale field of a document after the last call to bindTranslation.
     */
    private array $documentLocales = [];

    /**
     * PHPCR always returns and updates the whole data of a document. If on update data is "missing"
     * this means the data is deleted. This also applies to attachments. This is why we need to ensure
     * that data that is not mapped is not lost. This map here saves all the "left-over" data and keeps
     * track of it if necessary.
     */
    private array $nonMappedData = [];

    private array $originalData = [];
    private array $originalTranslatedData = [];
    private array $documentChangesets = [];

    /**
     * List of documents that have a changed field to be updated on next flush.
     *
     * oid => document
     */
    private array $scheduledUpdates = [];

    /**
     * List of documents that will be inserted on next flush.
     *
     * oid => document
     */
    private array $scheduledInserts = [];

    /**
     * List of documents that will be moved on next flush.
     *
     * oid => array(document, target path)
     */
    private array $scheduledMoves = [];

    /**
     * List of parent documents that have children that will be reordered on next flush.
     *
     * parent oid => list of array with records array(parent document, srcName, targetName, before) with
     * - parent document the document of the child to be reordered
     * - srcName the Nodename of the document to be moved,
     * - targetName the Nodename of the document to move srcName to
     * - before a boolean telling whether to move srcName before or after targetName.
     */
    private array $scheduledReorders = [];

    /**
     * List of documents that will be removed on next flush.
     *
     * oid => document
     */
    private array $scheduledRemovals = [];

    private array $visitedCollections = [];
    private array $changesetComputed = [];

    /**
     * @var IdGenerator[]
     */
    private array $idGenerators = [];

    /**
     * Used to generate uuid when we need to build references before flushing.
     */
    private \Closure $uuidGenerator;

    private SessionInterface $session;
    private ListenersInvoker $eventListenersInvoker;
    private EventManager $eventManager;
    private DocumentClassMapperInterface $documentClassMapper;
    private PrefetchHelper $prefetchHelper;
    private bool $validateDocumentName;
    private bool $writeMetadata;
    private ?string $useFetchDepth = null;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
        $this->session = $dm->getPhpcrSession();
        $this->eventManager = $dm->getEventManager();
        $this->eventListenersInvoker = new ListenersInvoker($this->eventManager);

        $config = $dm->getConfiguration();
        $this->documentClassMapper = $config->getDocumentClassMapper();
        $this->validateDocumentName = $config->getValidateDoctrineMetadata();
        $this->writeMetadata = $config->getWriteDoctrineMetadata();
        $this->uuidGenerator = $config->getUuidGenerator();

        if ($this->session instanceof JackalopeSession) {
            $this->useFetchDepth = 'jackalope.fetch_depth';
        }
    }

    public function setPrefetchHelper(PrefetchHelper $helper): void
    {
        $this->prefetchHelper = $helper;
    }

    public function getPrefetchHelper(): PrefetchHelper
    {
        if (!isset($this->prefetchHelper)) {
            $this->prefetchHelper = new PrefetchHelper();
        }

        return $this->prefetchHelper;
    }

    /**
     * Validate if a document is of the specified class, if the global setting
     * to validate is activated.
     *
     * @param string|null $className The class name $document must be
     *                               instanceof. Pass empty to not validate anything.
     *
     * @throws PHPCRException
     */
    public function validateClassName(object $document, ?string $className): void
    {
        if ($className && $this->validateDocumentName) {
            $this->documentClassMapper->validateClassName($this->dm, $document, $className);
        }
    }

    /**
     * Get the existing document or proxy of the specified class and node data
     * or create a new one if not existing.
     *
     * Supported hints are
     * - refresh: reload the fields from the database if set
     * - locale: use this locale instead of the one from the annotation or the default
     * - fallback: whether to try other languages or throw a not found
     *      exception if the desired locale is not found. defaults to true if
     *      not set and locale is not given either.
     * - prefetch: if set to false, do not attempt to prefetch related data.
     *      (This makes sense when the caller already did this beforehand.)
     *
     * @throws PHPCRExceptionInterface if $className was specified and does not match
     *                                 the class of the document corresponding to $node
     */
    public function getOrCreateDocument(?string $className, NodeInterface $node, array &$hints = []): ?object
    {
        $documents = $this->getOrCreateDocuments($className, [$node], $hints);

        return array_shift($documents);
    }

    /**
     * Get the existing document or proxy of the specified class and node data
     * or create a new one if not existing.
     *
     * Supported hints are
     * - refresh: reload the fields from the database if set
     * - locale: use this locale instead of the one from the annotation or the default
     * - fallback: whether to try other languages or throw a not found
     *      exception if the desired locale is not found. defaults to true if
     *      not set and locale is not given either.
     * - prefetch: if set to false, do not attempt to prefetch related data.
     *      (This makes sense when the caller already did this beforehand.)
     *
     * @param \Iterator|array $nodes
     *
     * @throws Exception\InvalidArgumentException
     * @throws PHPCRException
     */
    public function getOrCreateDocuments(?string $className, iterable $nodes, array $hints = []): array
    {
        $refresh = $hints['refresh'] ?? false;
        $locale = $hints['locale'] ?? null;
        $fallback = $hints['fallback'] ?? null !== $locale;
        $documents = [];
        $overrideLocalValuesOids = [];
        $strategies = [];
        $nodesByStrategy = [];
        $allLocales = [];

        // prepare array of document ordered by the nodes path
        $existingDocuments = 0;
        foreach ($nodes as $node) {
            $requestedClassName = $className;

            try {
                $actualClassName = $this->documentClassMapper->getClassName($this->dm, $node, $className);
            } catch (ClassMismatchException $e) {
                // ignore class mismatch, just skip that one
                continue;
            }

            $id = $node->getPath();
            $class = $this->dm->getClassMetadata($actualClassName);

            // prepare first, add later when fine
            $document = $this->getDocumentById($id);

            if ($document) {
                if (!$refresh) {
                    ++$existingDocuments;
                    $documents[$id] = $document;
                } else {
                    $overrideLocalValuesOids[$id] = \spl_object_hash($document);
                }

                try {
                    $this->validateClassName($document, $requestedClassName);
                } catch (ClassMismatchException $e) {
                    continue;
                }
            } else {
                $document = $class->newInstance();
                // delay registering the new document until children proxy have been created
                $overrideLocalValuesOids[$id] = false;
            }

            $documents[$id] = $document;

            if ($this->isDocumentTranslatable($class)) {
                $currentStrategy = $this->dm->getTranslationStrategy($class->translator);

                $localesToTry = $this->dm->getLocaleChooserStrategy()->getFallbackLocales(
                    $document,
                    $class,
                    $locale
                );

                foreach ($localesToTry as $localeToTry) {
                    $allLocales[$localeToTry] = $localeToTry;
                }

                $strategies[$class->name] = $currentStrategy;
                $nodesByStrategy[$class->name][] = $node;
            }
        }

        foreach ($nodesByStrategy as $strategyClass => $nodesForLocale) {
            if (!$strategies[$strategyClass] instanceof TranslationNodesWarmer) {
                continue;
            }

            $strategies[$strategyClass]->getTranslationsForNodes($nodesForLocale, $allLocales, $this->session);
        }

        // return early
        if (count($documents) === $existingDocuments) {
            return $documents;
        }

        foreach ($nodes as $node) {
            $id = $node->getPath();
            $document = $this->getDocumentById($id) ?: ($documents[$id] ?? null);

            if (!$document) {
                continue;
            }

            $documents[$id] = $document;
            $class = $this->dm->getClassMetadata(get_class($document));

            $documentState = [];
            $nonMappedData = [];

            // second param is false to get uuid rather than dereference reference properties to node instances
            $properties = $node->getPropertiesValues(null, false);

            foreach ($class->fieldMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if (array_key_exists($mapping['property'], $properties)) {
                    if (true === $mapping['multivalue']) {
                        if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                            $documentState[$fieldName] = $this->createAssoc($properties, $mapping);
                        } else {
                            $documentState[$fieldName] = (array) $properties[$mapping['property']];
                        }
                    } else {
                        $documentState[$fieldName] = $properties[$mapping['property']];
                    }
                } elseif (true === $mapping['multivalue']) {
                    $documentState[$mapping['property']] = [];
                }
            }

            if ($class->node) {
                $documentState[$class->node] = $node;
            }

            if ($class->nodename) {
                $documentState[$class->nodename] = $node->getName();
            }

            if ($class->identifier) {
                $documentState[$class->identifier] = $node->getPath();
            }

            if (!array_key_exists('prefetch', $hints) || $hints['prefetch']) {
                $this->getPrefetchHelper()->prefetchReferences($class, $node);
            }

            // initialize inverse side collections
            foreach ($class->referenceMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                    if (!$node->hasProperty($mapping['property'])) {
                        continue;
                    }

                    try {
                        $referencedNode = $node->getProperty($mapping['property'])->getNode();
                        $proxy = $this->getOrCreateProxyFromNode($referencedNode, $locale);
                        if (array_key_exists('targetDocument', $mapping) && !$proxy instanceof $mapping['targetDocument']) {
                            throw new PHPCRException("Unexpected class for referenced document at '{$referencedNode->getPath()}'. Expected '{$mapping['targetDocument']}' but got '".ClassUtils::getClass($proxy)."'.");
                        }
                    } catch (RepositoryException $e) {
                        if ($e instanceof ItemNotFoundException || array_key_exists('ignoreHardReferenceNotFound', $hints)) {
                            // a weak reference or an old version can have lost references
                            $proxy = null;
                        } else {
                            throw new PHPCRException($e->getMessage(), 0, $e);
                        }
                    }

                    $documentState[$fieldName] = $proxy;
                } elseif (ClassMetadata::MANY_TO_MANY === $mapping['type']) {
                    $referencedNodes = [];
                    if ($node->hasProperty($mapping['property'])) {
                        foreach ($node->getProperty($mapping['property'])->getString() as $reference) {
                            $referencedNodes[] = $reference;
                        }
                    }

                    $targetDocument = $mapping['targetDocument'] ?? null;
                    $coll = new ReferenceManyCollection(
                        $this->dm,
                        $document,
                        $mapping['property'],
                        $referencedNodes,
                        $targetDocument,
                        $locale,
                        $this->getReferenceManyCollectionTypeFromMetadata($mapping)
                    );
                    $documentState[$fieldName] = $coll;
                }
            }

            if (!array_key_exists('prefetch', $hints) || $hints['prefetch']) {
                if ($class->translator) {
                    try {
                        $prefetchLocale = $locale ?: $this->dm->getLocaleChooserStrategy()->getLocale();
                    } catch (InvalidArgumentException $e) {
                        throw new InvalidArgumentException($e->getMessage().' but document '.$class->name.' is mapped with translations.');
                    }
                } else {
                    $prefetchLocale = null;
                }

                $this->getPrefetchHelper()->prefetchHierarchy($class, $node, $prefetchLocale);
            }

            if ($class->parentMapping && $node->getDepth() > 0) {
                // do not map parent to self if we are at root
                $documentState[$class->parentMapping] = $this->getOrCreateProxyFromNode($node->getParent(), $locale);
            }

            if ($class->depthMapping) {
                $documentState[$class->depthMapping] = $node->getDepth();
            }

            foreach ($class->childMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                $documentState[$fieldName] = $node->hasNode($mapping['nodeName'])
                    ? $this->getOrCreateProxyFromNode($node->getNode($mapping['nodeName']), $locale)
                    : null;
            }

            foreach ($class->childrenMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                $documentState[$fieldName] = new ChildrenCollection($this->dm, $document, $mapping['filter'], $mapping['fetchDepth'], $locale);
            }

            foreach ($class->referrersMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                // get the reference type strategy (weak or hard) on the fly, as we
                // can not do it in ClassMetadata
                $referringMeta = $this->dm->getClassMetadata($mapping['referringDocument']);
                $referringField = $referringMeta->mappings[$mapping['referencedBy']];
                $documentState[$fieldName] = new ReferrersCollection(
                    $this->dm,
                    $document,
                    $referringField['strategy'],
                    $referringField['property'],
                    $locale,
                    $mapping['referringDocument']
                );
            }
            foreach ($class->mixedReferrersMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                $documentState[$fieldName] = new ImmutableReferrersCollection(
                    $this->dm,
                    $document,
                    $mapping['referenceType'],
                    $locale
                );
            }

            // when not set then not needed
            if (!array_key_exists($id, $overrideLocalValuesOids)) {
                continue;
            }

            if (!$overrideLocalValuesOids[$id]) {
                // registering the document needs to be delayed until the children proxies where created
                $overrideLocalValuesOids[$id] = $this->registerDocument($document, $id);
            }

            $this->nonMappedData[$overrideLocalValuesOids[$id]] = $nonMappedData;
            foreach ($class->reflFields as $fieldName => $reflFields) {
                $value = $documentState[$fieldName] ?? null;
                $reflFields->setValue($document, $value);
                $this->originalData[$overrideLocalValuesOids[$id]][$fieldName] = $value;
            }

            // Load translations
            $this->doLoadTranslation($document, $class, $locale, $fallback, $refresh);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postLoad)) {
                $this->eventListenersInvoker->invoke(
                    $class,
                    Event::postLoad,
                    $document,
                    new LifecycleEventArgs($document, $this->dm),
                    $invoke
                );
            }
        }

        return $documents;
    }

    /**
     * Get the existing document or proxy or create a new one for this PHPCR Node.
     */
    public function getOrCreateProxyFromNode(NodeInterface $node, string $locale = null): object
    {
        $targetId = $node->getPath();
        $className = $this->documentClassMapper->getClassName($this->dm, $node);

        return $this->getOrCreateProxy($targetId, $className, $locale);
    }

    /**
     * Get the existing document or proxy for this id of this class, or create
     * a new one.
     */
    public function getOrCreateProxy(string $targetId, string $className, string $locale = null): object
    {
        $document = $this->getDocumentById($targetId);

        // check if referenced document already exists
        if ($document) {
            $metadata = $this->dm->getClassMetadata($className);
            if ($locale && $locale !== $this->getCurrentLocale($document, $metadata)) {
                $this->doLoadTranslation($document, $metadata, $locale, true);
            }

            return $document;
        }

        $metadata = $this->dm->getClassMetadata($className);
        $proxyDocument = $this->dm->getProxyFactory()->getProxy($className, [$metadata->identifier => $targetId]);

        // register the document under its own id
        $this->registerDocument($proxyDocument, $targetId);

        if ($locale) {
            $this->setLocale($proxyDocument, $this->dm->getClassMetadata($className), $locale);
        }

        return $proxyDocument;
    }

    /**
     * Populate the proxy with actual data.
     */
    public function refreshDocumentForProxy(string $className, Proxy $document): void
    {
        $node = $this->session->getNode($this->determineDocumentId($document));

        $hints = ['refresh' => true, 'fallback' => true];

        $oid = \spl_object_hash($document);
        if (array_key_exists($oid, $this->documentLocales) && array_key_exists('current', $this->documentLocales[$oid])) {
            $hints['locale'] = $this->documentLocales[$oid]['current'];
        }

        $this->getOrCreateDocument($className, $node, $hints);
    }

    /**
     * Bind the translatable fields of the document in the specified locale.
     *
     * This method will update the field mapped to Locale if it does not match the $locale argument.
     *
     * @param object $document the document to persist a translation of
     * @param string $locale   the locale this document currently has
     *
     * @throws PHPCRException if the document is not translatable
     */
    public function bindTranslation(object $document, string $locale): void
    {
        $state = $this->getDocumentState($document);
        if (self::STATE_MANAGED !== $state) {
            throw new InvalidArgumentException('Document has to be managed to be able to bind a translation '.self::objToStr($document, $this->dm));
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($class)) {
            throw new PHPCRException('This document is not translatable, do not use bindTranslation: '.self::objToStr($document, $this->dm));
        }

        if ($this->getCurrentLocale($document) != $locale
            && false !== array_search($locale, $this->getLocalesFor($document), true)
        ) {
            throw new RuntimeException(sprintf(
                'Translation "%s" already exists for "%s". First load this translation if you want to change it, or remove the existing translation.',
                $locale,
                self::objToStr($document, $this->dm)
            ));
        }

        $this->doBindTranslation($document, $locale, $class);
    }

    private function doBindTranslation(object $document, string $locale, ClassMetadata $class): void
    {
        $oid = \spl_object_hash($document);

        // only trigger the events if we bind a new translation
        if (empty($this->documentTranslations[$oid][$locale])
            && $invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preCreateTranslation)
        ) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::preCreateTranslation,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        } elseif ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preUpdateTranslation)) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::preUpdateTranslation,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        }

        $this->setLocale($document, $class, $locale);

        foreach ($class->translatableFields as $field) {
            $this->documentTranslations[$oid][$locale][$field] = $class->getFieldValue($document, $field);
        }
    }

    /**
     * Schedule insertion of this document and cascade if necessary.
     */
    public function scheduleInsert(object $document): void
    {
        $visited = [];
        $this->doScheduleInsert($document, $visited);
    }

    private function doScheduleInsert(object $document, array &$visited): void
    {
        if (!is_object($document)) {
            throw new PHPCRException(sprintf(
                'Expected a mapped object, found <%s>',
                gettype($document)
            ));
        }

        $oid = \spl_object_hash($document);
        // To avoid recursion loops (over children and parents)
        if (array_key_exists($oid, $visited)) {
            return;
        }
        $visited[$oid] = true;

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isMappedSuperclass) {
            throw new InvalidArgumentException('Cannot persist a mapped super class instance: '.$class->name);
        }

        $this->cascadeScheduleParentInsert($class, $document, $visited);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                $this->persistNew($class, $document);

                break;
            case self::STATE_MANAGED:
                // TODO: Change Tracking Deferred Explicit
                break;
            case self::STATE_REMOVED:
                unset($this->scheduledRemovals[$oid]);
                $this->setDocumentState($oid, self::STATE_MANAGED);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('Detached document or new document with already existing id passed to persist(): '.self::objToStr($document, $this->dm));
            case self::STATE_FROZEN:
                throw new InvalidArgumentException('Document versions cannot be persisted: '.self::objToStr($document, $this->dm));
        }

        $this->cascadeScheduleInsert($class, $document, $visited);
    }

    private function cascadeScheduleInsert(ClassMetadata $class, object $document, array &$visited): void
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST)) {
                continue;
            }

            $related = $class->getFieldValue($document, $fieldName);
            if (null !== $related) {
                if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                    if (is_array($related) || $related instanceof Collection) {
                        throw new PHPCRException(sprintf(
                            'Referenced document is not stored correctly in a reference-one property. Do not use array notation or a (ReferenceMany)Collection in field "%s" of document "%s"',
                            $fieldName,
                            self::objToStr($document, $this->dm)
                        ));
                    }
                    if (!is_object($related)) {
                        throw new PHPCRException(sprintf(
                            'A reference field may only contain mapped documents, found <%s> in field "%s" of "%s"',
                            gettype($related),
                            $fieldName,
                            self::objToStr($document, $this->dm)
                        ));
                    }

                    if (self::STATE_NEW === $this->getDocumentState($related)) {
                        $this->doScheduleInsert($related, $visited);
                    }
                } else {
                    if (!is_array($related) && !$related instanceof Collection) {
                        throw new PHPCRException('Referenced documents are not stored correctly in a reference-many property. Use array notation or a (ReferenceMany)Collection: '.self::objToStr($document, $this->dm));
                    }
                    foreach ($related as $relatedDocument) {
                        if (!$relatedDocument) {
                            continue;
                        }
                        if (!is_object($relatedDocument)) {
                            throw new PHPCRException(sprintf(
                                'A reference field may only contain mapped documents, found <%s> in field "%s" of "%s"',
                                gettype($relatedDocument),
                                $fieldName,
                                self::objToStr($document, $this->dm)
                            ));
                        }
                        if (self::STATE_NEW === $this->getDocumentState($relatedDocument)) {
                            $this->doScheduleInsert($relatedDocument, $visited);
                        }
                    }
                }
            }
        }
    }

    private function cascadeScheduleParentInsert(ClassMetadata $class, object $document, array &$visited): void
    {
        if ($class->parentMapping) {
            $parent = $class->getFieldValue($document, $class->parentMapping);
            if (null !== $parent && self::STATE_NEW === $this->getDocumentState($parent)) {
                if (!is_object($parent)) {
                    throw new PHPCRException(sprintf(
                        'A parent field may only contain mapped documents, found <%s> in field "%s" of "%s"',
                        gettype($parent),
                        $class->parentMapping,
                        self::objToStr($document, $this->dm)
                    ));
                }

                $this->doScheduleInsert($parent, $visited);
            }
        }
    }

    private function getIdGenerator(int $type): IdGenerator
    {
        if (!array_key_exists($type, $this->idGenerators)) {
            $this->idGenerators[$type] = IdGenerator::create($type);
        }

        return $this->idGenerators[$type];
    }

    public function scheduleMove(object $document, string $targetPath): void
    {
        $oid = \spl_object_hash($document);

        $state = $this->getDocumentState($document);

        switch ($state) {
            case self::STATE_NEW:
                unset($this->scheduledInserts[$oid]);

                break;
            case self::STATE_REMOVED:
                unset($this->scheduledRemovals[$oid]);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('Detached document passed to move(): '.self::objToStr($document, $this->dm));
        }

        $this->scheduledMoves[$oid] = [$document, $targetPath];
        $this->setDocumentState($oid, self::STATE_MANAGED);
    }

    public function scheduleReorder(object $document, string $srcName, string $targetName, bool $before): void
    {
        $oid = \spl_object_hash($document);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_REMOVED:
                throw new InvalidArgumentException('Removed document passed to reorder(): '.self::objToStr($document, $this->dm));
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('Detached document passed to reorder(): '.self::objToStr($document, $this->dm));
        }

        if (!array_key_exists($oid, $this->scheduledReorders)) {
            $this->scheduledReorders[$oid] = [];
        }
        $this->scheduledReorders[$oid][] = [$document, $srcName, $targetName, $before];
    }

    public function scheduleRemove(object $document): void
    {
        $visited = [];
        $this->doRemove($document, $visited);
    }

    private function doRemove(object $document, array &$visited): void
    {
        $oid = \spl_object_hash($document);
        if (array_key_exists($oid, $visited)) {
            return;
        }
        $visited[$oid] = true;

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                unset($this->scheduledInserts[$oid]);

                break;
            case self::STATE_MANAGED:
                unset($this->scheduledMoves[$oid], $this->scheduledReorders[$oid]);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('Detached document passed to remove(): '.self::objToStr($document, $this->dm));
        }

        $this->scheduledRemovals[$oid] = $document;
        $this->setDocumentState($oid, self::STATE_REMOVED);

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preRemove)) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::preRemove,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        }

        $this->cascadeRemove($class, $document, $visited);
    }

    private function cascadeRemove(ClassMetadata $class, object $document, array &$visited): void
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_REMOVE)) {
                continue;
            }

            $related = $class->getFieldValue($document, $fieldName);
            if ($related instanceof Collection || is_array($related)) {
                // If its a PersistentCollection initialization is intended! No unwrap!
                foreach ($related as $relatedDocument) {
                    if (null !== $relatedDocument) {
                        $this->doRemove($relatedDocument, $visited);
                    }
                }
            } elseif (null !== $related) {
                $this->doRemove($related, $visited);
            }
        }

        // remove is cascaded to children automatically on PHPCR level
    }

    /**
     * recurse over all known child documents to remove them form this unit of work
     * as their parent gets removed from phpcr. If you do not, flush will try to create
     * orphaned nodes if these documents are modified which leads to a PHPCR exception.
     */
    private function purgeChildren(object $document): void
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->childMappings as $fieldName) {
            $child = $class->getFieldValue($document, $fieldName);
            if (null !== $child) {
                $this->purgeChildren($child);
                $this->unregisterDocument($child);
            }
        }
    }

    /**
     * @param string $oid document object hash
     */
    private function setDocumentState(string $oid, int $state): void
    {
        $this->documentState[$oid] = $state;
    }

    /**
     * @return int one of the STATE_* constants of this class
     */
    public function getDocumentState(object $document): int
    {
        $oid = \spl_object_hash($document);
        if (!array_key_exists($oid, $this->documentState)) {
            // this will only use the metadata if id is mapped
            $id = $this->determineDocumentId($document);

            if (!$id) {
                return self::STATE_NEW;
            }

            if ($this->getDocumentById($id)) {
                return self::STATE_DETACHED;
            }

            return $this->dm->getPhpcrSession()->nodeExists($id)
                ? self::STATE_DETACHED : self::STATE_NEW;
        }

        return $this->documentState[$oid];
    }

    public function isScheduledForInsert(object $document): bool
    {
        return array_key_exists(\spl_object_hash($document), $this->scheduledInserts);
    }

    /**
     * Detects the changes for a single document.
     */
    public function computeSingleDocumentChangeSet(object $document): void
    {
        $state = $this->getDocumentState($document);

        if (self::STATE_MANAGED !== $state && self::STATE_REMOVED !== $state) {
            throw new InvalidArgumentException('Document has to be managed for single computation '.self::objToStr($document, $this->dm));
        }

        foreach ($this->scheduledInserts as $insertedDocument) {
            $class = $this->dm->getClassMetadata(get_class($insertedDocument));
            $this->computeChangeSet($class, $insertedDocument);
        }

        // Ignore uninitialized proxy objects
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $oid = \spl_object_hash($document);
        if (!array_key_exists($oid, $this->scheduledInserts)) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Detects the changes that need to be persisted.
     */
    public function computeChangeSets(): void
    {
        foreach ($this->identityMap as $document) {
            $state = $this->getDocumentState($document);
            if (self::STATE_MANAGED === $state) {
                $class = $this->dm->getClassMetadata(get_class($document));
                $this->computeChangeSet($class, $document);
            }
        }
    }

    /**
     * Get a documents actual data, flattening all the objects to arrays.
     */
    private function getDocumentActualData(ClassMetadata $class, object $document): array
    {
        $actualData = [];
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            // do not set the version info fields if they have values, they are not to be managed by the user in write scenarios.
            if ($fieldName === $class->versionNameField
                || $fieldName === $class->versionCreatedField
            ) {
                continue;
            }
            $value = $reflProperty->isInitialized($document) ? $reflProperty->getValue($document) : null;
            $actualData[$fieldName] = $value;
        }

        return $actualData;
    }

    /**
     * Determine the nodename of a child in a children list.
     *
     * @param string $nodename name to use if we can't determine the node name otherwise
     *
     * @return mixed|string
     *
     * @throws PHPCRException
     */
    private function getChildNodename(string $parentId, string $nodename, object $child, object $parent)
    {
        $childClass = $this->dm->getClassMetadata(get_class($child));
        if ($childClass->nodename && $childNodeName = $childClass->getFieldValue($child, $childClass->nodename)) {
            if ($exception = $childClass->isValidNodename($childNodeName)) {
                throw IdException::illegalName($child, $childClass->nodename, $nodename, $exception);
            }

            return $childNodeName;
        }

        $childId = '';
        if ($childClass->identifier) {
            $childId = $childClass->getIdentifierValue($child);
        }
        if (!$childId) {
            $generator = $this->getIdGenerator($childClass->idGenerator);
            $childId = $generator->generate($child, $childClass, $this->dm, $parent);
        }

        if ('' !== $childId) {
            if ($childId !== $parentId.'/'.PathHelper::getNodeName($childId)) {
                throw PHPCRException::cannotMoveByAssignment(self::objToStr($child, $this->dm));
            }
            $nodename = PathHelper::getNodeName($childId);
        }

        return $nodename;
    }

    /**
     * @throws Exception\InvalidArgumentException
     * @throws PHPCRException
     */
    private function computeAssociationChanges(object $document, ClassMetadata $class, string $oid, bool $isNew, array $changeSet, string $assocType): void
    {
        switch ($assocType) {
            case 'reference':
                $mappings = $class->referenceMappings;
                $computeMethod = 'computeReferenceChanges';

                break;
            case 'referrer':
                $mappings = $class->referrersMappings;
                $computeMethod = 'computeReferrerChanges';

                break;
            default:
                throw new InvalidArgumentException('Unsupported association type used: '.$assocType);
        }

        foreach ($mappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];

            if ((ClassMetadata::MANY_TO_MANY === $mapping['type'] && 'reference' === $assocType)
                || ('referrers' === $mapping['type'] && 'referrer' === $assocType)
            ) {
                if ($changeSet[$fieldName] instanceof PersistentCollection) {
                    if (!$changeSet[$fieldName]->isInitialized()) {
                        continue;
                    }
                } else {
                    if (null === $changeSet[$fieldName]) {
                        $changeSet[$fieldName] = [];
                    }

                    if (!is_array($changeSet[$fieldName]) && !$changeSet[$fieldName] instanceof Collection) {
                        throw PHPCRException::associationFieldNoArray(
                            self::objToStr($document, $this->dm),
                            $fieldName
                        );
                    }

                    // convert to a PersistentCollection
                    switch ($assocType) {
                        case 'reference':
                            $targetDocument = $mapping['targetDocument'] ?? null;
                            $changeSet[$fieldName] = ReferenceManyCollection::createFromCollection(
                                $this->dm,
                                $document,
                                $mapping['property'],
                                $changeSet[$fieldName],
                                $targetDocument,
                                !$isNew
                            );

                            break;
                        case 'referrer':
                            $referringMeta = $this->dm->getClassMetadata($mapping['referringDocument']);
                            $referringField = $referringMeta->mappings[$mapping['referencedBy']];

                            $changeSet[$fieldName] = ReferrersCollection::createFromCollection(
                                $this->dm,
                                $document,
                                $changeSet[$fieldName],
                                $referringField['strategy'],
                                $referringField['property'],
                                $mapping['referringDocument'],
                                !$isNew
                            );

                            break;
                    }

                    $class->setFieldValue($document, $fieldName, $changeSet[$fieldName]);
                    $this->originalData[$oid][$fieldName] = $changeSet[$fieldName];
                }

                $coid = \spl_object_hash($changeSet[$fieldName]);
                $this->visitedCollections[$coid] = $changeSet[$fieldName];

                foreach ($changeSet[$fieldName] as $association) {
                    if (null !== $association) {
                        $this->$computeMethod($mapping, $association);
                    }
                }

                if (!$isNew && $mapping['cascade'] & ClassMetadata::CASCADE_REMOVE) {
                    if (!$this->originalData[$oid][$fieldName] instanceof PersistentCollection) {
                        throw new RuntimeException('OriginalData for a collection association contains something else than a PersistentCollection.');
                    }

                    \assert($this->originalData[$oid][$fieldName] instanceof ReferrersCollection || $this->originalData[$oid][$fieldName] instanceof ReferenceManyCollection);
                    $associations = $this->originalData[$oid][$fieldName]->getOriginalPaths();
                    foreach ($associations as $association) {
                        $association = $this->getDocumentById($association);
                        if ($association && !$this->originalData[$oid][$fieldName]->contains($association)) {
                            $this->scheduleRemove($association);
                        }
                    }
                }
            } elseif ($changeSet[$fieldName] && ClassMetadata::MANY_TO_ONE === $mapping['type'] && 'reference' === $assocType) {
                $this->computeReferenceChanges($mapping, $changeSet[$fieldName]);
            }
        }
    }

    private function computeChildrenChanges(object $document, ClassMetadata $class, string $oid, bool $isNew, array $changeSet): void
    {
        $id = $this->getDocumentId($document, false);

        foreach ($class->childrenMappings as $fieldName) {
            if ($changeSet[$fieldName] instanceof PersistentCollection) {
                if (!$changeSet[$fieldName]->isInitialized()) {
                    continue;
                }
            } else {
                if (null === $changeSet[$fieldName]) {
                    $changeSet[$fieldName] = [];
                }

                if (!is_array($changeSet[$fieldName]) && !$changeSet[$fieldName] instanceof Collection) {
                    throw PHPCRException::childrenFieldNoArray(
                        self::objToStr($document, $this->dm),
                        $fieldName
                    );
                }

                $filter = $mapping['filter'] ?? null;
                $fetchDepth = $mapping['fetchDepth'] ?? -1;

                // convert to a PersistentCollection
                $changeSet[$fieldName] = ChildrenCollection::createFromCollection(
                    $this->dm,
                    $document,
                    $changeSet[$fieldName],
                    $filter,
                    $fetchDepth,
                    !$isNew
                );

                $class->setFieldValue($document, $fieldName, $changeSet[$fieldName]);
                $this->originalData[$oid][$fieldName] = $changeSet[$fieldName];
            }

            $mapping = $class->mappings[$fieldName];
            $childNames = $movedChildNames = [];

            $coid = \spl_object_hash($changeSet[$fieldName]);
            $this->visitedCollections[$coid] = $changeSet[$fieldName];

            foreach ($changeSet[$fieldName] as $originalNodename => $child) {
                if (!is_object($child)) {
                    throw PHPCRException::childrenContainsNonObject(
                        self::objToStr($document, $this->dm),
                        $fieldName,
                        gettype($child)
                    );
                }

                $nodename = $this->getChildNodename($id, $originalNodename, $child, $document);
                $changeSet[$fieldName][$nodename] = $this->computeChildChanges($mapping, $child, $id, $nodename, $document);
                if (0 !== strcmp($originalNodename, $nodename)) {
                    unset($changeSet[$fieldName][$originalNodename]);
                    $movedChildNames[] = (string) $originalNodename;
                }
                $childNames[] = $nodename;
            }

            if ($isNew) {
                continue;
            }

            if (!$this->originalData[$oid][$fieldName] instanceof ChildrenCollection) {
                throw new RuntimeException('OriginalData for a children association contains something else than a ChildrenCollection.');
            }

            $this->originalData[$oid][$fieldName]->initialize();
            $originalNames = $this->originalData[$oid][$fieldName]->getOriginalNodenames();
            foreach ($originalNames as $key => $childName) {
                // check moved children to not accidentally remove a child that simply moved away.
                if (!(in_array($childName, $childNames, true) || in_array($childName, $movedChildNames, true))) {
                    $child = $this->getDocumentById($id.'/'.$childName);
                    // make sure that when the child move is already processed and another compute is triggered
                    // we don't remove that child
                    $childOid = \spl_object_hash($child);
                    if (!array_key_exists($childOid, $this->scheduledMoves)) {
                        $this->scheduleRemove($child);
                        unset($originalNames[$key]);
                    }
                }
            }

            if (count($childNames) > 0) {
                // reindex the arrays to avoid holes in the indexes
                $originalNames = array_values($originalNames);
                $originalNames = array_merge($originalNames, array_diff($childNames, $originalNames));
                if ($originalNames != $childNames) {
                    $reordering = NodeHelper::calculateOrderBefore($originalNames, $childNames);
                    if (empty($this->documentChangesets[$oid])) {
                        $this->documentChangesets[$oid] = ['reorderings' => [$reordering]];
                    } else {
                        $this->documentChangesets[$oid]['reorderings'][] = $reordering;
                    }

                    $this->scheduledUpdates[$oid] = $document;
                } elseif (empty($this->documentChangesets[$oid]['fields'])) {
                    unset($this->documentChangesets[$oid], $this->scheduledUpdates[$oid]);
                } else {
                    $this->documentChangesets[$oid]['reorderings'] = [];
                }
            }
        }
    }

    public function computeChangeSet(ClassMetadata $class, object $document): void
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $oid = \spl_object_hash($document);
        if (in_array($oid, $this->changesetComputed, true)) {
            return;
        }

        $this->changesetComputed[] = $oid;

        $changeSet = $actualData = $this->getDocumentActualData($class, $document);
        $id = $this->getDocumentId($document, false);
        $isNew = !array_key_exists($oid, $this->originalData);

        if ($isNew) {
            // Document is New and should be inserted
            $this->originalData[$oid] = $changeSet;
        } elseif (!empty($this->documentChangesets[$oid]['fields'])) {
            foreach ($this->documentChangesets[$oid]['fields'] as $fieldName => $data) {
                $this->originalData[$oid][$fieldName] = $data[0];
            }
        }

        if ($class->parentMapping
            && array_key_exists($class->parentMapping, $changeSet)
            && null !== $parent = $changeSet[$class->parentMapping]
        ) {
            $parentClass = $this->dm->getClassMetadata(get_class($parent));
            $state = $this->getDocumentState($parent);

            if (self::STATE_MANAGED === $state) {
                $this->computeChangeSet($parentClass, $parent);
            }
        }

        foreach ($class->childMappings as $fieldName) {
            if ($changeSet[$fieldName]) {
                if (is_array($changeSet[$fieldName]) || $changeSet[$fieldName] instanceof Collection) {
                    throw PHPCRException::childFieldIsArray(
                        self::objToStr($document, $this->dm),
                        $fieldName
                    );
                }

                if (!is_object($changeSet[$fieldName])) {
                    throw PHPCRException::childFieldNoObject(
                        self::objToStr($document, $this->dm),
                        $fieldName,
                        gettype($changeSet[$fieldName])
                    );
                }

                $mapping = $class->mappings[$fieldName];
                $changeSet[$fieldName] = $this->computeChildChanges($mapping, $changeSet[$fieldName], $id, $mapping['nodeName']);
            }
        }

        $this->computeAssociationChanges($document, $class, $oid, $isNew, $changeSet, 'reference');
        $this->computeAssociationChanges($document, $class, $oid, $isNew, $changeSet, 'referrer');

        foreach ($class->mixedReferrersMappings as $fieldName) {
            if ($changeSet[$fieldName]
                && $changeSet[$fieldName] instanceof PersistentCollection
                && $changeSet[$fieldName]->isDirty()
            ) {
                throw new PHPCRException("The immutable mixed referrer collection in field $fieldName is dirty");
            }
        }

        $this->computeChildrenChanges($document, $class, $oid, $isNew, $changeSet);

        if (!$isNew) {
            // collect assignment move operations
            $destPath = $destName = false;

            if (array_key_exists($oid, $this->originalData)
                && array_key_exists($class->parentMapping, $this->originalData[$oid])
                && array_key_exists($class->parentMapping, $changeSet)
                && $this->originalData[$oid][$class->parentMapping] !== $changeSet[$class->parentMapping]
            ) {
                $destPath = $this->getDocumentId($changeSet[$class->parentMapping]);
            }

            if (array_key_exists($oid, $this->originalData)
                && array_key_exists($class->nodename, $this->originalData[$oid])
                && array_key_exists($class->nodename, $changeSet)
                && $this->originalData[$oid][$class->nodename] !== $changeSet[$class->nodename]
            ) {
                $destName = $changeSet[$class->nodename];
            }

            // there was assignment move
            if ($destPath || $destName) {
                // add the other field if only one was changed
                if (false === $destPath) {
                    $destPath = array_key_exists($class->parentMapping, $changeSet)
                        ? $this->getDocumentId($changeSet[$class->parentMapping])
                        : PathHelper::getParentPath($this->getDocumentId($document));
                }
                if (false === $destName) {
                    $destName = null !== $class->nodename && $changeSet[$class->nodename]
                        ? $changeSet[$class->nodename]
                        : PathHelper::getNodeName($this->getDocumentId($document));
                }

                // make sure destination nodename is okay
                if ($exception = $class->isValidNodename($destName)) {
                    throw IdException::illegalName($document, $class->nodename, $destName);
                }

                // prevent path from becoming "//foobar" when moving to root node.
                $targetPath = ('/' === $destPath) ? "/$destName" : "$destPath/$destName";

                $this->scheduleMove($document, $targetPath);
            }

            if (array_key_exists($oid, $this->originalData)
                && array_key_exists($class->identifier, $this->originalData[$oid])
                && array_key_exists($class->identifier, $changeSet)
                && $this->originalData[$oid][$class->identifier] !== $changeSet[$class->identifier]
            ) {
                throw new PHPCRException('The Id is immutable ('.$this->originalData[$oid][$class->identifier].' !== '.$changeSet[$class->identifier].'). Please use DocumentManager::move to move the document: '.self::objToStr($document, $this->dm));
            }
        }

        $fields = array_intersect_key($changeSet, $class->mappings);

        if ($this->isDocumentTranslatable($class)) {
            $locale = $this->getCurrentLocale($document, $class);

            // ensure we do not bind a previously removed translation
            if (!$this->isTranslationRemoved($document, $locale)) {
                $this->doBindTranslation($document, $locale, $class);
            }
        }

        if ($isNew) {
            $this->documentChangesets[$oid]['fields'] = $fields;
            $this->scheduledInserts[$oid] = $document;

            return;
        }

        $translationChanges = false;
        if ($this->isDocumentTranslatable($class)) {
            $oid = \spl_object_hash($document);
            if (array_key_exists($oid, $this->documentTranslations)) {
                foreach ($this->documentTranslations[$oid] as $localeToCheck => $data) {
                    // a translation was removed
                    if (empty($data)) {
                        $translationChanges = true;

                        break;
                    }
                    // a translation was added
                    if (empty($this->originalTranslatedData[$oid][$localeToCheck])) {
                        $translationChanges = true;

                        break;
                    }
                    // a translation was changed
                    foreach ($data as $fieldName => $fieldValue) {
                        if ($this->originalTranslatedData[$oid][$localeToCheck][$fieldName] !== $fieldValue) {
                            $translationChanges = true;

                            break;
                        }
                    }
                }
            }

            // ensure that locale changes are not considered a change in the document
            if ($class->localeMapping && array_key_exists($class->localeMapping, $fields)) {
                unset($fields[$class->localeMapping]);
            }
        }

        foreach ($fields as $fieldName => $fieldValue) {
            $keepChange = false;
            if ($fieldValue instanceof ReferenceManyCollection || $fieldValue instanceof ReferrersCollection) {
                if ($fieldValue->changed()) {
                    $keepChange = true;
                }
            } elseif ($this->originalData[$oid][$fieldName] !== $fieldValue) {
                $keepChange = true;
            }

            if ($keepChange) {
                $fields[$fieldName] = [$this->originalData[$oid][$fieldName], $fieldValue];
            } else {
                unset($fields[$fieldName]);
            }
        }

        if (!empty($fields) || $translationChanges) {
            $this->documentChangesets[$oid]['fields'] = $fields;
            $this->originalData[$oid] = $actualData;
            $this->scheduledUpdates[$oid] = $document;
        } elseif (empty($this->documentChangesets[$oid]['reorderings'])) {
            unset($this->documentChangesets[$oid], $this->scheduledUpdates[$oid]);
        } else {
            $this->documentChangesets[$oid]['fields'] = [];
        }
    }

    /**
     * Computes the changes of a child.
     *
     * @param string $nodename the name of the node as specified by the mapping
     *
     * @return object the child instance (if we are replacing a child this can be a different instance than was originally provided)
     */
    private function computeChildChanges(array $mapping, object $child, string $parentId, string $nodename, object $parent = null): object
    {
        $targetClass = $this->dm->getClassMetadata(get_class($child));
        $state = $this->getDocumentState($child);

        switch ($state) {
            case self::STATE_NEW:
                // cascade persist is implicit on children, no check for cascading

                // check if we have conflicting nodename information on creation.
                if ($targetClass->nodename) {
                    $assignedName = $targetClass->getFieldValue($child, $targetClass->nodename);
                    if ($assignedName && $assignedName !== $nodename) {
                        throw IdException::conflictingChildName(
                            $parentId,
                            $mapping['fieldName'],
                            $nodename,
                            $child,
                            $assignedName
                        );
                    }
                }
                $childId = $parentId.'/'.$nodename;
                $targetClass->setIdentifierValue($child, $childId);

                if ($this->getDocumentById($childId)) {
                    $child = $this->merge($child);
                } else {
                    $this->persistNew($targetClass, $child, null, $parent);
                }

                $this->computeChangeSet($targetClass, $child);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('A detached document was found through a child relationship during cascading a persist operation: '.self::objToStr($child, $this->dm));
            default:
                if (PathHelper::getParentPath($this->getDocumentId($child)) !== $parentId) {
                    throw PHPCRException::cannotMoveByAssignment(self::objToStr($child, $this->dm));
                }
        }

        return $child;
    }

    private function computeReferenceChanges(array $mapping, object $reference): void
    {
        $targetClass = $this->dm->getClassMetadata(get_class($reference));
        $state = $this->getDocumentState($reference);

        switch ($state) {
            case self::STATE_NEW:
                if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST)) {
                    throw CascadeException::newDocumentFound(self::objToStr($reference));
                }
                $this->persistNew($targetClass, $reference);
                $this->computeChangeSet($targetClass, $reference);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('A detached document was found through a reference during cascading a persist operation: '.self::objToStr($reference, $this->dm));
        }
    }

    /**
     * This method is called by dynamic code.
     */
    private function computeReferrerChanges(array $mapping, object $referrer): void
    {
        $targetClass = $this->dm->getClassMetadata(get_class($referrer));
        $state = $this->getDocumentState($referrer);

        switch ($state) {
            case self::STATE_NEW:
                if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST)) {
                    throw CascadeException::newDocumentFound(self::objToStr($referrer));
                }
                $this->persistNew($targetClass, $referrer);
                $this->computeChangeSet($targetClass, $referrer);

                break;
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('A detached document was found through a referrer during cascading a persist operation: '.self::objToStr($referrer, $this->dm));
        }
    }

    /**
     * Persist new document, marking it managed and generating the id and the node.
     *
     * This method is either called through `DocumentManager#persist()` or during `DocumentManager#flush()`,
     * when persistence by reachability is applied.
     *
     * @param int|null $overrideIdGenerator type of the id generator if not the default
     */
    public function persistNew(ClassMetadata $class, object $document, int $overrideIdGenerator = null, object $parent = null): void
    {
        if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::prePersist)) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::prePersist,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        }

        $generator = $this->getIdGenerator($overrideIdGenerator ?: $class->idGenerator);
        $id = $generator->generate($document, $class, $this->dm, $parent);
        $this->registerDocument($document, $id);

        if (!$generator instanceof AssignedIdGenerator) {
            $class->setIdentifierValue($document, $id);
        }

        // If the UUID is mapped, generate it early resp. validate if already present.
        $uuidFieldName = $class->getUuidFieldName();
        if ($uuidFieldName) {
            $existingUuid = $class->getFieldValue($document, $uuidFieldName);
            if (!$existingUuid) {
                $class->setFieldValue($document, $uuidFieldName, $this->generateUuid());
            } elseif (!UUIDHelper::isUUID($existingUuid)) {
                throw RuntimeException::invalidUuid($id, ClassUtils::getClass($document), $existingUuid);
            }
        }
    }

    public function refresh(object $document): void
    {
        $this->session->refresh(true);
        $visited = [];
        $this->doRefresh($document, $visited);
    }

    private function doRefresh(object $document, array &$visited): void
    {
        $oid = \spl_object_hash($document);
        if (array_key_exists($oid, $visited)) {
            return;
        }
        $visited[$oid] = true;

        if (self::STATE_MANAGED !== $this->getDocumentState($document)) {
            throw new InvalidArgumentException('Document has to be managed to be refreshed '.self::objToStr($document, $this->dm));
        }

        $node = $this->session->getNode($this->getDocumentId($document));

        $class = $this->dm->getClassMetadata(get_class($document));
        $this->cascadeRefresh($class, $document, $visited);

        $hints = ['refresh' => true];
        $this->getOrCreateDocument(ClassUtils::getClass($document), $node, $hints);
    }

    public function merge(object $document): object
    {
        $visited = [];

        return $this->doMerge($document, $visited);
    }

    private function doMergeSingleDocumentProperty(object $managedCopy, ?object $document, \ReflectionProperty $prop, array $mapping): void
    {
        if (null === $document) {
            $prop->setValue($managedCopy, null);
        } elseif (!($mapping['cascade'] & ClassMetadata::CASCADE_MERGE)) {
            if (self::STATE_MANAGED === $this->getDocumentState($document)) {
                $prop->setValue($managedCopy, $document);
            } else {
                $targetClass = $this->dm->getClassMetadata(get_class($document));
                $id = $this->determineDocumentId($document, $targetClass);
                $proxy = $this->getOrCreateProxy($id, $targetClass->name);
                $prop->setValue($managedCopy, $proxy);
                $this->registerDocument($proxy, $id);
            }
        }
    }

    private function cascadeMergeCollection(Collection $managedCol, array $mapping): void
    {
        if (!$managedCol instanceof PersistentCollection
            || !($mapping['cascade'] & ClassMetadata::CASCADE_MERGE)
        ) {
            return;
        }

        $managedCol->initialize();
        if (!$managedCol->isEmpty()) {
            $managedCol->unwrap()->clear();
            $managedCol->setDirty(true);
        }
    }

    /**
     * @param array|null $assoc Information for association when necessary
     */
    private function doMerge(object $document, array &$visited, object $prevManagedCopy = null, array $assoc = null): object
    {
        $oid = \spl_object_hash($document);
        if (array_key_exists($oid, $visited)) {
            return $document; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));
        $locale = $this->getCurrentLocale($document, $class);

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED entities are ignored by the merge operation.
        if (self::STATE_MANAGED === $this->getDocumentState($document)) {
            $managedCopy = $document;
        } else {
            $id = $this->determineDocumentId($document, $class);
            $persist = false;

            if (!$id) {
                // document is new
                $managedCopy = $class->newInstance();
                $persist = true;
            } else {
                $managedCopy = $this->getDocumentById($id);
                if ($managedCopy) {
                    // We have the document in-memory already, just make sure its not removed.
                    if (self::STATE_REMOVED == $this->getDocumentState($managedCopy)) {
                        throw new InvalidArgumentException("Removed document detected during merge at '$id'. Cannot merge with a removed document.");
                    }

                    if (ClassUtils::getClass($managedCopy) != ClassUtils::getClass($document)) {
                        throw new InvalidArgumentException('Can not merge documents of different classes.');
                    }

                    if ($this->getCurrentLocale($managedCopy, $class) !== $locale) {
                        $this->doLoadTranslation($document, $class, $locale, true);
                    }
                } elseif ($locale) {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->dm->findTranslation($class->name, $id, $locale);
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->dm->find($class->name, $id);
                }

                if (null === $managedCopy) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed document was not found.
                    if (ClassMetadata::GENERATOR_TYPE_ASSIGNED !== $class->idGenerator) {
                        throw new InvalidArgumentException("Document not found in merge operation: $id");
                    }

                    $managedCopy = $class->newInstance();
                    $class->setIdentifierValue($managedCopy, $id);
                    $persist = true;
                }
            }

            $managedOid = \spl_object_hash($managedCopy);

            // Merge state of $document into existing (managed) document
            foreach ($class->reflFields as $fieldName => $prop) {
                $other = $prop->isInitialized($document) ? $prop->getValue($document) : null;
                if (($other instanceof PersistentCollection && !$other->isInitialized())
                    || ($other instanceof Proxy && !$other->__isInitialized())
                ) {
                    // do not merge fields marked lazy that have not been fetched.
                    // keep the lazy persistent collection of the managed copy.
                    continue;
                }

                $mapping = $class->mappings[$fieldName];
                if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                    $this->doMergeSingleDocumentProperty($managedCopy, $other, $prop, $mapping);
                } elseif (ClassMetadata::MANY_TO_MANY === $mapping['type']) {
                    $managedCol = $prop->getValue($managedCopy);
                    if (!$managedCol) {
                        $managedCol = new ReferenceManyCollection(
                            $this->dm,
                            $document,
                            $mapping['property'],
                            [],
                            $mapping['targetDocument'] ?? null,
                            $locale,
                            $this->getReferenceManyCollectionTypeFromMetadata($mapping)
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('child' === $mapping['type']) {
                    if (null !== $other) {
                        $this->doMergeSingleDocumentProperty($managedCopy, $other, $prop, $mapping);
                    }
                } elseif ('children' === $mapping['type']) {
                    $managedCol = $prop->getValue($managedCopy);
                    if (!$managedCol) {
                        $managedCol = new ChildrenCollection(
                            $this->dm,
                            $managedCopy,
                            $mapping['filter'],
                            $mapping['fetchDepth'],
                            $locale
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('referrers' === $mapping['type']) {
                    $managedCol = $prop->getValue($managedCopy);
                    if (!$managedCol) {
                        $referringMeta = $this->dm->getClassMetadata($mapping['referringDocument']);
                        $referringField = $referringMeta->mappings[$mapping['referencedBy']];
                        $managedCol = new ReferrersCollection(
                            $this->dm,
                            $managedCopy,
                            $referringField['strategy'],
                            $referringField['property'],
                            $locale
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('mixedreferrers' === $mapping['type']) {
                    $managedCol = $prop->getValue($managedCopy);
                    if (!$managedCol) {
                        $managedCol = new ImmutableReferrersCollection(
                            $this->dm,
                            $managedCopy,
                            $mapping['referenceType'],
                            $locale
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('parent' === $mapping['type']) {
                    $this->doMergeSingleDocumentProperty($managedCopy, $other, $prop, $mapping);
                } elseif (in_array($mapping['type'], ['locale', 'versionname', 'versioncreated', 'node', 'nodename'])) {
                    if (null !== $other) {
                        $prop->setValue($managedCopy, $other);
                    }
                } elseif (!$class->isIdentifier($fieldName)) {
                    $prop->setValue($managedCopy, $other);
                }
            }

            if ($persist) {
                $this->persistNew($class, $managedCopy);
            }

            // Mark the managed copy visited as well
            $visited[$managedOid] = true;
        }

        if (null !== $prevManagedCopy) {
            $prevClass = $this->dm->getClassMetadata(get_class($prevManagedCopy));
            if (ClassMetadata::MANY_TO_ONE === $assoc['type']) {
                $prevClass->reflFields[$assoc['fieldName']]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assoc['fieldName']]->getValue($prevManagedCopy)->add($managedCopy);
            }
        }

        $this->cascadeMerge($class, $document, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * Cascades a merge operation to associated entities.
     */
    private function cascadeMerge(ClassMetadata $class, object $document, object $managedCopy, array &$visited): void
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_MERGE)) {
                continue;
            }
            $related = $class->getFieldValue($document, $fieldName);
            if ($related instanceof Collection || is_array($related)) {
                if ($related instanceof PersistentCollection) {
                    // Unwrap so that foreach () does not initialize
                    $related = $related->unwrap();
                }
                foreach ($related as $relatedDocument) {
                    $this->doMerge($relatedDocument, $visited, $managedCopy, $mapping);
                }
            } elseif (null !== $related) {
                $this->doMerge($related, $visited, $managedCopy, $mapping);
            }
        }
    }

    /**
     * Detaches a document from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     */
    public function detach(object $document): void
    {
        $visited = [];
        $this->doDetach($document, $visited);
    }

    private function doDetach(object $document, array &$visited): void
    {
        $oid = \spl_object_hash($document);
        if (array_key_exists($oid, $visited)) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));
        $this->cascadeDetach($class, $document, $visited);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_MANAGED:
                $this->unregisterDocument($document);

                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }
    }

    private function cascadeRefresh(ClassMetadata $class, object $document, array &$visited): void
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_REFRESH)) {
                continue;
            }

            $related = $class->getFieldValue($document, $fieldName);
            if ($related instanceof Collection || is_array($related)) {
                if ($related instanceof PersistentCollection) {
                    // Unwrap so that foreach () does not initialize
                    $related = $related->unwrap();
                }
                foreach ($related as $relatedDocument) {
                    $this->doRefresh($relatedDocument, $visited);
                }
            } elseif (null !== $related) {
                $this->doRefresh($related, $visited);
            }
        }
    }

    /**
     * Cascades a detach operation to associated documents.
     */
    private function cascadeDetach(ClassMetadata $class, object $document, array &$visited): void
    {
        foreach ($class->childrenMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_DETACH)) {
                continue;
            }
            $related = $class->getFieldValue($document, $fieldName);
            if ($related instanceof Collection || is_array($related)) {
                foreach ($related as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } elseif (null !== $related) {
                $this->doDetach($related, $visited);
            }
        }

        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_DETACH)) {
                continue;
            }
            $related = $class->getFieldValue($document, $fieldName);
            if ($related instanceof Collection || is_array($related)) {
                foreach ($related as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } elseif (null !== $related) {
                $this->doDetach($related, $visited);
            }
        }

        // no cascade for mixed referrers
    }

    /**
     * Commits the UnitOfWork.
     *
     * @param object|array|null $document optionally limit to a specific
     *                                    document or an array of documents
     */
    public function commit($document = null): void
    {
        $this->invokeGlobalEvent(Event::preFlush, new ManagerEventArgs($this->dm));

        if (null === $document) {
            $this->computeChangeSets();
        } elseif (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } elseif (is_array($document)) {
            foreach ($document as $object) {
                $this->computeSingleDocumentChangeSet($object);
            }
        }

        $this->invokeGlobalEvent(Event::onFlush, new ManagerEventArgs($this->dm));

        if (empty($this->scheduledInserts)
            && empty($this->scheduledUpdates)
            && empty($this->scheduledRemovals)
            && empty($this->scheduledReorders)
            && empty($this->documentTranslations)
            && empty($this->scheduledMoves)
        ) {
            $this->invokeGlobalEvent(Event::postFlush, new ManagerEventArgs($this->dm));
            $this->changesetComputed = [];

            return; // Nothing to do.
        }

        try {
            $utx = $this->session->getWorkspace()->getTransactionManager();
            if ($utx->inTransaction()) {
                $utx = null;
            } else {
                $utx->begin();
            }
        } catch (UnsupportedRepositoryOperationException $e) {
            $utx = null;
        }

        try {
            $this->executeInserts($this->scheduledInserts);

            $this->executeUpdates($this->scheduledUpdates);

            $this->executeRemovals($this->scheduledRemovals);

            $this->executeReorders($this->scheduledReorders);

            $this->executeMoves($this->scheduledMoves);

            $this->session->save();

            if ($utx) {
                $utx->commit();
            }
        } catch (\Exception $e) {
            try {
                $this->dm->close();

                if ($utx) {
                    $utx->rollback();
                }
            } catch (\Exception $innerException) {
                // TODO: log error while closing dm after error: $innerException->getMessage
            }

            throw $e;
        }

        foreach ($this->visitedCollections as $col) {
            $col->takeSnapshot();
        }

        $this->invokeGlobalEvent(Event::postFlush, new ManagerEventArgs($this->dm));

        if (null === $document) {
            foreach ($this->documentLocales as $oid => $locales) {
                $this->documentLocales[$oid]['original'] = $locales['current'];
            }
        } else {
            $documents = is_array($document) ? $document : [$document];
            foreach ($documents as $doc) {
                $oid = \spl_object_hash($doc);
                if (array_key_exists($oid, $this->documentLocales)) {
                    $this->documentLocales[$oid]['original'] = $this->documentLocales[$oid]['current'];
                }
            }
        }

        $this->scheduledUpdates =
        $this->scheduledRemovals =
        $this->scheduledMoves =
        $this->scheduledReorders =
        $this->scheduledInserts =
        $this->visitedCollections =
        $this->documentChangesets =
        $this->changesetComputed = [];

        $this->invokeGlobalEvent(Event::endFlush, new ManagerEventArgs($this->dm));
    }

    /**
     * @param object[] $documents
     */
    private function executeInserts(array $documents): void
    {
        // sort the documents to insert parents first but maintain child order
        $oids = [];
        foreach ($documents as $oid => $document) {
            if (!$this->contains($oid)) {
                continue;
            }

            $oids[$oid] = $this->getDocumentId($document);
        }

        $order = array_flip(array_values($oids));
        uasort(
            $oids,
            function ($a, $b) use ($order) {
                // compute the node depths
                $aCount = substr_count($a, '/');
                $bCount = substr_count($b, '/');

                // ensure that the original order is maintained for nodes with the same depth
                if ($aCount === $bCount) {
                    return ($order[$a] < $order[$b]) ? -1 : 1;
                }

                return ($aCount < $bCount) ? -1 : 1;
            }
        );

        $associationChangesets = $associationUpdates = [];

        foreach ($oids as $oid => $id) {
            $document = $documents[$oid];
            /** @var ClassMetadata $class */
            $class = $this->dm->getClassMetadata(get_class($document));

            // PHPCR does not validate nullable unless we would start to
            // generate custom node types, which we at the moment don't.
            // the ORM can delegate this validation to the relational database
            // that is using a strict schema
            foreach ($class->fieldMappings as $fieldName) {
                if (!isset($this->documentChangesets[$oid]['fields'][$fieldName]) // empty string is ok
                    && !$class->isNullable($fieldName)
                    && !$this->isAutocreatedProperty($class, $fieldName)
                ) {
                    throw new PHPCRException(sprintf('Field "%s" of class "%s" is not nullable', $fieldName, $class->name));
                }
            }

            $parentNode = $this->session->getNode(PathHelper::getParentPath($id));
            $this->validateChildClass($parentNode, $class);

            $nodename = PathHelper::getNodeName($id);
            $node = $parentNode->addNode($nodename, $class->nodeType);
            if ($class->node) {
                $this->originalData[$oid][$class->node] = $node;
            }
            if ($class->nodename) {
                $this->originalData[$oid][$class->nodename] = $nodename;
            }

            try {
                $node->addMixin('phpcr:managed');
            } catch (NoSuchNodeTypeException $e) {
                throw new PHPCRException('Register phpcr:managed node type first. See https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr:managed');
            }

            foreach ($class->mixins as $mixin) {
                $node->addMixin($mixin);
            }

            if ($class->identifier) {
                $class->setIdentifierValue($document, $id);
            }
            if ($class->node) {
                $class->reflFields[$class->node]->setValue($document, $node);
            }
            if ($class->nodename) {
                // make sure this reflects the id generator strategy generated id
                $class->reflFields[$class->nodename]->setValue($document, $node->getName());
            }
            // make sure this reflects the id generator strategy generated id
            if ($class->parentMapping && !$class->getFieldValue($document, $class->parentMapping)) {
                $class->reflFields[$class->parentMapping]->setValue($document, $this->getOrCreateProxyFromNode($parentNode, $this->getCurrentLocale($document, $class)));
            }

            if ($this->writeMetadata) {
                $this->documentClassMapper->writeMetadata($this->dm, $node, $class->name);
            }

            $this->setMixins($class, $node, $document);

            $fields = $this->documentChangesets[$oid]['fields'] ?? [];
            foreach ($fields as $fieldName => $fieldValue) {
                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields, true)) {
                    continue;
                }

                if (in_array($fieldName, $class->fieldMappings, true)) {
                    $mapping = $class->mappings[$fieldName];
                    $type = PropertyType::valueFromName($mapping['type']);
                    if (null === $fieldValue) {
                        continue;
                    }

                    if ($mapping['multivalue'] && $fieldValue) {
                        $fieldValue = (array) $fieldValue;
                        if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                            $fieldValue = $this->processAssoc($node, $mapping, $fieldValue);
                        }
                    }

                    $node->setProperty($mapping['property'], $fieldValue, $type);
                } elseif (in_array($fieldName, $class->referenceMappings, true) || in_array($fieldName, $class->referrersMappings, true)) {
                    $associationUpdates[$oid] = $document;

                    // populate $associationChangesets to force executeUpdates($associationUpdates)
                    // to only update association fields
                    $data = $associationChangesets[$oid]['fields'] ?? [];
                    $data[$fieldName] = [null, $fieldValue];
                    $associationChangesets[$oid] = ['fields' => $data];
                }
            }

            $this->doSaveTranslation($document, $node, $class);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postPersist)) {
                $this->eventListenersInvoker->invoke(
                    $class,
                    Event::postPersist,
                    $document,
                    new LifecycleEventArgs($document, $this->dm),
                    $invoke
                );
            }
        }

        $this->documentChangesets = array_merge($this->documentChangesets, $associationChangesets);

        $this->executeUpdates($associationUpdates, false);
    }

    /**
     * Identify whether a PHPCR property is autocreated or not.
     */
    private function isAutocreatedProperty(ClassMetadata $class, string $fieldName): bool
    {
        $field = $class->getFieldMapping($fieldName);
        if ('jcr:uuid' === $field['property']) {
            // jackrabbit at least does not identify this as auto created
            // it is strictly speaking no property
            return true;
        }
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        $nodeType = $ntm->getNodeType($class->getNodeType());
        $propertyDefinitions = $nodeType->getPropertyDefinitions();
        foreach ($class->getMixins() as $mixinTypeName) {
            $nodeType = $ntm->getNodeType($mixinTypeName);
            $propertyDefinitions = array_merge($propertyDefinitions, $nodeType->getPropertyDefinitions());
        }

        foreach ($propertyDefinitions as $property) {
            if ($class->mappings[$fieldName]['property'] === $property->getName()
                && $property->isAutoCreated()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object[] $documents
     */
    private function executeUpdates(array $documents, bool $dispatchEvents = true): void
    {
        foreach ($documents as $oid => $document) {
            if (!$this->contains($oid)) {
                continue;
            }

            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->session->getNode($this->getDocumentId($document));

            if ($this->writeMetadata) {
                $this->documentClassMapper->writeMetadata($this->dm, $node, $class->name);
            }

            if ($dispatchEvents) {
                if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preUpdate)) {
                    $this->eventListenersInvoker->invoke(
                        $class,
                        Event::preUpdate,
                        $document,
                        new PreUpdateEventArgs($document, $this->dm, $this->documentChangesets[$oid]),
                        $invoke
                    );
                    $this->changesetComputed = array_diff($this->changesetComputed, [$oid]);
                    $this->computeChangeSet($class, $document);
                }
            }

            $fields = $this->documentChangesets[$oid]['fields'] ?? [];
            foreach ($fields as $fieldName => $data) {
                $fieldValue = $data[1];

                // PHPCR does not validate nullable unless we would start to
                // generate custom node types, which we at the moment don't.
                // the ORM can delegate this validation to the relational database
                // that is using a strict schema.
                // do this after the preUpdate events to give listener a last
                // chance to provide values
                if (null === $fieldValue
                    && in_array($fieldName, $class->fieldMappings, true) // only care about non-virtual fields
                    && !$class->isNullable($fieldName)
                    && !$this->isAutocreatedProperty($class, $fieldName)
                ) {
                    throw new PHPCRException(sprintf('Field "%s" of class "%s" is not nullable', $fieldName, $class->name));
                }

                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields, true)) {
                    continue;
                }

                $mapping = $class->mappings[$fieldName];
                if (in_array($fieldName, $class->fieldMappings, true)) {
                    $type = PropertyType::valueFromName($mapping['type']);
                    if ($mapping['multivalue']) {
                        $value = empty($fieldValue) ? null : ($fieldValue instanceof Collection ? $fieldValue->toArray() : $fieldValue);
                        if ($value && array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                            $value = $this->processAssoc($node, $mapping, $value);
                        }
                    } else {
                        $value = $fieldValue;
                    }
                    $node->setProperty($mapping['property'], $value, $type);
                } elseif ($mapping['type'] === $class::MANY_TO_ONE
                    || $mapping['type'] === $class::MANY_TO_MANY
                ) {
                    if (!$this->writeMetadata) {
                        continue;
                    }
                    if (is_null($fieldValue) && $node->hasProperty($mapping['property'])) {
                        $node->getProperty($mapping['property'])->remove();
                        if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                            $this->removeAssoc($node, $mapping);
                        }

                        continue;
                    }

                    switch ($mapping['strategy']) {
                        case 'hard':
                            $strategy = PropertyType::REFERENCE;

                            break;
                        case 'path':
                            $strategy = PropertyType::PATH;

                            break;
                        default:
                            $strategy = PropertyType::WEAKREFERENCE;

                            break;
                    }

                    if ($mapping['type'] === $class::MANY_TO_MANY) {
                        if (null !== $fieldValue) {
                            $refNodesIds = [];
                            foreach ($fieldValue as $fv) {
                                if (null === $fv) {
                                    continue;
                                }

                                $associatedNode = $this->session->getNode($this->getDocumentId($fv));
                                if (PropertyType::PATH === $strategy) {
                                    $refNodesIds[] = $associatedNode->getPath();
                                } else {
                                    $refClass = $this->dm->getClassMetadata(get_class($fv));
                                    $this->setMixins($refClass, $associatedNode, $fv);
                                    if (!$associatedNode->isNodeType('mix:referenceable')) {
                                        throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), ClassUtils::getClass($fv)));
                                    }
                                    $refNodesIds[] = $associatedNode->getIdentifier();
                                }
                            }

                            $refNodesIds = empty($refNodesIds) ? null : $refNodesIds;
                            $node->setProperty($mapping['property'], $refNodesIds, $strategy);
                        }
                    } elseif ($mapping['type'] === $class::MANY_TO_ONE) {
                        if (null !== $fieldValue) {
                            $associatedNode = $this->session->getNode($this->getDocumentId($fieldValue));

                            if (PropertyType::PATH === $strategy) {
                                $node->setProperty($fieldName, $associatedNode->getPath(), $strategy);
                            } else {
                                $refClass = $this->dm->getClassMetadata(get_class($fieldValue));
                                $this->setMixins($refClass, $associatedNode, $document);
                                if (!$associatedNode->isNodeType('mix:referenceable')) {
                                    throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), ClassUtils::getClass($fieldValue)));
                                }
                                $node->setProperty($mapping['property'], $associatedNode->getIdentifier(), $strategy);
                            }
                        }
                    }
                } elseif ('referrers' === $mapping['type']) {
                    if (null !== $fieldValue) {
                        /*
                         * each document in referrers field is supposed to
                         * reference this document, so we have to update its
                         * referencing property to contain the uuid of this
                         * document
                         */
                        foreach ($fieldValue as $fv) {
                            if (null === $fv) {
                                continue;
                            }

                            if (!$fv instanceof $mapping['referringDocument']) {
                                throw new PHPCRException(sprintf('%s is not an instance of %s for document %s field %s', self::objToStr($fv, $this->dm), $mapping['referencedBy'], self::objToStr($document, $this->dm), $mapping['fieldName']));
                            }

                            $referencingNode = $this->session->getNode($this->getDocumentId($fv));
                            $referencingMeta = $this->dm->getClassMetadata($mapping['referringDocument']);
                            $referencingField = $referencingMeta->getAssociation($mapping['referencedBy']);

                            $uuid = $node->getIdentifier();
                            $strategy = 'weak' == $referencingField['strategy'] ? PropertyType::WEAKREFERENCE : PropertyType::REFERENCE;
                            switch ($referencingField['type']) {
                                case ClassMetadata::MANY_TO_ONE:
                                    $ref = $referencingMeta->getFieldValue($fv, $referencingField['fieldName']);
                                    if (null !== $ref && $ref !== $document) {
                                        throw new PHPCRException(sprintf('Conflicting settings for referrer and reference: Document %s field %s points to %s but document %s has set first document as referrer on field %s', self::objToStr($fv, $this->dm), $referencingField['fieldName'], self::objToStr($ref, $this->dm), self::objToStr($document, $this->dm), $mapping['fieldName']));
                                    }
                                    // update the referencing document field to point to this document
                                    $referencingMeta->setFieldValue($fv, $referencingField['fieldName'], $document);
                                    // and make sure the reference is not deleted in this change because the field could be null
                                    unset($this->documentChangesets[\spl_object_hash($fv)]['fields'][$referencingField['fieldName']]);
                                    // store the change in PHPCR
                                    $referencingNode->setProperty($referencingField['property'], $uuid, $strategy);

                                    break;
                                case ClassMetadata::MANY_TO_MANY:
                                    /** @var ReferenceManyCollection $collection */
                                    $collection = $referencingMeta->getFieldValue($fv, $referencingField['fieldName']);
                                    if ($collection instanceof PersistentCollection && $collection->isDirty()) {
                                        throw new PHPCRException(sprintf('You may not modify the reference and referrer collections of interlinked documents as this is ambiguous. Reference %s on document %s and referrers %s on document %s are both modified', self::objToStr($fv, $this->dm), $referencingField['fieldName'], self::objToStr($document, $this->dm), $mapping['fieldName']));
                                    }
                                    if ($collection) {
                                        // make sure the reference is not deleted in this change because the field could be null
                                        unset($this->documentChangesets[\spl_object_hash($fv)]['fields'][$referencingField['fieldName']]);
                                    } else {
                                        $collection = new ReferenceManyCollection($this->dm, $fv, $referencingField['property'], [$node], $class->name, null, $this->getReferenceManyCollectionTypeFromMetadata($mapping));
                                        $referencingMeta->setFieldValue($fv, $referencingField['fieldName'], $collection);
                                    }

                                    if ($referencingNode->hasProperty($referencingField['property'])) {
                                        if (!in_array($uuid, $referencingNode->getProperty($referencingField['property'])->getString(), true)) {
                                            if (!$collection instanceof PersistentCollection || !$collection->isDirty()) {
                                                // update the reference collection: add us to it
                                                $collection->add($document);
                                            }
                                            // store the change in PHPCR
                                            $referencingNode->getProperty($referencingField['property'])->addValue($uuid); // property should be correct type already
                                        }
                                    } else {
                                        // store the change in PHPCR
                                        $referencingNode->setProperty($referencingField['property'], [$uuid], $strategy);
                                    }

                                    // avoid confusion later, this change to the reference collection is already saved
                                    $collection->setDirty(false);

                                    break;
                                default:
                                    // in class metadata we only did a santiy check but not look at the actual mapping
                                    throw new MappingException(sprintf('Field "%s" of document "%s" is not a reference field. Error in referrer annotation: '.self::objToStr($document, $this->dm), $mapping['referencedBy'], ClassUtils::getClass($fv)));
                            }
                        }
                    }
                } elseif ('child' === $mapping['type']) {
                    if (null === $fieldValue && $node->hasNode($mapping['nodeName'])) {
                        $child = $node->getNode($mapping['nodeName']);
                        $childDocument = $this->getOrCreateDocument(null, $child);
                        $this->purgeChildren($childDocument);
                        $child->remove();
                    }
                }
            }

            if (!empty($this->documentChangesets[$oid]['reorderings'])) {
                foreach ($this->documentChangesets[$oid]['reorderings'] as $reorderings) {
                    foreach ($reorderings as $srcChildRelPath => $destChildRelPath) {
                        $node->orderBefore($srcChildRelPath, $destChildRelPath);
                    }
                }
            }

            $this->doSaveTranslation($document, $node, $class);

            if ($dispatchEvents) {
                if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postUpdate)) {
                    $this->eventListenersInvoker->invoke(
                        $class,
                        Event::postUpdate,
                        $document,
                        new LifecycleEventArgs($document, $this->dm),
                        $invoke
                    );
                }
            }
        }
    }

    /**
     * @param object[] $documents
     */
    private function executeMoves(array $documents): void
    {
        foreach ($documents as $oid => $value) {
            if (!$this->contains($oid)) {
                continue;
            }

            [$document, $targetPath] = $value;

            $sourcePath = $this->getDocumentId($document);
            if ($sourcePath === $targetPath) {
                continue;
            }

            $class = $this->dm->getClassMetadata(get_class($document));
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preMove)) {
                $this->eventListenersInvoker->invoke(
                    $class,
                    Event::preMove,
                    $document,
                    new MoveEventArgs($document, $this->dm, $sourcePath, $targetPath),
                    $invoke
                );
            }

            $parentNode = $this->session->getNode(PathHelper::getParentPath($targetPath));
            $this->validateChildClass($parentNode, $class);

            $this->session->move($sourcePath, $targetPath);

            // update fields nodename, parentMapping and depth if they exist in this type
            $node = $this->session->getNode($targetPath); // get node from session, document class might not map it
            if ($class->nodename) {
                $class->setFieldValue($document, $class->nodename, $node->getName());
            }

            if ($class->parentMapping) {
                $class->setFieldValue($document, $class->parentMapping, $this->getOrCreateProxyFromNode($node->getParent(), $this->getCurrentLocale($document, $class)));
            }

            if ($class->depthMapping) {
                $class->setFieldValue($document, $class->depthMapping, $node->getDepth());
            }

            // update all cached children of the document to reflect the move (path id changes)
            foreach ($this->documentIds as $childOid => $id) {
                if (0 !== strpos($id, $sourcePath)) {
                    continue;
                }

                $newId = $targetPath.substr($id, strlen($sourcePath));
                $this->documentIds[$childOid] = $newId;

                $child = $this->getDocumentById($id);
                if (!$child) {
                    continue;
                }

                unset($this->identityMap[$id]);
                $this->identityMap[$newId] = $child;

                $childClass = $this->dm->getClassMetadata(get_class($child));
                if ($childClass->identifier) {
                    $childClass->setIdentifierValue($child, $newId);
                    if (!$child instanceof Proxy || $child->__isInitialized()) {
                        $this->originalData[$childOid][$childClass->identifier] = $newId;
                    }
                }
            }

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postMove)) {
                $this->eventListenersInvoker->invoke(
                    $class,
                    Event::postMove,
                    $document,
                    new MoveEventArgs($document, $this->dm, $sourcePath, $targetPath),
                    $invoke
                );
            }
        }
    }

    /**
     * @param object[] $documents
     */
    private function executeReorders(array $documents): void
    {
        foreach ($documents as $oid => $list) {
            if (!$this->contains($oid)) {
                continue;
            }
            foreach ($list as $value) {
                [$parent, $src, $target, $before] = $value;
                $parentNode = $this->session->getNode($this->getDocumentId($parent));

                // check for src and target ...
                $dest = $target;
                if ($parentNode->hasNode($src) && $parentNode->hasNode($target)) {
                    // there is no orderAfter, so we need to find the child after target to use it in orderBefore
                    if (!$before) {
                        $dest = null;
                        $found = false;
                        foreach ($parentNode->getNodes() as $name => $child) {
                            if ($name === $target) {
                                $found = true;
                            } elseif ($found) {
                                $dest = $name;

                                break;
                            }
                        }
                    }

                    $parentNode->orderBefore($src, $dest);
                    $class = $this->dm->getClassMetadata(get_class($parent));
                    foreach ($class->childrenMappings as $fieldName) {
                        if ($parent instanceof Proxy && $parent->__isInitialized()) {
                            $children = $class->getFieldValue($parent, $fieldName);
                            $children->refresh();
                        }
                    }
                }
            }
        }
    }

    /**
     * @param object[] $documents
     */
    private function executeRemovals(array $documents): void
    {
        foreach ($documents as $oid => $document) {
            if (empty($this->documentIds[$oid])) {
                continue;
            }

            $class = $this->dm->getClassMetadata(get_class($document));
            $id = $this->getDocumentId($document);

            try {
                $node = $this->session->getNode($id);
                $this->doRemoveAllTranslations($document, $class);
                $node->remove();
            } catch (PathNotFoundException $e) {
            }

            $this->unregisterDocument($document);
            $this->purgeChildren($document);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postRemove)) {
                $this->eventListenersInvoker->invoke(
                    $class,
                    Event::postRemove,
                    $document,
                    new LifecycleEventArgs($document, $this->dm),
                    $invoke
                );
            }
        }
    }

    /**
     * @see DocumentManager::findVersionByName
     */
    public function findVersionByName(?string $className, string $id, string $versionName): ?object
    {
        $versionManager = $this->session
            ->getWorkspace()
            ->getVersionManager();

        try {
            $history = $versionManager->getVersionHistory($id);
        } catch (ItemNotFoundException $e) {
            // there is no document with $id
            return null;
        } catch (UnsupportedRepositoryOperationException $e) {
            throw new InvalidArgumentException("Document with id $id is not versionable", $e->getCode(), $e);
        }

        try {
            $version = $history->getVersion($versionName);
            $node = $version->getFrozenNode();
        } catch (RepositoryException $e) {
            throw new InvalidArgumentException("No version $versionName on document $id", $e->getCode(), $e);
        }

        $hints = ['versionName' => $versionName, 'ignoreHardReferenceNotFound' => true];
        $frozenDocument = $this->getOrCreateDocument($className, $node, $hints);
        if (!$frozenDocument) {
            return null;
        }

        $oid = \spl_object_hash($frozenDocument);
        $this->documentHistory[$oid] = $history;
        $this->documentVersion[$oid] = $version;

        // Set the annotations
        $metadata = $this->dm->getClassMetadata(get_class($frozenDocument));
        if ($metadata->versionNameField) {
            $metadata->reflFields[$metadata->versionNameField]->setValue($frozenDocument, $versionName);
        }
        if ($metadata->versionCreatedField) {
            $metadata->reflFields[$metadata->versionCreatedField]->setValue($frozenDocument, $version->getCreated());
        }

        return $frozenDocument;
    }

    /**
     * Checkin operation - Save all current changes and then check in the Node by id.
     */
    public function checkin(object $document): void
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkin($path); // Checkin Node aka make a new Version
    }

    /**
     * Check out operation - Save all current changes and then check out the
     * Node by path.
     */
    public function checkout(object $document): void
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkout($path);
    }

    /**
     * Create a version of the document and check it out right again to
     * continue editing.
     */
    public function checkpoint(object $document): void
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkpoint($path);
    }

    /**
     * Get the version history information for a document.
     *
     * TODO: implement labels once jackalope implements them, until then labels will be an empty array.
     * TODO: implement limit
     *
     * @param int $limit an optional limit to only get the latest $limit information
     *
     * @return array of <versionname> => array("name" => <versionname>, "labels" => <array of labels>, "created" => <DateTime>)
     *               oldest version first
     */
    public function getAllLinearVersions(object $document, int $limit = -1): array
    {
        $path = $this->getDocumentId($document);
        $metadata = $this->dm->getClassMetadata(get_class($document));

        if (!$metadata->versionable) {
            throw new InvalidArgumentException(sprintf("The document of type '%s' is not versionable", $metadata->getName()));
        }

        $versions = $this->session
            ->getWorkspace()
            ->getVersionManager()
            ->getVersionHistory($path)
            ->getAllLinearVersions();

        $result = [];
        foreach ($versions as $version) {
            /* @var $version VersionInterface */
            $result[$version->getName()] = [
                'name' => $version->getName(),
                'labels' => [],
                'created' => $version->getCreated(),
            ];
        }

        return $result;
    }

    /**
     * @see VersionManager::restore
     */
    public function restoreVersion(object $documentVersion, bool $removeExisting): void
    {
        $oid = \spl_object_hash($documentVersion);
        $history = $this->documentHistory[$oid];
        $version = $this->documentVersion[$oid];
        $document = $this->dm->find(null, $history->getVersionableIdentifier());
        $vm = $this->session->getWorkspace()->getVersionManager();

        $vm->restore($removeExisting, $version);
        $this->dm->refresh($document);
    }

    /**
     * @see DocumentManager::removeVersion
     */
    public function removeVersion(object $documentVersion): void
    {
        $oid = \spl_object_hash($documentVersion);
        $history = $this->documentHistory[$oid];
        $version = $this->documentVersion[$oid];

        $history->removeVersion($version->getName());

        unset($this->documentVersion[$oid], $this->documentHistory[$oid]);
    }

    /**
     * Removes a document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     */
    private function unregisterDocument(object $document): void
    {
        $oid = \spl_object_hash($document);

        if (array_key_exists($oid, $this->documentIds)) {
            unset($this->identityMap[$this->documentIds[$oid]]);
        }

        unset($this->scheduledRemovals[$oid],
            $this->scheduledUpdates[$oid],
            $this->scheduledMoves[$oid],
            $this->scheduledReorders[$oid],
            $this->scheduledInserts[$oid],
            $this->originalData[$oid],
            $this->originalTranslatedData[$oid],
            $this->documentIds[$oid],
            $this->documentState[$oid],
            $this->documentTranslations[$oid],
            $this->documentLocales[$oid],
            $this->nonMappedData[$oid],
            $this->documentChangesets[$oid],
            $this->documentHistory[$oid],
            $this->documentVersion[$oid]
        );

        $this->changesetComputed = array_diff($this->changesetComputed, [$oid]);
    }

    /**
     * @param string $id the id that this document has
     *
     * @return string generated object hash
     */
    public function registerDocument(object $document, string $id): string
    {
        $oid = \spl_object_hash($document);
        $this->documentIds[$oid] = $id;
        $this->identityMap[$id] = $document;

        // frozen nodes need another state so they are managed but not included for updates
        $frozen = $this->session->nodeExists($id) && $this->session->getNode($id)->isNodeType('nt:frozenNode');

        $this->setDocumentState($oid, $frozen ? self::STATE_FROZEN : self::STATE_MANAGED);

        return $oid;
    }

    /**
     * @param object|string $document document instance or document object hash
     */
    public function contains($document): bool
    {
        $oid = is_object($document) ? \spl_object_hash($document) : $document;

        return array_key_exists($oid, $this->documentIds) && !array_key_exists($oid, $this->scheduledRemovals);
    }

    /**
     * Tries to find a document with the given id in the identity map of
     * this UnitOfWork.
     *
     * @return object|null returns the document with the specified id if it exists in
     *                     this UnitOfWork, null otherwise
     */
    public function getDocumentById(string $id): ?object
    {
        return $this->identityMap[$id] ?? null;
    }

    /**
     * Get the object ID for the given document.
     *
     * @param object|string $document document instance or document object hash
     *
     * @throws PHPCRException
     */
    public function getDocumentId($document, bool $throw = true): ?string
    {
        $oid = is_object($document) ? \spl_object_hash($document) : $document;
        if (empty($this->documentIds[$oid])) {
            if (!$throw) {
                return null;
            }
            $msg = 'Document is not managed and has no id';
            if (is_object($document)) {
                $msg .= ': '.self::objToStr($document);
            }

            throw new PHPCRException($msg);
        }

        return $this->documentIds[$oid];
    }

    /**
     * Try to determine the document id first by looking into the document,
     * but if not mapped, look into the document id cache.
     */
    public function determineDocumentId(object $document, ClassMetadata $metadata = null): ?string
    {
        if (!$metadata) {
            $metadata = $this->dm->getClassMetadata(get_class($document));
        }
        if ($metadata->identifier) {
            $id = $metadata->getIdentifierValue($document);
            if ($id) {
                return $id;
            }
        }

        return $this->getDocumentId($document, false);
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     */
    public function initializeObject(object $obj): void
    {
        if ($obj instanceof Proxy) {
            $obj->__load();
        } elseif ($obj instanceof PersistentCollection) {
            $obj->initialize();
        }
    }

    /**
     * Clears the UnitOfWork.
     */
    public function clear(): void
    {
        $this->identityMap =
        $this->documentIds =
        $this->documentState =
        $this->documentTranslations =
        $this->documentLocales =
        $this->nonMappedData =
        $this->originalData =
        $this->originalTranslatedData =
        $this->documentChangesets =
        $this->changesetComputed =
        $this->scheduledUpdates =
        $this->scheduledInserts =
        $this->scheduledMoves =
        $this->scheduledReorders =
        $this->scheduledRemovals =
        $this->visitedCollections =
        $this->documentHistory =
        $this->documentVersion = [];

        $this->invokeGlobalEvent(Event::onClear, new OnClearEventArgs($this->dm));

        $this->session->refresh(false);
    }

    /**
     * Get all locales in which this document currently exists in storage.
     *
     * @param object $document A managed document
     *
     * @return array<string> list of locales of this document
     *
     * @throws MissingTranslationException if this document is not translatable
     */
    public function getLocalesFor(object $document): array
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            throw new MissingTranslationException('This document is not translatable: : '.self::objToStr($document, $this->dm));
        }

        $oid = \spl_object_hash($document);
        if ($this->contains($oid)) {
            try {
                $node = $this->session->getNode($this->getDocumentId($document));
                $locales = $this->dm->getTranslationStrategy($metadata->translator)->getLocalesFor($document, $node, $metadata);
            } catch (PathNotFoundException $e) {
                $locales = [];
            }
        } else {
            $locales = [];
        }

        if (array_key_exists($oid, $this->documentTranslations)) {
            foreach ($this->documentTranslations[$oid] as $locale => $value) {
                if (!in_array($locale, $locales, true)) {
                    if ($value) {
                        $locales[] = $locale;
                    }
                } elseif (!$value) {
                    $key = array_search($locale, $locales, true);
                    unset($locales[$key]);
                }
            }

            $locales = array_values($locales);
        }

        return $locales;
    }

    private function doSaveTranslation(object $document, NodeInterface $node, ClassMetadata $metadata): void
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $oid = \spl_object_hash($document);
        if (!empty($this->documentTranslations[$oid])) {
            $strategy = $this->dm->getTranslationStrategy($metadata->translator);
            foreach ($this->documentTranslations[$oid] as $locale => $data) {
                if ($data) {
                    foreach ($data as $fieldName => $fieldValue) {
                        $this->originalTranslatedData[$oid][$locale][$fieldName] = $fieldValue;
                    }

                    $strategy->saveTranslation($data, $node, $metadata, $locale);
                } else {
                    $strategy->removeTranslation($document, $node, $metadata, $locale);

                    $class = $this->dm->getClassMetadata(get_class($document));
                    if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postRemoveTranslation)) {
                        $this->eventListenersInvoker->invoke(
                            $class,
                            Event::postRemoveTranslation,
                            $document,
                            new LifecycleEventArgs($document, $this->dm),
                            $invoke
                        );
                    }
                }
            }
        }
    }

    /**
     * Load an in-memory bound translation if there is one in the requested
     * locale. Does not attempt any fallback.
     *
     * @return bool whether the pending translation in language $locale was
     *              loaded or not
     *
     * @see doLoadTranslation
     */
    protected function doLoadPendingTranslation(object $document, ClassMetadata $metadata, string $locale): bool
    {
        $oid = \spl_object_hash($document);

        if (!array_key_exists($oid, $this->documentTranslations)
            || !array_key_exists($locale, $this->documentTranslations[$oid])) {
            return false;
        }

        $translations = $this->documentTranslations[$oid][$locale];
        foreach ($metadata->translatableFields as $field) {
            $metadata->reflFields[$field]->setValue($document, $translations[$field]);
        }

        return true;
    }

    /**
     * Attempt to load translation from the database.
     *
     * If $fallback is true, goes over the locales as provided by the locale
     * chooser strategy to find the best language, each time first checking for
     * a pending translation. If no translation is found at all, the translated
     * fields are set to null and the requested locale is considered to be the
     * one found.
     *
     * @param bool $fallback whether to perform language fallback
     * @param bool $refresh  whether to force reloading the translation
     *
     * @throws MissingTranslationException if the translation in $locale is not found and $fallback is false
     *
     * @see doLoadTranslation
     */
    protected function doLoadDatabaseTranslation(object $document, ClassMetadata $metadata, string $locale, bool $fallback, bool $refresh): string
    {
        $oid = \spl_object_hash($document);

        $strategy = $this->dm->getTranslationStrategy($metadata->translator);

        try {
            $node = $this->session->getNode($this->getDocumentId($oid));
            if ($strategy->loadTranslation($document, $node, $metadata, $locale)) {
                return $locale;
            }
        } catch (PathNotFoundException $e) {
            // no node, document not persisted yet
            $node = null;
        }

        if (!$fallback) {
            $msg = sprintf('Document %s has no translation %s and fallback was not active', $this->getDocumentId($oid), $locale);

            throw new MissingTranslationException($msg);
        }

        $localesToTry = $this->dm->getLocaleChooserStrategy()->getFallbackLocales($document, $metadata, $locale);

        foreach ($localesToTry as $desiredLocale) {
            if (!$refresh && $desiredLocale === ($this->documentLocales[$oid]['current'] ?? false)) {
                // noop, already the correct locale.
                return $desiredLocale;
            }
            // if there is a pending translation, it wins
            if ($this->doLoadPendingTranslation($document, $metadata, $desiredLocale)) {
                return $desiredLocale;
            }
            // try loading the translation with strategy if this is a stored document
            if ($node && $strategy->loadTranslation($document, $node, $metadata, $desiredLocale)) {
                return $desiredLocale;
            }
        }

        // we found no locale. so all translated fields are null and we
        // consider the locale to be the requested one
        $localeUsed = $locale;
        foreach ($metadata->translatableFields as $fieldName) {
            $value = ($metadata->mappings[$fieldName]['multivalue']) ? [] : null;
            $metadata->reflFields[$fieldName]->setValue($document, $value);
        }

        return $locale;
    }

    /**
     * Load the translatable fields of the document.
     *
     * If locale is not set then the current locale of the document is
     * reloaded, resetting possible changes.
     *
     * If the document is not translatable, this method returns immediately
     * and without error.
     */
    public function doLoadTranslation(object $document, ClassMetadata $metadata, string $locale = null, bool $fallback = false, bool $refresh = false): void
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $currentLocale = $this->getCurrentLocale($document, $metadata);

        $oid = \spl_object_hash($document);
        if (null === $locale || $locale === $currentLocale) {
            // current locale is already loaded and not removed
            if (!$refresh
                && array_key_exists($oid, $this->documentTranslations)
                && array_key_exists($currentLocale, $this->documentTranslations[$oid])
            ) {
                return;
            }

            $locale = $currentLocale;
        }

        if (!$refresh && $this->doLoadPendingTranslation($document, $metadata, $locale)) {
            $localeUsed = $locale;
        } else {
            $localeUsed = $this->doLoadDatabaseTranslation($document, $metadata, $locale, $fallback, $refresh);
        }

        $this->doBindTranslation($document, $localeUsed, $metadata);

        foreach ($metadata->translatableFields as $fieldName) {
            $this->originalData[$oid][$fieldName] = $this->originalTranslatedData[$oid][$localeUsed][$fieldName]
                = $metadata->getFieldValue($document, $fieldName);
        }

        if ($metadata->parentMapping && $parent = $metadata->getFieldValue($document, $metadata->parentMapping)) {
            $this->cascadeDoLoadTranslation($parent, $metadata->mappings[$metadata->parentMapping], $locale);
        }

        if ($metadata->childMappings) {
            foreach ($metadata->childMappings as $fieldName) {
                if ($child = $metadata->getFieldValue($document, $fieldName)) {
                    $this->cascadeDoLoadTranslation($child, $metadata->mappings[$fieldName], $locale);
                }
            }
        }

        if ($metadata->childrenMappings) {
            foreach ($metadata->childrenMappings as $fieldName) {
                $children = $metadata->getFieldValue($document, $fieldName);
                if ($children instanceof ChildrenCollection && !$children->isInitialized()) {
                    $children->setLocale($locale);
                } elseif (!empty($children)) {
                    foreach ($children as $child) {
                        $this->cascadeDoLoadTranslation($child, $metadata->mappings[$fieldName], $locale);
                    }
                }
            }
        }

        if ($metadata->referenceMappings) {
            foreach ($metadata->referenceMappings as $fieldName) {
                $reference = $metadata->getFieldValue($document, $fieldName);
                if ($reference instanceof ReferenceManyCollection && !$reference->isInitialized()) {
                    $reference->setLocale($locale);
                } else {
                    if ($reference instanceof \Traversable || is_array($reference)) {
                        foreach ($reference as $ref) {
                            $this->cascadeDoLoadTranslation($ref, $metadata->mappings[$fieldName], $locale);
                        }
                    } elseif ($reference) {
                        $this->cascadeDoLoadTranslation($reference, $metadata->mappings[$fieldName], $locale);
                    }
                }
            }
        }

        if ($metadata->referrersMappings) {
            foreach ($metadata->referrersMappings as $fieldName) {
                $referrers = $metadata->getFieldValue($document, $fieldName);
                if ($referrers instanceof ReferrersCollection && !$referrers->isInitialized()) {
                    $referrers->setLocale($locale);
                } elseif (!empty($referrers)) {
                    foreach ($referrers as $referrer) {
                        $this->cascadeDoLoadTranslation($referrer, $metadata->mappings[$fieldName], $locale);
                    }
                }
            }
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::postLoadTranslation)) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::postLoadTranslation,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        }
    }

    private function cascadeDoLoadTranslation(object $document, array $mapping, string $locale): void
    {
        if (!($mapping['cascade'] & ClassMetadata::CASCADE_TRANSLATION)) {
            return;
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            $this->setLocale($document, $class, $locale);
        } elseif ($this->isDocumentTranslatable($class)
            && $this->getCurrentLocale($document, $class) !== $locale
        ) {
            try {
                $this->doLoadTranslation($document, $class, $locale, true);
            } catch (\Exception $e) {
                // do nothing
            }
        }
    }

    /**
     * @throws MissingTranslationException
     */
    public function removeTranslation(object $document, string $locale): void
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($class, Event::preRemoveTranslation)) {
            $this->eventListenersInvoker->invoke(
                $class,
                Event::preRemoveTranslation,
                $document,
                new LifecycleEventArgs($document, $this->dm),
                $invoke
            );
        }

        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        if (1 === count($this->getLocalesFor($document))) {
            throw new PHPCRException('The last translation of a translatable document may not be removed');
        }

        if ($document instanceof Proxy) {
            $document->__load();
        }

        $oid = \spl_object_hash($document);
        $this->documentTranslations[$oid][$locale] = null;
        $this->setLocale($document, $metadata, null);
    }

    /**
     * Checks if the translation was removed.
     *
     * Note it also returns true if the document isn't translated
     * or was not translated into the given locale, ie.
     * it does not check if there is a translation for the given locale.
     */
    private function isTranslationRemoved(object $document, string $locale): bool
    {
        $oid = \spl_object_hash($document);

        return array_key_exists($oid, $this->documentTranslations)
            && array_key_exists($locale, $this->documentTranslations[$oid])
            && empty($this->documentTranslations[$oid][$locale]);
    }

    private function doRemoveAllTranslations(object $document, ClassMetadata $metadata): void
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $node = $this->session->getNode($this->getDocumentId($document));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);
        $strategy->removeAllTranslations($document, $node, $metadata);
    }

    private function setLocale(object $document, ClassMetadata $metadata, ?string $locale): void
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $oid = \spl_object_hash($document);
        if (empty($this->documentLocales[$oid])) {
            $this->documentLocales[$oid] = ['original' => $locale];
        }
        $this->documentLocales[$oid]['current'] = $locale;

        if ($metadata->localeMapping
            && (!$document instanceof Proxy || $document->__isInitialized())
        ) {
            $metadata->reflFields[$metadata->localeMapping]->setValue($document, $locale);
        }
    }

    /**
     * Determine the current locale of a managed document.
     *
     * If the document is not translatable, null is returned.
     *
     * If the document is translatable and the locale is mapped onto a document
     * field, the value of that field is returned. Otherwise the UnitOfWork
     * information on locales for documents without a locale mapping is
     * consulted.
     *
     * If nothing matches (for example when this is a detached document), the
     * default locale of the LocaleChooserStrategy is returned.
     *
     * @return string|null the current locale of $document or null if it is not translatable
     */
    public function getCurrentLocale(object $document, ClassMetadata $metadata = null): ?string
    {
        if (null === $metadata) {
            $metadata = $this->dm->getClassMetadata(get_class($document));
        }
        if (!$this->isDocumentTranslatable($metadata)) {
            return null;
        }

        if ($metadata->localeMapping
            && (!$document instanceof Proxy || $document->__isInitialized())
        ) {
            $locale = $metadata->getFieldValue($document, $metadata->localeMapping);
            if ($locale) {
                return $locale;
            }
        }

        $oid = \spl_object_hash($document);

        return $this->documentLocales[$oid]['current'] ?? $this->dm->getLocaleChooserStrategy()->getLocale();
    }

    /**
     * Determine whether this document is translatable.
     *
     * To be translatable, it needs a translation strategy and have at least
     * one translated field.
     */
    public function isDocumentTranslatable(ClassMetadata $metadata): bool
    {
        return !empty($metadata->translator)
            && is_string($metadata->translator)
            && 0 !== count($metadata->translatableFields);
    }

    private static function objToStr(object $obj, DocumentManagerInterface $dm = null)
    {
        $string = method_exists($obj, '__toString')
            ? (string) $obj
            : ClassUtils::getClass($obj).'@'.\spl_object_hash($obj);

        if ($dm) {
            try {
                $id = $dm->getUnitOfWork()->determineDocumentId($obj);
                if (!$id) {
                    $id = 'unmanaged or new document without id';
                }
                $string .= " ($id)";
            } catch (\Exception $e) {
                $id = 'failed to determine id';
                $string .= " ($id)";
            }
        }

        return $string;
    }

    private function getVersionedNodePath(object $document): ?string
    {
        $path = $this->getDocumentId($document);
        $metadata = $this->dm->getClassMetadata(get_class($document));

        if (!$metadata->versionable) {
            throw new InvalidArgumentException(sprintf(
                "The document at path '%s' is not versionable",
                $path
            ));
        }

        $node = $this->session->getNode($path);

        $mixin = 'simple' === $metadata->versionable ?
            'mix:simpleVersionable' :
            'mix:versionable';

        if (!$node->isNodeType($mixin)) {
            $node->addMixin($mixin);
        }

        return $path;
    }

    /**
     * @param object $document the document to update autogenerated fields
     */
    private function setMixins(ClassMetadata $metadata, NodeInterface $node, object $document): void
    {
        $repository = $this->session->getRepository();
        if ('full' === $metadata->versionable) {
            if ($repository->getDescriptor(RepositoryInterface::OPTION_VERSIONING_SUPPORTED)) {
                $node->addMixin('mix:versionable');
            } elseif ($repository->getDescriptor(RepositoryInterface::OPTION_SIMPLE_VERSIONING_SUPPORTED)) {
                $node->addMixin('mix:simpleVersionable');
            }
        } elseif ('simple' === $metadata->versionable
            && $repository->getDescriptor(RepositoryInterface::OPTION_SIMPLE_VERSIONING_SUPPORTED)
        ) {
            $node->addMixin('mix:simpleVersionable');
        }

        if (!$node->isNodeType('mix:referenceable') && $metadata->referenceable) {
            $node->addMixin('mix:referenceable');
        }

        // manually set the uuid if it is not present yet, so we can assign it to documents
        if ($node->isNodeType('mix:referenceable') && !$node->hasProperty('jcr:uuid')) {
            $uuid = false;

            $uuidFieldName = $metadata->getUuidFieldName();
            if ($uuidFieldName) {
                $uuid = $metadata->getFieldValue($document, $uuidFieldName);
            }

            if (!$uuid) {
                $uuid = $this->generateUuid();
            }

            $node->setProperty('jcr:uuid', $uuid);

            if ($uuidFieldName && !$metadata->getFieldValue($document, $uuidFieldName)) {
                $metadata->setFieldValue($document, $uuidFieldName, $uuid);
            }
        }
    }

    private function generateUuid(): string
    {
        return $this->uuidGenerator->__invoke();
    }

    /**
     * Extracts ReferenceManyCollection type from field metadata.
     */
    private function getReferenceManyCollectionTypeFromMetadata(array $referenceFieldMetadata): string
    {
        if ('path' === ($referenceFieldMetadata['strategy'] ?? 'not-path')) {
            return ReferenceManyCollection::REFERENCE_TYPE_PATH;
        }

        return ReferenceManyCollection::REFERENCE_TYPE_UUID;
    }

    /**
     * Sets the fetch depth on the session if the PHPCR session instance supports it
     * and returns the previous fetch depth value.
     *
     * @return int previous fetch depth value
     */
    public function setFetchDepth(int $fetchDepth = null): int
    {
        if (!$this->useFetchDepth
            || !method_exists($this->session, 'getSessionOption')
            || !method_exists($this->session, 'setSessionOption')
        ) {
            return 0;
        }

        $oldFetchDepth = $this->session->getSessionOption($this->useFetchDepth);

        if (null !== $fetchDepth) {
            $this->session->setSessionOption($this->useFetchDepth, $fetchDepth);
        }

        return $oldFetchDepth;
    }

    /**
     * Gets the currently scheduled document updates in this UnitOfWork.
     */
    public function getScheduledUpdates(): array
    {
        return $this->scheduledUpdates;
    }

    /**
     * Gets the currently scheduled document insertions in this UnitOfWork.
     */
    public function getScheduledInserts(): array
    {
        return $this->scheduledInserts;
    }

    /**
     * Gets the currently scheduled document moves in this UnitOfWork.
     */
    public function getScheduledMoves(): array
    {
        return $this->scheduledMoves;
    }

    /**
     * Gets the currently scheduled document reorders in this UnitOfWork.
     */
    public function getScheduledReorders(): array
    {
        return $this->scheduledReorders;
    }

    /**
     * Gets the currently scheduled document deletions in this UnitOfWork.
     */
    public function getScheduledRemovals(): array
    {
        return $this->scheduledRemovals;
    }

    /**
     * Process null values in an associative array so that they can be stored in phpcr.
     *
     * Stores keys and null fields in the node and returns the processed values
     */
    public function processAssoc(NodeInterface $node, array $mapping, array $assoc): array
    {
        $isNull = static function ($item) {
            return null === $item;
        };

        $isNotNull = static function ($item) {
            return null !== $item;
        };

        $keys = array_keys($assoc);
        $values = array_values(array_filter($assoc, $isNotNull));
        $nulls = array_keys(array_filter($assoc, $isNull));

        if (empty($keys)) {
            $this->removeAssoc($node, $mapping);
        } else {
            $node->setProperty($mapping['assoc'], $keys, PropertyType::STRING);
            $node->setProperty($mapping['assocNulls'], $nulls, PropertyType::STRING);
        }

        return $values;
    }

    /**
     * Create an associative array form the properties stored with the node.
     *
     * @param array $properties the node's properties
     * @param array $mapping    the field's mapping information
     */
    public function createAssoc(array $properties, array $mapping): array
    {
        $values = (array) $properties[$mapping['property']];
        if (!array_key_exists('assoc', $mapping) || null === $mapping['assoc'] || !array_key_exists($mapping['assoc'], $properties)) {
            return $values;
        }

        $keys = (array) $properties[$mapping['assoc']];
        $nulls = (array) ($properties[$mapping['assocNulls']] ?? []);

        // make sure we start with first value
        reset($values);
        $result = [];
        foreach ($keys as $key) {
            if (in_array($key, $nulls, true)) {
                $result[$key] = null;
            } else {
                $result[$key] = current($values);
                next($values);
            }
        }

        return $result;
    }

    /**
     * Remove an associative array form the properties stored with the node.
     */
    public function removeAssoc(NodeInterface $node, array $mapping): void
    {
        if ($node->hasProperty($mapping['assoc'])) {
            $node->getProperty($mapping['assoc'])->remove();
        }
        if ($node->hasProperty($mapping['assocNulls'])) {
            $node->getProperty($mapping['assocNulls'])->remove();
        }
    }

    /**
     * To invoke a global invent without using the ListenersInvoker.
     */
    public function invokeGlobalEvent(string $eventName, EventArgs $event): void
    {
        if ($this->eventManager->hasListeners($eventName)) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }

    /**
     * If the parent node has child restrictions, ensure that the given
     * class name is within them.
     */
    private function validateChildClass(NodeInterface $parentNode, ClassMetadata $class): void
    {
        $parentClass = $this->documentClassMapper->getClassName($this->dm, $parentNode);

        if (null === $parentClass) {
            return;
        }

        $metadata = $this->dm->getClassMetadata($parentClass);
        $metadata->assertValidChildClass($class);
    }
}
