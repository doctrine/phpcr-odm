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

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\OnClearEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Doctrine\ODM\PHPCR\Event\ListenersInvoker;
use Doctrine\ODM\PHPCR\Event\PreUpdateEventArgs;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;
use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Id\IdGenerator;
use Doctrine\ODM\PHPCR\Exception\CascadeException;
use Doctrine\ODM\PHPCR\Tools\Helper\PrefetchHelper;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationNodesWarmer;

use Iterator;
use PHPCR\RepositoryInterface;
use PHPCR\PropertyType;
use PHPCR\NodeInterface;
use PHPCR\RepositoryException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\PathNotFoundException;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\Util\UUIDHelper;
use PHPCR\Util\PathHelper;
use PHPCR\Util\NodeHelper;

use Jackalope\Session as JackalopeSession;

/**
 * Unit of work class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author      Brian King <brian@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class UnitOfWork
{
    const STATE_NEW = 1;
    const STATE_MANAGED = 2;
    const STATE_REMOVED = 3;
    const STATE_DETACHED = 4;

    /**
     * @var DocumentManager
     */
    private $dm = null;

    /**
     * @var array
     */
    private $identityMap = array();

    /**
     * @var array
     */
    private $documentIds = array();

    /**
     * Track version history of the version documents we create, indexed by spl_object_hash
     * @var \PHPCR\Version\VersionHistoryInterface[]
     */
    private $documentHistory = array();

    /**
     * Track version objects of the version documents we create, indexed by spl_object_hash
     * @var \PHPCR\Version\VersionInterface[]
     */
    private $documentVersion = array();

    /**
     * @var array
     */
    private $documentState = array();

    /**
     * Hashmap of spl_object_hash => locale => hashmap of all translated
     * document fields to store fields until the flush, in case the user is
     * using bindTranslation to store more than one locale in one flush.
     *
     * @var array
     */
    private $documentTranslations = array();

    /**
     * Hashmap of spl_object_hash => { original => locale , current => locale }
     * The original vs current locale is used to detect if the user changed the
     * mapped locale field of a document after the last call to bindTranslation
     *
     * @var array
     */
    private $documentLocales = array();

    /**
     * PHPCR always returns and updates the whole data of a document. If on update data is "missing"
     * this means the data is deleted. This also applies to attachments. This is why we need to ensure
     * that data that is not mapped is not lost. This map here saves all the "left-over" data and keeps
     * track of it if necessary.
     *
     * @var array
     */
    private $nonMappedData = array();

    /**
     * @var array
     */
    private $originalData = array();

    /**
     * @var array
     */
    private $originalTranslatedData = array();

    /**
     * @var array
     */
    private $documentChangesets = array();

    /**
     * List of documents that have a changed field to be updated on next flush
     * oid => document
     * @var array
     */
    private $scheduledUpdates = array();

    /**
     * List of documents that will be inserted on next flush
     * oid => document
     * @var array
     */
    private $scheduledInserts = array();

    /**
     * List of documents that will be moved on next flush
     * oid => array(document, target path)
     * @var array
     */
    private $scheduledMoves = array();

    /**
     * List of parent documents that have children that will be reordered on next flush
     * parent oid => list of array with records array(parent document, srcName, targetName, before) with
     * - parent document the document of the child to be reordered
     * - srcName the Nodename of the document to be moved,
     * - targetName the Nodename of the document to move srcName to
     * - before a boolean telling whether to move srcName before or after targetName
     *
     * @var array
     */
    private $scheduledReorders = array();

    /**
     * List of documents that will be removed on next flush
     * oid => document
     * @var array
     */
    private $scheduledRemovals = array();

    /**
     * @var array
     */
    private $visitedCollections = array();

    /**
     * @var array
     */
    private $changesetComputed = array();

    /**
     * @var IdGenerator
     */
    private $idGenerators = array();

    /**
     * Used to generate uuid when we need to build references before flushing.
     *
     * @var \Closure
     */
    private $uuidGenerator;

    /**
     * \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var Event\ListenersInvoker
     */
    private $eventListenersInvoker;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * @var DocumentClassMapperInterface
     */
    private $documentClassMapper;

    private $prefetchHelper;

    /**
     * @var boolean
     */
    private $validateDocumentName;

    /**
     * @var boolean
     */
    private $writeMetadata;

    /**
     * @var string
     */
    private $useFetchDepth;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->session = $dm->getPhpcrSession();
        $this->eventListenersInvoker = new ListenersInvoker($dm);
        $this->eventManager = $dm->getEventManager();

        $config = $dm->getConfiguration();
        $this->documentClassMapper = $config->getDocumentClassMapper();
        $this->validateDocumentName = $config->getValidateDoctrineMetadata();
        $this->writeMetadata = $config->getWriteDoctrineMetadata();
        $this->uuidGenerator = $config->getUuidGenerator();

        if ($this->session instanceof JackalopeSession) {
            $this->useFetchDepth = 'jackalope.fetch_depth';
        }
    }

    public function setPrefetchHelper($helper)
    {
        $this->prefetchHelper = $helper;
    }

    public function getPrefetchHelper()
    {
        if (!$this->prefetchHelper) {
            $this->prefetchHelper = new PrefetchHelper();
        }

        return $this->prefetchHelper;
    }

    /**
     * Validate if a document is of the specified class, if the global setting
     * to validate is activated.
     *
     * @param object      $document
     * @param string|null $className The class name $document must be
     *                               instanceof. Pass empty to not validate anything.
     *
     * @throws PHPCRException
     */
    public function validateClassName($document, $className)
    {
        if (isset($className) && $this->validateDocumentName) {
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
     * @param null|string   $className
     * @param NodeInterface $node
     * @param array         $hints
     *
     * @return object
     *
     * @throws PHPCRExceptionInterface if $className was specified and does not match
     *      the class of the document corresponding to $node.
     */
    public function getOrCreateDocument($className, NodeInterface $node, array &$hints = array())
    {
        $documents = $this->getOrCreateDocuments($className, array($node), $hints);

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
     * @param null|string $className
     * @param Iterator|array $nodes
     * @param array $hints
     *
     * @throws Exception\InvalidArgumentException
     * @throws PHPCRException
     * @return array
     */
    public function getOrCreateDocuments($className, $nodes, array &$hints = array())
    {
        $refresh = isset($hints['refresh']) ? $hints['refresh'] : false;
        $locale = isset($hints['locale']) ? $hints['locale'] : null;
        $fallback = isset($hints['fallback']) ? $hints['fallback'] : isset($locale);
        $documents = array();
        $overrideLocalValuesOids = array();
        $strategies = array();
        $nodesByStrategy = array();
        $allLocales = array();

        //prepare array of document ordered by the nodes path
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
                    $overrideLocalValuesOids[$id] = spl_object_hash($document);
                }

                try {
                    $this->validateClassName($document, $requestedClassName);
                } catch(ClassMismatchException $e) {
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
            $id       = $node->getPath();
            $document = $this->getDocumentById($id) ?: (isset($documents[$id]) ? $documents[$id] : null);

            if (! $document) {
                continue;
            }

            $documents[$id] = $document;
            $class          = $this->dm->getClassMetadata(get_class($document));

            $documentState = array();
            $nonMappedData = array();

            // second param is false to get uuid rather than dereference reference properties to node instances
            $properties = $node->getPropertiesValues(null, false);

            foreach ($class->fieldMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if (isset($properties[$mapping['property']])) {
                    if (true === $mapping['multivalue']) {
                        if (isset($mapping['assoc'])) {
                            $documentState[$fieldName] = $this->createAssoc($properties, $mapping);
                        } else {
                            $documentState[$fieldName] = (array) $properties[$mapping['property']];
                        }
                    } else {
                        $documentState[$fieldName] = $properties[$mapping['property']];
                    }
                } elseif (true === $mapping['multivalue']) {
                    $documentState[$mapping['property']] = array();
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

            if (! isset($hints['prefetch']) || $hints['prefetch']) {
                $this->getPrefetchHelper()->prefetchReferences($class, $node);
            }

            // initialize inverse side collections
            foreach ($class->referenceMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                    if (!$node->hasProperty($mapping['property'])) {
                        continue;
                    }

                    try {
                        $referencedNode = $node->getProperty($mapping['property'])->getNode();
                        $proxy = $this->getOrCreateProxyFromNode($referencedNode, $locale);
                        if (isset($mapping['targetDocument']) && !$proxy instanceof $mapping['targetDocument']) {
                            throw new PHPCRException("Unexpected class for referenced document at '{$referencedNode->getPath()}'. Expected '{$mapping['targetDocument']}' but got '".ClassUtils::getClass($proxy)."'.");
                        }
                    } catch (RepositoryException $e) {
                        if ($e instanceof ItemNotFoundException || isset($hints['ignoreHardReferenceNotFound'])) {
                            // a weak reference or an old version can have lost references
                            $proxy = null;
                        } else {
                            throw new PHPCRException($e->getMessage(), 0, $e);
                        }
                    }

                    $documentState[$fieldName] = $proxy;
                } elseif ($mapping['type'] === ClassMetadata::MANY_TO_MANY) {
                    $referencedNodes = array();
                    if ($node->hasProperty($mapping['property'])) {
                        foreach ($node->getProperty($mapping['property'])->getString() as $reference) {
                            $referencedNodes[] = $reference;
                        }
                    }

                    $targetDocument = isset($mapping['targetDocument']) ? $mapping['targetDocument'] : null;
                    $coll = new ReferenceManyCollection($this->dm, $document, $mapping['property'], $referencedNodes, $targetDocument, $locale);
                    $documentState[$fieldName] = $coll;
                }
            }

            if (! isset($hints['prefetch']) || $hints['prefetch']) {
                if ($class->translator) {
                    try {
                        $prefetchLocale = $locale ?: $this->dm->getLocaleChooserStrategy()->getLocale();
                    } catch (InvalidArgumentException $e) {
                        throw new InvalidArgumentException($e->getMessage() . ' but document ' . $class->name . ' is mapped with translations.');
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
            if (!isset($overrideLocalValuesOids[$id])) {
                continue;
            }

            if (!$overrideLocalValuesOids[$id]) {
                // registering the document needs to be delayed until the children proxies where created
                $overrideLocalValuesOids[$id] = $this->registerDocument($document, $id);
            }

            $this->nonMappedData[$overrideLocalValuesOids[$id]] = $nonMappedData;
            foreach ($class->reflFields as $fieldName => $reflFields) {
                $value = isset($documentState[$fieldName]) ? $documentState[$fieldName] : null;
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
     * Get the existing document or proxy or create a new one for this PHPCR Node
     *
     * @param NodeInterface $node
     * @param string        $locale
     *
     * @return object
     */
    public function getOrCreateProxyFromNode(NodeInterface $node, $locale = null)
    {
        $targetId = $node->getPath();
        $className = $this->documentClassMapper->getClassName($this->dm, $node);

        return $this->getOrCreateProxy($targetId, $className, $locale);
    }

    /**
     * Get the existing document or proxy for this id of this class, or create
     * a new one.
     *
     * @param string $targetId
     * @param string $className
     * @param string $locale
     *
     * @return object
     */
    public function getOrCreateProxy($targetId, $className, $locale = null)
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

        $metadata      = $this->dm->getClassMetadata($className);
        $proxyDocument = $this->dm->getProxyFactory()->getProxy($className, array($metadata->identifier => $targetId));

        // register the document under its own id
        $this->registerDocument($proxyDocument, $targetId);

        if ($locale) {
            $this->setLocale($proxyDocument, $this->dm->getClassMetadata($className), $locale);
        }

        return $proxyDocument;
    }

    /**
     * Populate the proxy with actual data
     *
     * @param string $className
     * @param Proxy  $document
     */
    public function refreshDocumentForProxy($className, Proxy $document)
    {
        $node = $this->session->getNode($this->determineDocumentId($document));

        $hints = array('refresh' => true, 'fallback' => true);

        $oid = spl_object_hash($document);
        if (isset($this->documentLocales[$oid]['current'])) {
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
    public function bindTranslation($document, $locale)
    {
        $state = $this->getDocumentState($document);
        if ($state !== self::STATE_MANAGED) {
            throw new InvalidArgumentException('Document has to be managed to be able to bind a translation '.self::objToStr($document, $this->dm));
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($class)) {
            throw new PHPCRException('This document is not translatable, do not use bindTranslation: '.self::objToStr($document, $this->dm));
        }

        if ($this->getCurrentLocale($document) != $locale
            && false !== array_search($locale, $this->getLocalesFor($document))
        ) {
            throw new RuntimeException(sprintf(
                'Translation "%s" already exists for "%s". First load this translation if you want to change it, or remove the existing translation.',
                $locale,
                self::objToStr($document, $this->dm)
            ));
        }

        $this->doBindTranslation($document, $locale, $class);
    }

    /**
     * @param object        $document
     * @param string        $locale
     * @param ClassMetadata $class
     */
    private function doBindTranslation($document, $locale, ClassMetadata $class)
    {
        $oid = spl_object_hash($document);

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
        }

        $this->setLocale($document, $class, $locale);

        foreach ($class->translatableFields as $field) {
            $this->documentTranslations[$oid][$locale][$field] = $class->reflFields[$field]->getValue($document);
        }
    }

    /**
     * Schedule insertion of this document and cascade if necessary.
     *
     * @param object $document
     */
    public function scheduleInsert($document)
    {
        $visited = array();
        $this->doScheduleInsert($document, $visited);
    }

    private function doScheduleInsert($document, &$visited, $overrideIdGenerator = null)
    {
        if (!is_object($document)) {
            throw new PHPCRException(sprintf(
                'Expected a mapped object, found <%s>',
                gettype($document)
            ));
        }

        $oid = spl_object_hash($document);
        // To avoid recursion loops (over children and parents)
        if (isset($visited[$oid])) {
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
                $this->persistNew($class, $document, $overrideIdGenerator);
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
        }

        $this->cascadeScheduleInsert($class, $document, $visited);
    }

    /**
     *
     * @param ClassMetadata $class
     * @param object        $document
     * @param array         $visited
     */
    private function cascadeScheduleInsert($class, $document, &$visited)
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST)) {
                continue;
            }

            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related !== null) {
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

                    if ($this->getDocumentState($related) === self::STATE_NEW) {
                        $this->doScheduleInsert($related, $visited);
                    }
                } else {
                    if (!is_array($related) && !$related instanceof Collection) {
                        throw new PHPCRException('Referenced documents are not stored correctly in a reference-many property. Use array notation or a (ReferenceMany)Collection: '.self::objToStr($document, $this->dm));
                    }
                    foreach ($related as $relatedDocument) {
                        if (!isset($relatedDocument)) {
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
                        if ($this->getDocumentState($relatedDocument) === self::STATE_NEW) {
                            $this->doScheduleInsert($relatedDocument, $visited);
                        }
                    }
                }
            }
        }
    }

    private function cascadeScheduleParentInsert($class, $document, &$visited)
    {
        if ($class->parentMapping) {
            $parent = $class->reflFields[$class->parentMapping]->getValue($document);
            if ($parent !== null && $this->getDocumentState($parent) === self::STATE_NEW) {
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

    /**
     * @param string $type the id generator type
     *
     * @return IdGenerator
     */
    private function getIdGenerator($type)
    {
        if (!isset($this->idGenerators[$type])) {
            $this->idGenerators[$type] = IdGenerator::create($type);
        }

        return $this->idGenerators[$type];
    }

    public function scheduleMove($document, $targetPath)
    {
        $oid = spl_object_hash($document);

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

        $this->scheduledMoves[$oid] = array($document, $targetPath);
        $this->setDocumentState($oid, self::STATE_MANAGED);
    }

    public function scheduleReorder($document, $srcName, $targetName, $before)
    {
        $oid = spl_object_hash($document);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_REMOVED:
                throw new InvalidArgumentException('Removed document passed to reorder(): '.self::objToStr($document, $this->dm));
            case self::STATE_DETACHED:
                throw new InvalidArgumentException('Detached document passed to reorder(): '.self::objToStr($document, $this->dm));
        }

        if (! isset($this->scheduledReorders[$oid])) {
            $this->scheduledReorders[$oid] = array();
        }
        $this->scheduledReorders[$oid][] = array($document, $srcName, $targetName, $before);
    }

    public function scheduleRemove($document)
    {
        $visited = array();
        $this->doRemove($document, $visited);
    }

    private function doRemove($document, &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }
        $visited[$oid] = true;

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                unset($this->scheduledInserts[$oid]);
                break;
            case self::STATE_MANAGED:
                unset($this->scheduledMoves[$oid]);
                unset($this->scheduledReorders[$oid]);
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

    private function cascadeRemove(ClassMetadata $class, $document, &$visited)
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_REMOVE)) {
                continue;
            }

            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                // If its a PersistentCollection initialization is intended! No unwrap!
                foreach ($related as $relatedDocument) {
                    $this->doRemove($relatedDocument, $visited);
                }
            } elseif ($related !== null) {
                $this->doRemove($related, $visited);
            }
        }

        // remove is cascaded to children automatically on PHPCR level
    }

    /**
     * recurse over all known child documents to remove them form this unit of work
     * as their parent gets removed from phpcr. If you do not, flush will try to create
     * orphaned nodes if these documents are modified which leads to a PHPCR exception
     *
     * @param object $document
     */
    private function purgeChildren($document)
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->childMappings as $fieldName) {
            $child = $class->reflFields[$fieldName]->getValue($document);
            if ($child !== null) {
                $this->purgeChildren($child);
                $this->unregisterDocument($child);
            }
        }
    }

    /**
     * @param object|string $document document instance or document object hash
     * @param int           $state
     */
    private function setDocumentState($document, $state)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        $this->documentState[$oid] = $state;
    }

    /**
     * Get the state of this document
     *
     * STATE_NEW:      the document is not persisted, but a valid mapped document
     * STATE_MANAGED:  the document is tracked and will be updated on flush
     * STATE_REMOVED:  the document is scheduled for removal
     * STATE_DETACHED: there is a corresponding Node in storage, but this document is not bound to it
     *
     * @param object $document the document to get the state of
     *
     * @return int one of the STATE_* constants of this class
     */
    public function getDocumentState($document)
    {
        $oid = spl_object_hash($document);
        if (!isset($this->documentState[$oid])) {
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

    /**
     * Checks whether a document is scheduled for insertion.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isScheduledForInsert($document)
    {
        return isset($this->scheduledInserts[spl_object_hash($document)]);
    }

    /**
     * Detects the changes for a single document
     *
     * @param object $document
     */
    public function computeSingleDocumentChangeSet($document)
    {
        $state = $this->getDocumentState($document);

        if ($state !== self::STATE_MANAGED && $state !== self::STATE_REMOVED) {
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

        $oid = spl_object_hash($document);
        if (!isset($this->scheduledInserts[$oid])) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $this->computeChangeSet($class, $document);
        }
    }

    /**
     * Detects the changes that need to be persisted
     */
    public function computeChangeSets()
    {
        foreach ($this->identityMap as $document) {
            $state = $this->getDocumentState($document);
            if ($state === self::STATE_MANAGED) {
                $class = $this->dm->getClassMetadata(get_class($document));
                $this->computeChangeSet($class, $document);
            }
        }
    }

    /**
     * Get a documents actual data, flattening all the objects to arrays.
     *
     * @param ClassMetadata $class
     * @param object        $document
     *
     * @return array
     */
    private function getDocumentActualData(ClassMetadata $class, $document)
    {
        $actualData = array();
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            // do not set the version info fields if they have values, they are not to be managed by the user in write scenarios.
            if ($fieldName === $class->versionNameField
                || $fieldName === $class->versionCreatedField
            ) {
                continue;
            }
            $value = $reflProperty->getValue($document);
            $actualData[$fieldName] = $value;
        }

        return $actualData;
    }

    /**
     * Determine the nodename of a child in a children list.
     *
     * @param string $parentId Id of the parent document
     * @param string $nodename Name to use if we can't determine the node name otherwise.
     * @param object $child    The child document
     * @param object $parent   The parent document
     *
     * @return mixed|string
     *
     * @throws PHPCRException
     */
    private function getChildNodename($parentId, $nodename, $child, $parent)
    {
        $childClass = $this->dm->getClassMetadata(get_class($child));
        if ($childClass->nodename && $childClass->reflFields[$childClass->nodename]->getValue($child)) {
            $nodename = $childClass->reflFields[$childClass->nodename]->getValue($child);

            if ($exception = $childClass->isValidNodename($nodename)) {
                throw IdException::illegalName($child, $childClass->nodename, $nodename);
            }
        } else {
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
        }

        return $nodename;
    }

    /**
     * @param object $document
     * @param string $class
     * @param string $oid
     * @param boolean $isNew
     * @param array $changeSet
     * @param string $assocType
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @throws PHPCRException
     */
    private function computeAssociationChanges($document, $class, $oid, $isNew, $changeSet, $assocType)
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
                        $changeSet[$fieldName] = array();
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
                            $targetDocument = isset($mapping['targetDocument']) ? $mapping['targetDocument'] : null;
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

                $coid = spl_object_hash($changeSet[$fieldName]);
                $this->visitedCollections[$coid] = $changeSet[$fieldName];

                foreach ($changeSet[$fieldName] as $association) {
                    if ($association !== null) {
                        $this->$computeMethod($mapping, $association);
                    }
                }

                if (!$isNew && $mapping['cascade'] & ClassMetadata::CASCADE_REMOVE) {
                    if (!$this->originalData[$oid][$fieldName] instanceof PersistentCollection) {
                        throw new RuntimeException("OriginalData for a collection association contains something else than a PersistentCollection.");
                    }

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

    /**
     * @param object $document
     * @param string $class
     * @param string $oid
     * @param boolean $isNew
     * @param array $changeSet
     * @throws Exception\RuntimeException
     * @throws PHPCRException
     */
    private function computeChildrenChanges($document, $class, $oid, $isNew, $changeSet)
    {
        $id = $this->getDocumentId($document, false);

        foreach ($class->childrenMappings as $fieldName) {
            if ($changeSet[$fieldName] instanceof PersistentCollection) {
                if (!$changeSet[$fieldName]->isInitialized()) {
                    continue;
                }
            } else {
                if (null === $changeSet[$fieldName]) {
                    $changeSet[$fieldName] = array();
                }

                if (!is_array($changeSet[$fieldName]) && !$changeSet[$fieldName] instanceof Collection) {
                    throw PHPCRException::childrenFieldNoArray(
                        self::objToStr($document, $this->dm),
                        $fieldName
                    );
                }

                $filter = isset($mapping['filter']) ? $mapping['filter'] : null;
                $fetchDepth = isset($mapping['fetchDepth']) ? $mapping['fetchDepth'] : null;

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
            $childNames = $movedChildNames = array();

            $coid = spl_object_hash($changeSet[$fieldName]);
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
                throw new RuntimeException("OriginalData for a children association contains something else than a ChildrenCollection.");
            }

            $this->originalData[$oid][$fieldName]->initialize();
            $originalNames = $this->originalData[$oid][$fieldName]->getOriginalNodenames();
            foreach ($originalNames as $key => $childName) {
                // check moved children to not accidentally remove a child that simply moved away.
                if (!(in_array($childName, $childNames) || in_array($childName, $movedChildNames))) {
                    $child = $this->getDocumentById($id.'/'.$childName);
                    $this->scheduleRemove($child);
                    unset($originalNames[$key]);
                }
            }

            if (!empty($childNames) && isset($originalNames)) {
                // reindex the arrays to avoid holes in the indexes
                $originalNames = array_values($originalNames);
                $originalNames = array_merge($originalNames, array_diff($childNames, $originalNames));
                if ($originalNames !== $childNames) {
                    $reordering = NodeHelper::calculateOrderBefore($originalNames, $childNames);
                    if (empty($this->documentChangesets[$oid])) {
                        $this->documentChangesets[$oid] = array('reorderings' => array($reordering));
                    } else {
                        $this->documentChangesets[$oid]['reorderings'][] = $reordering;
                    }

                    $this->scheduledUpdates[$oid] = $document;
                } elseif (empty($this->documentChangesets[$oid]['fields'])) {
                    unset($this->documentChangesets[$oid]);
                    unset($this->scheduledUpdates[$oid]);
                } else {
                    $this->documentChangesets[$oid]['reorderings'] = array();
                }
            }
        }
    }

    /**
     * Computes changeset for a given document.
     *
     * @param ClassMetadata $class
     * @param object        $document
     */
    public function computeChangeSet(ClassMetadata $class, $document)
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $oid = spl_object_hash($document);
        if (in_array($oid, $this->changesetComputed)) {
            return;
        }

        $this->changesetComputed[] = $oid;

        $changeSet = $actualData = $this->getDocumentActualData($class, $document);
        $id = $this->getDocumentId($document, false);
        $isNew = !isset($this->originalData[$oid]);

        if ($isNew) {
            // Document is New and should be inserted
            $this->originalData[$oid] = $changeSet;
        } elseif (!empty($this->documentChangesets[$oid]['fields'])) {
            foreach ($this->documentChangesets[$oid]['fields'] as $fieldName => $data) {
                $this->originalData[$oid][$fieldName] = $data[0];
            }
        }

        if ($class->parentMapping && isset($changeSet[$class->parentMapping])) {
            $parent = $changeSet[$class->parentMapping];
            $parentClass = $this->dm->getClassMetadata(get_class($parent));
            $state = $this->getDocumentState($parent);

            if ($state === self::STATE_MANAGED) {
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

            if (isset($this->originalData[$oid][$class->parentMapping])
                && isset($changeSet[$class->parentMapping])
                && $this->originalData[$oid][$class->parentMapping] !== $changeSet[$class->parentMapping]
            ) {
                $destPath = $this->getDocumentId($changeSet[$class->parentMapping]);
            }

            if (isset($this->originalData[$oid][$class->nodename])
                && isset($changeSet[$class->nodename])
                && $this->originalData[$oid][$class->nodename] !== $changeSet[$class->nodename]
            ) {
                $destName = $changeSet[$class->nodename];
            }

            // there was assignment move
            if ($destPath || $destName) {
                // add the other field if only one was changed
                if (false === $destPath) {
                    $destPath = isset($changeSet[$class->parentMapping])
                        ? $this->getDocumentId($changeSet[$class->parentMapping])
                        : PathHelper::getParentPath($this->getDocumentId($document));
                }
                if (false === $destName) {
                    $destName = $class->nodename !== null && $changeSet[$class->nodename]
                        ? $changeSet[$class->nodename]
                        : PathHelper::getNodeName($this->getDocumentId($document));
                }

                // make sure destination nodename is okay
                if ($exception = $class->isValidNodename($destName)) {
                    throw IdException::illegalName($document, $class->nodename, $destName);
                }

                // prevent path from becoming "//foobar" when moving to root node.
                $targetPath = ('/' == $destPath) ? "/$destName" : "$destPath/$destName";

                $this->scheduleMove($document, $targetPath);
            }

            if (isset($this->originalData[$oid][$class->identifier])
                && isset($changeSet[$class->identifier])
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
            $oid = spl_object_hash($document);
            if (isset($this->documentTranslations[$oid])) {
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
                $fields[$fieldName] = array($this->originalData[$oid][$fieldName], $fieldValue);
            } else {
                unset($fields[$fieldName]);
            }
        }

        if (!empty($fields) || $translationChanges) {
            $this->documentChangesets[$oid]['fields'] = $fields;
            $this->originalData[$oid] = $actualData;
            $this->scheduledUpdates[$oid] = $document;
        } elseif (empty($this->documentChangesets[$oid]['reorderings'])) {
            unset($this->documentChangesets[$oid]);
            unset($this->scheduledUpdates[$oid]);
        } else {
            $this->documentChangesets[$oid]['fields'] = array();
        }
    }

    /**
     * Computes the changes of a child.
     *
     * @param array  $mapping  the mapping data
     * @param mixed  $child    the child document.
     * @param string $parentId the id of the parent document
     * @param string $nodename the name of the node as specified by the mapping
     * @param mixed  $parent   the parent document
     *
     * @return object the child instance (if we are replacing a child this can be a different instance than was originally provided)
     */
    private function computeChildChanges($mapping, $child, $parentId, $nodename, $parent = null)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($child));
        $state = $this->getDocumentState($child);

        switch ($state) {
            case self::STATE_NEW:
                // cascade persist is implicit on children, no check for cascading

                // check if we have conflicting nodename information on creation.
                if ($targetClass->nodename) {
                    $assignedName = $targetClass->getFieldValue($child, $targetClass->nodename);
                    if ($assignedName && $assignedName != $nodename) {
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
                    $this->persistNew($targetClass, $child, ClassMetadata::GENERATOR_TYPE_ASSIGNED, $parent);
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

    /**
     * Computes the changes of a reference.
     *
     * @param array $mapping   the mapping data
     * @param mixed $reference the referenced document.
     */
    private function computeReferenceChanges($mapping, $reference)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($reference));
        $state = $this->getDocumentState($reference);

        switch ($state) {
            case self::STATE_NEW:
                if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST) ) {
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
     * Computes the changes of a referrer.
     *
     * @param array $mapping  the mapping data
     * @param mixed $referrer the referenced document.
     */
    private function computeReferrerChanges($mapping, $referrer)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($referrer));
        $state = $this->getDocumentState($referrer);

        switch ($state) {
            case self::STATE_NEW:
                if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST) ) {
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
     * @param ClassMetadata $class
     * @param object        $document
     */
    public function persistNew(ClassMetadata $class, $document, $overrideIdGenerator = null, $parent = null)
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

        $generator = $this->getIdGenerator($overrideIdGenerator ? $overrideIdGenerator : $class->idGenerator);
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

    public function refresh($document)
    {
        $this->session->refresh(true);
        $visited = array();
        $this->doRefresh($document, $visited);
    }

    private function doRefresh($document, &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }
        $visited[$oid] = true;

        if ($this->getDocumentState($document) !== self::STATE_MANAGED) {
            throw new InvalidArgumentException('Document has to be managed to be refreshed '.self::objToStr($document, $this->dm));
        }

        $node = $this->session->getNode($this->getDocumentId($document));

        $class = $this->dm->getClassMetadata(get_class($document));
        $this->cascadeRefresh($class, $document, $visited);

        $hints = array('refresh' => true);
        $this->getOrCreateDocument(ClassUtils::getClass($document), $node, $hints);
    }

    public function merge($document)
    {
        $visited = array();

        return $this->doMerge($document, $visited);
    }

    private function doMergeSingleDocumentProperty($managedCopy, $document, \ReflectionProperty $prop, array $mapping)
    {
        if (null === $document) {
            $prop->setValue($managedCopy, null);
        } elseif (!($mapping['cascade'] & ClassMetadata::CASCADE_MERGE)) {
            if ($this->getDocumentState($document) == self::STATE_MANAGED) {
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

    private function cascadeMergeCollection($managedCol, array $mapping)
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

    private function doMerge($document, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return $document; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));
        $locale = $this->getCurrentLocale($document, $class);

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED entities are ignored by the merge operation.
        if ($this->getDocumentState($document) == self::STATE_MANAGED) {
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
                    if ($this->getDocumentState($managedCopy) == self::STATE_REMOVED) {
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

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed document was not found.
                    if ($class->idGenerator !== ClassMetadata::GENERATOR_TYPE_ASSIGNED) {
                        throw new InvalidArgumentException("Document not found in merge operation: $id");
                    }

                    $managedCopy = $class->newInstance();
                    $class->setIdentifierValue($managedCopy, $id);
                    $persist = true;
                }
            }

            $managedOid = spl_object_hash($managedCopy);

            // Merge state of $document into existing (managed) document
            foreach ($class->reflFields as $fieldName => $prop) {
                $other = $prop->getValue($document);
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
                            array(),
                            isset($mapping['targetDocument']) ? $mapping['targetDocument'] : null,
                            $locale
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
                } elseif (in_array($mapping['type'], array('locale', 'versionname', 'versioncreated', 'node', 'nodename'))) {
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

        if ($prevManagedCopy !== null) {
            $prevClass = $this->dm->getClassMetadata(get_class($prevManagedCopy));
            if ($assoc['type'] == ClassMetadata::MANY_TO_ONE) {
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
     *
     * @param ClassMetadata $class
     * @param object        $document
     * @param object        $managedCopy
     * @param array         $visited
     */
    private function cascadeMerge(ClassMetadata $class, $document, $managedCopy, array &$visited)
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_MERGE)) {
                continue;
            }
            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                if ($related instanceof PersistentCollection) {
                    // Unwrap so that foreach () does not initialize
                    $related = $related->unwrap();
                }
                foreach ($related as $relatedDocument) {
                    $this->doMerge($relatedDocument, $visited, $managedCopy, $mapping);
                }
            } elseif ($related !== null) {
                $this->doMerge($related, $visited, $managedCopy, $mapping);
            }
        }
    }

    /**
     * Detaches a document from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $document The document to detach.
     */
    public function detach($document)
    {
        $visited = array();
        $this->doDetach($document, $visited);
    }

    /**
     * Executes a detach operation on the given document.
     *
     * @param object $document
     * @param array  $visited
     */
    private function doDetach($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
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

    private function cascadeRefresh(ClassMetadata $class, $document, &$visited)
    {
        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_REFRESH)) {
                continue;
            }

            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                if ($related instanceof PersistentCollection) {
                    // Unwrap so that foreach () does not initialize
                    $related = $related->unwrap();
                }
                foreach ($related as $relatedDocument) {
                    $this->doRefresh($relatedDocument, $visited);
                }
            } elseif ($related !== null) {
                $this->doRefresh($related, $visited);
            }
        }
    }

    /**
     * Cascades a detach operation to associated documents.
     *
     * @param object $document
     * @param array  $visited
     */
    private function cascadeDetach(ClassMetadata $class, $document, array &$visited)
    {
        foreach ($class->childrenMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_DETACH)) {
                continue;
            }
            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                foreach ($related as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } elseif ($related !== null) {
                $this->doDetach($related, $visited);
            }
        }

        foreach (array_merge($class->referenceMappings, $class->referrersMappings) as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!($mapping['cascade'] & ClassMetadata::CASCADE_DETACH)) {
                continue;
            }
            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                foreach ($related as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } elseif ($related !== null) {
                $this->doDetach($related, $visited);
            }
        }

        // no cascade for mixed referrers
    }

    /**
     * Commits the UnitOfWork
     *
     * @param object|array|null $document optionally limit to a specific
     *                                    document or an array of documents
     */
    public function commit($document = null)
    {
        $this->invokeGlobalEvent(Event::preFlush, new ManagerEventArgs($this->dm));

        if ($document === null) {
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
            $this->changesetComputed = array();

            // @deprecated This is to maintain BC with the old behavior, where users may call
            //             flush instead of PHPCR\SessionInterface#save
            $this->session->save();

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
                //TODO: log error while closing dm after error: $innerException->getMessage
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
            $documents = is_array($document) ? $document : array($document);
            foreach ($documents as $doc) {
                $oid = spl_object_hash($doc);
                if (isset($this->documentLocales[$oid])) {
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
        $this->changesetComputed = array();

        $this->invokeGlobalEvent(Event::endFlush, new ManagerEventArgs($this->dm));
    }

    /**
     * Executes all document insertions
     *
     * @param array $documents array of all to be inserted documents
     */
    private function executeInserts($documents)
    {
        // sort the documents to insert parents first but maintain child order
        $oids = array();
        foreach ($documents as $oid => $document) {
            if (!$this->contains($oid)) {
                continue;
            }

            $oids[$oid] = $this->getDocumentId($document);
        }

        $order = array_flip(array_values($oids));
        uasort($oids, function ($a, $b) use ($order) {
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

        $associationChangesets = $associationUpdates = array();

        foreach ($oids as $oid => $id) {
            $document = $documents[$oid];
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
            if ($class->parentMapping && !$class->reflFields[$class->parentMapping]->getValue($document)) {
                $class->reflFields[$class->parentMapping]->setValue($document, $this->getOrCreateProxyFromNode($parentNode, $this->getCurrentLocale($document, $class)));
            }

            if ($this->writeMetadata) {
                $this->documentClassMapper->writeMetadata($this->dm, $node, $class->name);
            }

            $this->setMixins($class, $node, $document);

            $fields = isset($this->documentChangesets[$oid]['fields']) ? $this->documentChangesets[$oid]['fields'] : array();
            foreach ($fields as $fieldName => $fieldValue) {
                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields)) {
                    continue;
                }

                if (in_array($fieldName, $class->fieldMappings)) {
                    $mapping = $class->mappings[$fieldName];
                    $type = PropertyType::valueFromName($mapping['type']);
                    if (null === $fieldValue) {
                        continue;
                    }

                    if ($mapping['multivalue'] && $fieldValue) {
                        $fieldValue = (array) $fieldValue;
                        if (isset($mapping['assoc'])) {
                            $fieldValue = $this->processAssoc($node, $mapping, $fieldValue);
                        }
                    }

                    $node->setProperty($mapping['property'], $fieldValue, $type);
                } elseif (in_array($fieldName, $class->referenceMappings) || in_array($fieldName, $class->referrersMappings)) {
                    $associationUpdates[$oid] = $document;

                    //populate $associationChangesets to force executeUpdates($associationUpdates)
                    //to only update association fields
                    $data = isset($associationChangesets[$oid]['fields']) ? $associationChangesets[$oid]['fields'] : array();
                    $data[$fieldName] = array(null, $fieldValue);
                    $associationChangesets[$oid] = array('fields' => $data);
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
     *
     * @param ClassMetadata $class
     * @param string        $fieldName
     *
     * @return boolean
     */
    private function isAutocreatedProperty(ClassMetadata $class, $fieldName)
    {
        $field = $class->getField($fieldName);
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
     * Executes all document updates
     *
     * @param array   $documents      array of all to be updated documents
     * @param boolean $dispatchEvents if to dispatch events
     */
    private function executeUpdates($documents, $dispatchEvents = true)
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
                    $this->changesetComputed = array_diff($this->changesetComputed, array($oid));
                    $this->computeChangeSet($class, $document);
                }
            }

            $fields = isset($this->documentChangesets[$oid]['fields']) ? $this->documentChangesets[$oid]['fields'] : array();
            foreach ($fields as $fieldName => $data) {
                $fieldValue = $data[1];

                // PHPCR does not validate nullable unless we would start to
                // generate custom node types, which we at the moment don't.
                // the ORM can delegate this validation to the relational database
                // that is using a strict schema.
                // do this after the preUpdate events to give listener a last
                // chance to provide values
                if (null === $fieldValue
                    && in_array($fieldName, $class->fieldMappings) // only care about non-virtual fields
                    && !$class->isNullable($fieldName)
                    && !$this->isAutocreatedProperty($class, $fieldName)
                ) {
                    throw new PHPCRException(sprintf('Field "%s" of class "%s" is not nullable', $fieldName, $class->name));
                }

                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields)) {
                    continue;
                }

                $mapping = $class->mappings[$fieldName];
                if (in_array($fieldName, $class->fieldMappings)) {
                    $type = PropertyType::valueFromName($mapping['type']);
                    if ($mapping['multivalue']) {
                        $value = empty($fieldValue) ? null : ($fieldValue instanceof Collection ? $fieldValue->toArray() : $fieldValue);
                        if ($value && isset($mapping['assoc'])) {
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
                    if ($node->hasProperty($mapping['property']) && is_null($fieldValue)) {
                        $node->getProperty($mapping['property'])->remove();
                        if (isset($mapping['assoc'])) {
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
                        if (isset($fieldValue)) {
                            $refNodesIds = array();
                            foreach ($fieldValue as $fv) {
                                if ($fv === null) {
                                    continue;
                                }

                                $associatedNode = $this->session->getNode($this->getDocumentId($fv));
                                if ($strategy === PropertyType::PATH) {
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
                        if (isset($fieldValue)) {
                            $associatedNode = $this->session->getNode($this->getDocumentId($fieldValue));

                            if ($strategy === PropertyType::PATH) {
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
                    if (isset($fieldValue)) {

                        /*
                         * each document in referrers field is supposed to
                         * reference this document, so we have to update its
                         * referencing property to contain the uuid of this
                         * document
                         */
                        foreach ($fieldValue as $fv) {
                            if ($fv === null) {
                                continue;
                            }

                            if (! $fv instanceof $mapping['referringDocument']) {
                                throw new PHPCRException(sprintf("%s is not an instance of %s for document %s field %s", self::objToStr($fv, $this->dm), $mapping['referencedBy'], self::objToStr($document, $this->dm), $mapping['fieldName']));
                            }

                            $referencingNode = $this->session->getNode($this->getDocumentId($fv));
                            $referencingMeta = $this->dm->getClassMetadata($mapping['referringDocument']);
                            $referencingField = $referencingMeta->getAssociation($mapping['referencedBy']);

                            $uuid = $node->getIdentifier();
                            $strategy = $referencingField['strategy'] == 'weak' ? PropertyType::WEAKREFERENCE : PropertyType::REFERENCE;
                            switch ($referencingField['type']) {
                                case ClassMetadata::MANY_TO_ONE:
                                    $ref = $referencingMeta->getFieldValue($fv, $referencingField['fieldName']);
                                    if ($ref !== null && $ref !== $document) {
                                        throw new PHPCRException(sprintf('Conflicting settings for referrer and reference: Document %s field %s points to %s but document %s has set first document as referrer on field %s', self::objToStr($fv, $this->dm), $referencingField['fieldName'], self::objToStr($ref, $this->dm), self::objToStr($document, $this->dm), $mapping['fieldName']));
                                    }
                                    // update the referencing document field to point to this document
                                    $referencingMeta->setFieldValue($fv, $referencingField['fieldName'], $document);
                                    // and make sure the reference is not deleted in this change because the field could be null
                                    unset($this->documentChangesets[spl_object_hash($fv)]['fields'][$referencingField['fieldName']]);
                                    // store the change in PHPCR
                                    $referencingNode->setProperty($referencingField['property'], $uuid, $strategy);
                                    break;
                                case ClassMetadata::MANY_TO_MANY:
                                    /** @var $collection ReferenceManyCollection */
                                    $collection = $referencingMeta->getFieldValue($fv, $referencingField['fieldName']);
                                    if ($collection instanceof PersistentCollection && $collection->isDirty()) {
                                        throw new PHPCRException(sprintf('You may not modify the reference and referrer collections of interlinked documents as this is ambiguous. Reference %s on document %s and referrers %s on document %s are both modified', self::objToStr($fv, $this->dm), $referencingField['fieldName'], self::objToStr($document, $this->dm), $mapping['fieldName']));
                                    }
                                    if ($collection) {
                                        // make sure the reference is not deleted in this change because the field could be null
                                        unset($this->documentChangesets[spl_object_hash($fv)]['fields'][$referencingField['fieldName']]);
                                    } else {
                                        $collection = new ReferenceManyCollection($this->dm, $fv, $referencingField['property'], array($node), $class->name);
                                        $referencingMeta->setFieldValue($fv, $referencingField['fieldName'], $collection);
                                    }

                                    if ($referencingNode->hasProperty($referencingField['property'])) {
                                        if (!in_array($uuid, $referencingNode->getProperty($referencingField['property'])->getString())) {
                                            if (!$collection instanceof PersistentCollection || !$collection->isDirty()) {
                                                // update the reference collection: add us to it
                                                $collection->add($document);
                                            }
                                            // store the change in PHPCR
                                            $referencingNode->getProperty($referencingField['property'])->addValue($uuid); // property should be correct type already
                                        }
                                    } else {
                                        // store the change in PHPCR
                                        $referencingNode->setProperty($referencingField['property'], array($uuid), $strategy);
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
                    if ($fieldValue === null && $node->hasNode($mapping['nodeName'])) {
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
     * Executes all document moves
     *
     * @param array $documents array of all to be moved documents
     */
    private function executeMoves($documents)
    {
        foreach ($documents as $oid => $value) {
            if (!$this->contains($oid)) {
                continue;
            }

            list($document, $targetPath) = $value;

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

            $this->session->move($sourcePath, $targetPath);

            // update fields nodename and parentMapping if they exist in this type
            $node = $this->session->getNode($targetPath); // get node from session, document class might not map it
            if ($class->nodename) {
                $class->setFieldValue($document, $class->nodename, $node->getName());
            }

            if ($class->parentMapping) {
                $class->setFieldValue($document, $class->parentMapping, $this->getOrCreateProxyFromNode($node->getParent(), $this->getCurrentLocale($document, $class)));
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
                    if (! $child instanceof Proxy || $child->__isInitialized()) {
                        $this->originalData[$oid][$childClass->identifier] = $newId;
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
     * Execute reorderings
     *
     * @param $documents
     */
    private function executeReorders($documents)
    {
        foreach ($documents as $oid => $list) {
            if (!$this->contains($oid)) {
                continue;
            }
            foreach ($list as $value) {
                list($parent, $src, $target, $before) = $value;
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
                            $children = $class->reflFields[$fieldName]->getValue($parent);
                            $children->refresh();
                        }
                    }
                }
            }
        }
    }

    /**
     * Executes all document removals
     *
     * @param array $documents array of all to be removed documents
     */
    private function executeRemovals($documents)
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
    public function findVersionByName($className, $id, $versionName)
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

        $hints = array('versionName' => $versionName, 'ignoreHardReferenceNotFound' => true);
        $frozenDocument = $this->getOrCreateDocument($className, $node, $hints);
        $this->dm->detach($frozenDocument);

        $oid = spl_object_hash($frozenDocument);
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
    public function checkin($document)
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkin($path); // Checkin Node aka make a new Version
    }

    /**
     * Check out operation - Save all current changes and then check out the
     * Node by path.
     */
    public function checkout($document)
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkout($path);
    }

    /**
     * Create a version of the document and check it out right again to
     * continue editing.
     */
    public function checkpoint($document)
    {
        $path = $this->getVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkpoint($path);
    }

    /**
     * Get the version history information for a document
     *
     * TODO: implement labels once jackalope implements them, until then labels will be an empty array.
     * TODO: implement limit
     *
     * @param object $document the document of which to get the version history
     * @param int    $limit    an optional limit to only get the latest $limit information
     *
     * @return array of <versionname> => array("name" => <versionname>, "labels" => <array of labels>, "created" => <DateTime>)
     *               oldest version first
     */
    public function getAllLinearVersions($document, $limit = -1)
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

        $result = array();
        foreach ($versions as $version) {
            /** @var $version \PHPCR\Version\VersionInterface */
            $result[$version->getName()] = array(
                'name' => $version->getName(),
                'labels' => array(),
                'created' => $version->getCreated(),
            );
        }

        return $result;
    }

    /**
     * Restore the document to the state it was before
     *
     * @param string  $documentVersion the version name to restore
     * @param boolean $removeExisting  how to handle identifier collisions
     *
     * @see VersionManager::restore
     */
    public function restoreVersion($documentVersion, $removeExisting)
    {
        $oid = spl_object_hash($documentVersion);
        $history = $this->documentHistory[$oid];
        $version = $this->documentVersion[$oid];
        $document = $this->dm->find(null, $history->getVersionableIdentifier());
        $vm = $this->session->getWorkspace()->getVersionManager();

        $vm->restore($removeExisting, $version);
        $this->dm->refresh($document);
    }

    /**
     * Delete an old version of a document
     *
     * @param string $documentVersion the version name
     */
    public function removeVersion($documentVersion)
    {
        $oid = spl_object_hash($documentVersion);
        $history = $this->documentHistory[$oid];
        $version = $this->documentVersion[$oid];

        $history->removeVersion($version->getName());

        unset($this->documentVersion[$oid]);
        unset($this->documentHistory[$oid]);
    }

    /**
     * Removes a document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @param object $document
     */
    private function unregisterDocument($document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->documentIds[$oid])) {
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

        $this->changesetComputed = array_diff($this->changesetComputed, array($oid));
    }

    /**
     * @param object $document
     * @param string $id       The document id to look for.
     *
     * @return string generated object hash
     */
    public function registerDocument($document, $id)
    {
        $oid = spl_object_hash($document);
        $this->documentIds[$oid] = $id;
        $this->identityMap[$id] = $document;
        $this->setDocumentState($oid, self::STATE_MANAGED);

        return $oid;
    }

    /**
     * @param object|string $document document instance or document object hash
     *
     * @return boolean
     */
    public function contains($document)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;

        return isset($this->documentIds[$oid]) && !isset($this->scheduledRemovals[$oid]);
    }

    /**
     * Tries to find a document with the given id in the identity map of
     * this UnitOfWork.
     *
     * @param string $id            The document id to look for.
     * @param string $rootClassName The name of the root class of the mapped document hierarchy.
     *
     * @return object|false Returns the document with the specified id if it exists in
     *                      this UnitOfWork, FALSE otherwise.
     */
    public function getDocumentById($id)
    {
        if (isset($this->identityMap[$id])) {
            return $this->identityMap[$id];
        }

        return false;
    }

    /**
     * Get the object ID for the given document
     *
     * @param object|string $document document instance or document object hash
     *
     * @return string|null
     *
     * @throws PHPCRException
     */
    public function getDocumentId($document, $throw = true)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        if (empty($this->documentIds[$oid])) {
            if (!$throw) {
                return null;
            }
            $msg = 'Document is not managed and has no id';
            if (is_object($document)) {
                $msg.= ': '.self::objToStr($document);
            }
            throw new PHPCRException($msg);
        }

        return $this->documentIds[$oid];
    }

    /**
     * Try to determine the document id first by looking into the document,
     * but if not mapped, look into the document id cache.
     *
     * @param object        $document
     * @param ClassMetadata $metadata
     *
     * @return string|null the current or stored id, or null if nothing can be found.
     */
    public function determineDocumentId($document, ClassMetadata $metadata = null)
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
     *
     * @param object
     */
    public function initializeObject($obj)
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
    public function clear()
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
        $this->documentVersion = array();

        $this->invokeGlobalEvent(Event::onClear, new OnClearEventArgs($this->dm));

        $this->session->refresh(false);
    }

    /**
     * Get all locales in which this document currently exists in storage.
     *
     * @param object $document A managed document
     *
     * @return array list of locales of this document
     *
     * @throws MissingTranslationException if this document is not translatable
     */
    public function getLocalesFor($document)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            throw new MissingTranslationException('This document is not translatable: : '.self::objToStr($document, $this->dm));
        }

        $oid = spl_object_hash($document);
        if ($this->contains($oid)) {
            try {
                $node = $this->session->getNode($this->getDocumentId($document));
                $locales = $this->dm->getTranslationStrategy($metadata->translator)->getLocalesFor($document, $node, $metadata);
            } catch (PathNotFoundException $e) {
                $locales = array();
            }
        } else {
            $locales = array();
        }

        if (isset($this->documentTranslations[$oid])) {
            foreach ($this->documentTranslations[$oid] as $locale => $value) {
                if (!in_array($locale, $locales)) {
                    if ($value) {
                        $locales[] = $locale;
                    }
                } elseif (!$value) {
                    $key = array_search($locale, $locales);
                    unset($locales[$key]);
                }
            }

            $locales = array_values($locales);
        }

        return $locales;
    }

    private function doSaveTranslation($document, NodeInterface $node, $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $oid = spl_object_hash($document);
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
     * @param object        $document
     * @param ClassMetadata $metadata
     * @param string        $locale
     *
     * @return boolean whether the pending translation in language $locale was
     *                 loaded or not.
     *
     * @see doLoadTranslation
     */
    protected function doLoadPendingTranslation($document, ClassMetadata $metadata, $locale)
    {
        $oid = spl_object_hash($document);

        if (!isset($this->documentTranslations[$oid][$locale])) {
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
     * @param object        $document
     * @param ClassMetadata $metadata
     * @param string        $locale   The desired locale.
     * @param boolean       $fallback Whether to perform language fallback.
     * @param boolean       $refresh  Whether to force reloading the translation.
     *
     * @return string The locale used
     *
     * @throws MissingTranslationException if the translation in $locale is not
     *                                     found and $fallback is false.
     *
     * @see doLoadTranslation
     */
    protected function doLoadDatabaseTranslation($document, ClassMetadata $metadata, $locale, $fallback, $refresh)
    {
        $oid = spl_object_hash($document);

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
            if (!$refresh && isset($this->documentLocales[$oid]['current']) && $desiredLocale == $this->documentLocales[$oid]['current']) {
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
            $value = ($metadata->mappings[$fieldName]['multivalue']) ? array() : null;
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
     *
     * @param object        $document
     * @param ClassMetadata $metadata
     * @param string        $locale   The locale to use or null if the default locale should be used
     * @param boolean       $fallback Whether to do try other languages
     * @param boolean       $refresh
     *
     * @throws MissingTranslationException if the translation in $locale is not
     *                                     found and $fallback is false.
     */
    public function doLoadTranslation($document, ClassMetadata $metadata, $locale = null, $fallback = false, $refresh = false)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $currentLocale = $this->getCurrentLocale($document, $metadata);

        $oid = spl_object_hash($document);
        if (null === $locale || $locale === $currentLocale) {
            // current locale is already loaded and not removed
            if (!$refresh && isset($this->documentTranslations[$oid][$currentLocale])) {
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

        if ($metadata->parentMapping) {
            $parent = $metadata->reflFields[$metadata->parentMapping]->getValue($document);
            $this->cascadeDoLoadTranslation($parent, $metadata->mappings[$metadata->parentMapping], $locale);
        }

        if ($metadata->childMappings) {
            foreach ($metadata->childMappings as $fieldName) {
                $child = $metadata->reflFields[$fieldName]->getValue($document);
                $this->cascadeDoLoadTranslation($child, $metadata->mappings[$fieldName], $locale);
            }
        }

        if ($metadata->childrenMappings) {
            foreach ($metadata->childrenMappings as $fieldName) {
                $children = $metadata->reflFields[$fieldName]->getValue($document);
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
                $reference = $metadata->reflFields[$fieldName]->getValue($document);
                if ($reference instanceof ReferenceManyCollection && !$reference->isInitialized()) {
                    $reference->setLocale($locale);
                } else {
                    if ($reference instanceof \Traversable || is_array($reference)) {
                        foreach ($reference as $ref) {
                            $this->cascadeDoLoadTranslation($ref, $metadata->mappings[$fieldName], $locale);
                        }
                    } else {
                        $this->cascadeDoLoadTranslation($reference, $metadata->mappings[$fieldName], $locale);
                    }
                }
            }
        }

        if ($metadata->referrersMappings) {
            foreach ($metadata->referrersMappings as $fieldName) {
                $referrers = $metadata->reflFields[$fieldName]->getValue($document);
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

    /**
     * @param object $document
     * @param array  $mapping
     * @param string $locale
     */
    private function cascadeDoLoadTranslation($document, array $mapping, $locale)
    {
        if (!$document || !($mapping['cascade'] & ClassMetadata::CASCADE_TRANSLATION)) {
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
     * @param object $document
     * @param string $locale
     *
     * @throws MissingTranslationException
     * @throws PHPCRException
     */
    public function removeTranslation($document, $locale)
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

        $oid = spl_object_hash($document);
        $this->documentTranslations[$oid][$locale] = null;
        $this->setLocale($document, $metadata, null);
    }

    /**
     * Checks if the translation was removed
     * Note it also returns true if the document isn't translated
     * or was not translated into the given locale, ie.
     * it does not check if there is a translation for the given locale
     *
     * @param object $document
     * @param string $locale
     *
     * @return bool
     */
    private function isTranslationRemoved($document, $locale)
    {
        $oid = spl_object_hash($document);

        return isset($this->documentTranslations[$oid])
            && empty($this->documentTranslations[$oid][$locale])
            && array_key_exists($locale, $this->documentTranslations[$oid])
        ;
    }

    /**
     * @param object        $document
     * @param ClassMetadata $metadata
     * @throws PHPCRException
     */
    private function doRemoveAllTranslations($document, ClassMetadata $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $node = $this->session->getNode($this->getDocumentId($document));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);
        $strategy->removeAllTranslations($document, $node, $metadata);
    }

    /**
     * @param object        $document
     * @param ClassMetadata $metadata
     * @param string        $locale
     */
    private function setLocale($document, ClassMetadata $metadata, $locale)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $oid = spl_object_hash($document);
        if (empty($this->documentLocales[$oid])) {
            $this->documentLocales[$oid] = array('original' => $locale);
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
     * @param object        $document the managed document to get the locale for
     * @param ClassMetadata $metadata document metadata, optional
     *
     * @return string|null the current locale of $document or null if it is not translatable
     */
    public function getCurrentLocale($document, ClassMetadata $metadata = null)
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
            $locale = $metadata->reflFields[$metadata->localeMapping]->getValue($document);
            if ($locale) {
                return $locale;
            }
        }

        $oid = spl_object_hash($document);
        if (isset($this->documentLocales[$oid]['current'])) {
            return $this->documentLocales[$oid]['current'];
        }

        return $this->dm->getLocaleChooserStrategy()->getLocale();
    }

    /**
     * Determine whether this document is translatable.
     *
     * To be translatable, it needs a translation strategy and have at least
     * one translated field.
     *
     * @param ClassMetadata $metadata the document meta data
     *
     * @return boolean
     */
    public function isDocumentTranslatable(ClassMetadata $metadata)
    {
        return !empty($metadata->translator)
            && is_string($metadata->translator)
            && count($metadata->translatableFields) !== 0;
    }

    private static function objToStr($obj, DocumentManager $dm = null)
    {
        $string = method_exists($obj, '__toString')
            ? (string) $obj
            : ClassUtils::getClass($obj).'@'.spl_object_hash($obj);

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

    private function getVersionedNodePath($document)
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

        $mixin = $metadata->versionable === 'simple' ?
            'mix:simpleVersionable' :
            'mix:versionable';

        if (!$node->isNodeType($mixin)) {
            $node->addMixin($mixin);
        }


        return $path;
    }

    /**
     * Set the mapped mixins.
     *
     * @param ClassMetadata $metadata
     * @param NodeInterface $node
     * @param object        $document The document to update autogenerated fields.
     */
    private function setMixins(Mapping\ClassMetadata $metadata, NodeInterface $node, $document)
    {
        $repository = $this->session->getRepository();
        if ($metadata->versionable === 'full') {
            if ($repository->getDescriptor(RepositoryInterface::OPTION_VERSIONING_SUPPORTED)) {
                $node->addMixin('mix:versionable');
            } elseif ($repository->getDescriptor(RepositoryInterface::OPTION_SIMPLE_VERSIONING_SUPPORTED)) {
                $node->addMixin('mix:simpleVersionable');
            }
        } elseif ($metadata->versionable === 'simple'
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

    /**
     * @return string a universally unique id.
     */
    private function generateUuid()
    {
        // php 5.3 compatibility, no direct execution of this closure.
        $g = $this->uuidGenerator;

        // TODO do we need to check with the storage backend if the generated id really is unique?
        return $g();
    }

    /**
     * Sets the fetch depth on the session if the PHPCR session instance supports it
     * and returns the previous fetch depth value
     *
     * @param int|null $fetchDepth
     *
     * @return int previous fetch depth value
     */
    public function setFetchDepth($fetchDepth = null)
    {
        if (!$this->useFetchDepth) {
            return 0;
        }

        $oldFetchDepth = $this->session->getSessionOption($this->useFetchDepth);

        if (isset($fetchDepth)) {
            $this->session->setSessionOption($this->useFetchDepth, $fetchDepth);
        }

        return $oldFetchDepth;
    }

    /**
     * Gets the currently scheduled document updates in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledUpdates()
    {
        return $this->scheduledUpdates;
    }

    /**
     * Gets the currently scheduled document insertions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledInserts()
    {
        return $this->scheduledInserts;
    }

    /**
     * Gets the currently scheduled document moves in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledMoves()
    {
        return $this->scheduledMoves;
    }

    /**
     * Gets the currently scheduled document reorders in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledReorders()
    {
        return $this->scheduledReorders;
    }

    /**
     * Gets the currently scheduled document deletions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledRemovals()
    {
        return $this->scheduledRemovals;
    }

    /**
     * Process null values in an associative array so that they can be stored in phpcr.
     *
     * Stores keys and null fields in the node and returns the processed values
     *
     * @param  NodeInterface $node    the node where to store the assoc array
     * @param  array         $mapping the field's mapping
     * @param  array         $assoc   the associative array
     * @return array
     */
    public function processAssoc(NodeInterface $node, array $mapping, array $assoc)
    {
        $isNull = function ($item) {
            return null === $item;
        };

        $isNotNull = function ($item) {
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
     * Create an associative array form the properties stored with the node
     *
     * @param  array $properties the node's properties
     * @param  array $mapping    the field's mapping information
     * @return array
     */
    public function createAssoc(array $properties, array $mapping)
    {
        $values = (array) $properties[$mapping['property']];
        if (!isset($properties[$mapping['assoc']])) {
            return $values;
        }

        $keys = (array) $properties[$mapping['assoc']];
        $nulls = isset($properties[$mapping['assocNulls']]) ? ((array) $properties[$mapping['assocNulls']]) : array();

        // make sure we start with first value
        reset($values);
        $result = array();
        foreach ($keys as $key) {
            if (in_array($key, $nulls)) {
                $result[$key] = null;
            } else {
                $result[$key] = current($values);
                next($values);
            }
        }

        return $result;
    }

    /**
     * Remove an associative array form the properties stored with the node
     *
     * @param  NodeInterface $node    the node where to store the assoc array
     * @param  array         $mapping the field's mapping
     */
    public function removeAssoc(NodeInterface $node, array $mapping)
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
     *
     * @param $eventName
     * @param EventArgs $event
     */
    public function invokeGlobalEvent($eventName, EventArgs $event)
    {
        if ($this->eventManager->hasListeners($eventName)) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }
}
