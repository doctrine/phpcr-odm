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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\PathNotFoundException;
use Doctrine\ODM\PHPCR\Exception\CascadeException;
use Doctrine\ODM\PHPCR\Exception\MissingTranslationException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event\OnFlushEventArgs;
use Doctrine\ODM\PHPCR\Event\PreFlushEventArgs;
use Doctrine\ODM\PHPCR\Event\PostFlushEventArgs;
use Doctrine\ODM\PHPCR\Event\OnClearEventArgs;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;
use Doctrine\ODM\PHPCR\Proxy\Proxy;

use Jackalope\Session as JackalopeSession;

use PHPCR\RepositoryInterface;
use PHPCR\PropertyType;
use PHPCR\NodeInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\ItemNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\RepositoryException;
use PHPCR\Util\UUIDHelper;

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
     * @var array of \PHPCR\Version\VersionHistory
     */
    private $documentHistory = array();

    /**
     * Track version objects of the version documents we create, indexed by spl_object_hash
     * @var array of PHPCR\Version\Version
     */
    private $documentVersion = array();

    /**
     * @var array
     */
    private $documentState = array();

    /**
     * @var array
     */
    private $documentTranslations = array();

    /**
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
    private $documentChangesets = array();

    /**
     * @var array
     */
    private $scheduledUpdates = array();

    /**
     * @var array
     */
    private $scheduledAssociationUpdates = array();

    /**
     * @var array
     */
    private $scheduledInserts = array();

    /**
     * @var array
     */
    private $scheduledMoves = array();

    /**
     * @var array
     */
    private $scheduledReorders = array();

    /**
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
     * @var array
     */
    private $idGenerators = array();

    /**
     * \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * @var DocumentClassMapperInterface
     */
    private $documentClassMapper;

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
        $this->evm = $dm->getEventManager();

        $config = $dm->getConfiguration();
        $this->documentClassMapper = $config->getDocumentClassMapper();
        $this->validateDocumentName = $config->getValidateDoctrineMetadata();
        $this->writeMetadata = $config->getWriteDoctrineMetadata();

        if ($this->session instanceof JackalopeSession) {
            $this->useFetchDepth = 'jackalope.fetch_depth';
        }
    }

    /**
     * @param object                    $document
     * @param string                    $className
     *
     * @throws \InvalidArgumentException
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
     *
     * @param null|string   $className
     * @param NodeInterface $node
     * @param array         $hints
     *
     * @return object
     */
    public function getOrCreateDocument($className, NodeInterface $node, array &$hints = array())
    {
        $requestedClassName = $className;
        $className = $this->documentClassMapper->getClassName($this->dm, $node, $className);
        $class = $this->dm->getClassMetadata($className);
        $id = $node->getPath();

        $document = $this->getDocumentById($id);

        if ($document) {
            if (empty($hints['refresh'])) {
                // document already loaded and no need to refresh. return early

                return $document;
            }
            $overrideLocalValuesOid = spl_object_hash($document);
        } else {
            $document = $class->newInstance();
            // delay registering the new document until children proxy have been created
            $overrideLocalValuesOid = false;
        }
        $this->validateClassName($document, $requestedClassName);

        $documentState = array();
        $nonMappedData = array();

        // second param is false to get uuid rather than dereference reference properties to node instances
        $properties = $node->getPropertiesValues(null, false);

        foreach ($class->fieldMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (isset($properties[$mapping['name']])) {
                if (true === $mapping['multivalue']) {
                    if (isset($mapping['assoc']) && isset($properties[$mapping['assoc']])) {
                        $documentState[$fieldName] = array_combine((array) $properties[$mapping['assoc']], (array) $properties[$mapping['name']]);
                    } else {
                        $documentState[$fieldName] = (array) $properties[$mapping['name']];
                    }
                } else {
                    $documentState[$fieldName] = $properties[$mapping['name']];
                }
            } elseif (true === $mapping['multivalue']) {
                $documentState[$mapping['name']] = array();
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

        // pre-fetch all nodes for MANY_TO_ONE references
        $refNodeUUIDs = array();
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!$node->hasProperty($fieldName)) {
                continue;
            }

            if ($mapping['type'] & ClassMetadata::MANY_TO_ONE
                && $mapping['strategy'] !== 'path'
            ) {
                $refNodeUUIDs[] = $node->getProperty($fieldName)->getString();
            }
        }

        if (count($refNodeUUIDs)) {
            $this->session->getNodesByIdentifier($refNodeUUIDs);
        }

        // initialize inverse side collections
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                if (!$node->hasProperty($fieldName)) {
                    continue;
                }

                try {
                    if (isset($mapping['targetDocument'])) {
                        $referencedClass = $this->dm->getMetadataFactory()->getMetadataFor(ltrim($mapping['targetDocument'], '\\'))->name;

                        if ($mapping['strategy'] === 'path') {
                            $path = $node->getProperty($fieldName)->getString();
                        } else {
                            $referencedNode = $node->getProperty($fieldName)->getNode();
                            $path = $referencedNode->getPath();
                        }

                        $proxy = $this->getOrCreateProxy($path, $referencedClass);
                    } else {
                        $referencedNode = $node->getProperty($fieldName)->getNode();
                        $proxy = $this->getOrCreateProxyFromNode($referencedNode);
                    }
                } catch (RepositoryException $e) {
                    if ($e instanceof ItemNotFoundException || isset($hints['ignoreHardReferenceNotFound'])) {
                        // a weak reference or an old version can have lost references
                        $proxy = null;
                    } else {
                        throw $e;
                    }
                }

                $documentState[$fieldName] = $proxy;
            } elseif ($mapping['type'] === ClassMetadata::MANY_TO_MANY) {
                $referencedNodes = array();
                if ($node->hasProperty($fieldName)) {
                    foreach ($node->getProperty($fieldName)->getString() as $reference) {
                        $referencedNodes[] = $reference;
                    }
                }

                $targetDocument = isset($mapping['targetDocument']) ? $mapping['targetDocument'] : null;
                $coll = new ReferenceManyCollection($this->dm, $referencedNodes, $targetDocument);
                $documentState[$fieldName] = $coll;
            }
        }

        if ($class->parentMapping && $node->getDepth() > 0) {
            // do not map parent to self if we are at root
            $documentState[$class->parentMapping] = $this->getOrCreateProxyFromNode($node->getParent());
        }

        foreach ($class->childMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            $documentState[$fieldName] = $node->hasNode($mapping['name'])
                ? $this->getOrCreateProxyFromNode($node->getNode($mapping['name']))
                : null;
        }

        foreach ($class->childrenMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            $documentState[$fieldName] = new ChildrenCollection($this->dm, $document, $mapping['filter'], $mapping['fetchDepth']);
        }

        foreach ($class->referrersMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            $documentState[$fieldName] = new ReferrersCollection($this->dm, $document, $mapping['referenceType'], $mapping['filter']);
        }

        if (! $overrideLocalValuesOid) {
            // registering the document needs to be delayed until the children proxies where created
            $overrideLocalValuesOid = $this->registerDocument($document, $id);
        }

        $this->nonMappedData[$overrideLocalValuesOid] = $nonMappedData;
        foreach ($class->reflFields as $prop => $reflFields) {
            $value = isset($documentState[$prop]) ? $documentState[$prop] : null;
            $reflFields->setValue($document, $value);
            $this->originalData[$overrideLocalValuesOid][$prop] = $value;
        }

        // Load translations
        $locale = isset($hints['locale']) ? $hints['locale'] : null;
        $fallback = isset($hints['fallback']) ? $hints['fallback'] : is_null($locale);

        $this->doLoadTranslation($document, $class, $locale, $fallback);

        // Invoke the postLoad lifecycle callbacks and listeners
        if (isset($class->lifecycleCallbacks[Event::postLoad])) {
            $class->invokeLifecycleCallbacks(Event::postLoad, $document);
        }
        if ($this->evm->hasListeners(Event::postLoad)) {
            $this->evm->dispatchEvent(Event::postLoad, new Event\LifecycleEventArgs($document, $this->dm));
        }

        return $document;
    }

    /**
     * Get the existing document or proxy or create a new one for this PHPCR Node
     *
     * @param NodeInterface $node
     *
     * @return object
     */
    public function getOrCreateProxyFromNode(NodeInterface $node)
    {
        $targetId = $node->getPath();
        $className = $this->documentClassMapper->getClassName($this->dm, $node);

        return $this->getOrCreateProxy($targetId, $className);
    }

    /**
     * Get the existing document or proxy for this id of this class, or create
     * a new one.
     *
     * @param string $targetId
     * @param string $className
     *
     * @return object
     */
    public function getOrCreateProxy($targetId, $className)
    {
        $document = $this->getDocumentById($targetId);

        // check if referenced document already exists
        if ($document) {
            return $document;
        }

        $proxyDocument = $this->dm->getProxyFactory()->getProxy($className, $targetId);

        // register the document under its own id
        $this->registerDocument($proxyDocument, $targetId);

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
        $node = $this->session->getNode($document->__getIdentifier());
        $hints = array('refresh' => true);
        $this->getOrCreateDocument($className, $node, $hints);
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
        $state = $this->getDocumentState($document);
        if ($state !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Document has to be managed to be able to bind a translation '.self::objToStr($document, $this->dm));
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($class)) {
            throw new PHPCRException('This document is not translatable, do not use bindTranslation: '.self::objToStr($document, $this->dm));
        }

        // Set the @Locale field
        if ($class->localeMapping) {
            $class->reflFields[$class->localeMapping]->setValue($document, $locale);
        }

        $oid = spl_object_hash($document);
        if (empty($this->documentTranslations[$oid])) {
            $this->documentTranslations[$oid] = array();
        }

        foreach ($class->translatableFields as $field) {
            $this->documentTranslations[$oid][$locale][$field] = $class->reflFields[$field]->getValue($document);
        }

        if (empty($this->documentLocales[$oid])) {
            $this->documentLocales[$oid] = array('original' => $locale);
        }
        $this->documentLocales[$oid]['current'] = $locale;
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
        $oid = spl_object_hash($document);
        // To avoid recursion loops (over children and parents)
        if (isset($visited[$oid])) {
            return;
        }
        $visited[$oid] = true;

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isMappedSuperclass) {
            throw new \InvalidArgumentException('Cannot persist a mapped super class instance: '.$class->name);
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
                throw new \InvalidArgumentException('Detached document or new document with already existing id passed to persist(): '.self::objToStr($document, $this->dm));
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
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST) {
                $related = $class->reflFields[$fieldName]->getValue($document);
                if ($related !== null) {
                    if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                        if (is_array($related) || $related instanceof Collection) {
                            throw new PHPCRException('Referenced document is not stored correctly in a reference-one property. Do not use array notation or a (ReferenceMany)Collection: '.self::objToStr($document, $this->dm));
                        }

                        if ($this->getDocumentState($related) === self::STATE_NEW) {
                            $this->doScheduleInsert($related, $visited);
                        }
                    } else {
                        if (!is_array($related) && !$related instanceof Collection) {
                            throw new PHPCRException('Referenced document is not stored correctly in a reference-many property. Use array notation or a (ReferenceMany)Collection: '.self::objToStr($document, $this->dm));
                        }
                        foreach ($related as $relatedDocument) {
                            if (isset($relatedDocument) && $this->getDocumentState($relatedDocument) === self::STATE_NEW) {
                                $this->doScheduleInsert($relatedDocument, $visited);
                            }
                        }
                    }
                }
            }
        }

        $id = $this->getDocumentId($document);
        foreach ($class->childMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            $child = $class->reflFields[$fieldName]->getValue($document);
            if ($child !== null && $this->getDocumentState($child) === self::STATE_NEW) {
                $childClass = $this->dm->getClassMetadata(get_class($child));
                $childId = $id.'/'.$mapping['name'];
                $childClass->setIdentifierValue($child, $childId);
                $this->doScheduleInsert($child, $visited, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            }
        }

        foreach ($class->childrenMappings as $fieldName) {
            $children = $class->reflFields[$fieldName]->getValue($document);
            if (empty($children)) {
                continue;
            }

            foreach ($children as $child) {
                if ($child !== null && $this->getDocumentState($child) === self::STATE_NEW) {
                    $childClass = $this->dm->getClassMetadata(get_class($child));
                    $nodename = $childClass->nodename
                        ? $childClass->reflFields[$childClass->nodename]->getValue($child)
                        : basename($childClass->getIdentifierValue($child));
                    $childId = $id.'/'.$nodename;
                    $childClass->setIdentifierValue($child, $childId);
                    $this->doScheduleInsert($child, $visited, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
                }
            }
        }
    }

    private function cascadeScheduleParentInsert($class, $document, &$visited)
    {
        if ($class->parentMapping) {
            $parent = $class->reflFields[$class->parentMapping]->getValue($document);
            if ($parent !== null && $this->getDocumentState($parent) === self::STATE_NEW) {
                $this->doScheduleInsert($parent, $visited);
            }
        }
    }

    private function getIdGenerator($type)
    {
        if (!isset($this->idGenerators[$type])) {
            $this->idGenerators[$type] = Id\IdGenerator::create($type);
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
                throw new \InvalidArgumentException('Detached document passed to move(): '.self::objToStr($document, $this->dm));
        }

        $this->scheduledMoves[$oid] = array($document, $targetPath);
        $this->setDocumentState($oid, self::STATE_MANAGED);
    }

    public function scheduleReorder($document, $srcName, $targetName, $before)
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
                throw new \InvalidArgumentException('Detached document passed to reorder(): '.self::objToStr($document, $this->dm));
        }

        $this->scheduledReorders[$oid] = array($document, $srcName, $targetName, $before);
        $this->setDocumentState($oid, self::STATE_MANAGED);
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
                throw new \InvalidArgumentException('Detached document passed to remove(): '.self::objToStr($document, $this->dm));
        }

        $this->scheduledRemovals[$oid] = $document;
        $this->setDocumentState($oid, self::STATE_REMOVED);

        $class = $this->dm->getClassMetadata(get_class($document));
        if (isset($class->lifecycleCallbacks[Event::preRemove])) {
            $class->invokeLifecycleCallbacks(Event::preRemove, $document);
        }
        if ($this->evm->hasListeners(Event::preRemove)) {
            $this->evm->dispatchEvent(Event::preRemove, new LifecycleEventArgs($document, $this->dm));
        }

        $this->cascadeRemove($class, $document, $visited);
    }

    private function cascadeRemove(ClassMetadata $class, $document, &$visited)
    {
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['cascade'] & ClassMetadata::CASCADE_REMOVE) {
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
        }
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
     * @param object $document
     *
     * @return int
     */
    private function getDocumentState($document)
    {
        $oid = spl_object_hash($document);
        if (!isset($this->documentState[$oid])) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $id = $class->getIdentifierValue($document);
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
     * Detects the changes for a single document
     *
     * @param object $document
     */
    private function computeSingleDocumentChangeSet($document)
    {
        $state = $this->getDocumentState($document);
        if ($state !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Document has to be managed for single computation '.self::objToStr($document, $this->dm));
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
    private function computeChangeSets()
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

    private function getChildNodename($id, $nodename, $child)
    {
        $childClass = $this->dm->getClassMetadata(get_class($child));
        if ($childClass->nodename && $childClass->reflFields[$childClass->nodename]->getValue($child)) {
            $nodename = $childClass->reflFields[$childClass->nodename]->getValue($child);
        } else {
            $childId = $childClass->getIdentifierValue($child);
            if ('' !== $childId) {
                if ($childId !== $id.'/'.basename($childId)) {
                    throw PHPCRException::cannotMoveByAssignment(self::objToStr($child, $this->dm));
                }
                $nodename = basename($childId);
            }
        }

        return $nodename;
    }

    /**
     * @param ClassMetadata $class
     * @param object        $document
     */
    private function computeChangeSet(ClassMetadata $class, $document)
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $oid = spl_object_hash($document);

        if (in_array($oid, $this->changesetComputed)) {
            return;
        }

        $this->changesetComputed[] = $oid;

        $actualData = $this->getDocumentActualData($class, $document);
        $id = $this->getDocumentId($document);

        $isNew = !isset($this->originalData[$oid]);
        if ($isNew) {
            // Document is New and should be inserted
            $this->originalData[$oid] = $actualData;
            $this->documentChangesets[$oid] = array('fields' => $actualData, 'reorderings' => array());
            $this->scheduledInserts[$oid] = $document;

            foreach ($class->childrenMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if ($actualData[$fieldName]) {
                    foreach ($actualData[$fieldName] as $nodename => $child) {
                        $nodename = $this->getChildNodename($id, $nodename, $child);
                        $this->computeChildChanges($mapping, $child, $id, $nodename, $document);
                        $childNames[] = $nodename;
                    }
                }
            }
        }

        if ($class->parentMapping && isset($actualData[$class->parentMapping])) {
            $parent = $actualData[$class->parentMapping];
            $parentClass = $this->dm->getClassMetadata(get_class($parent));
            $state = $this->getDocumentState($parent);

            if ($state === self::STATE_MANAGED) {
                $this->computeChangeSet($parentClass, $parent);
            }
        }

        foreach ($class->childMappings as $fieldName) {
            if ($actualData[$fieldName]) {
                if ($this->originalData[$oid][$fieldName] && $this->originalData[$oid][$fieldName] !== $actualData[$fieldName]) {
                    throw PHPCRException::cannotMoveByAssignment(self::objToStr($actualData[$fieldName], $this->dm));
                }
                $mapping = $class->mappings[$fieldName];
                $this->computeChildChanges($mapping, $actualData[$fieldName], $id);
            }
        }

        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($actualData[$fieldName]) {
                if ($actualData[$fieldName] instanceof PersistentCollection
                    && !$actualData[$fieldName]->isInitialized()
                ) {
                    continue;
                }

                if (is_array($actualData[$fieldName]) || $actualData[$fieldName] instanceof Collection) {
                    if ($actualData[$fieldName] instanceof PersistentCollection && !$actualData[$fieldName]->isInitialized()) {
                        continue;
                    }

                    foreach ($actualData[$fieldName] as $ref) {
                        if ($ref !== null) {
                            $this->computeReferenceChanges($mapping, $ref);
                        }
                    }
                } else {
                    $this->computeReferenceChanges($mapping, $actualData[$fieldName]);
                }
            }
        }

        foreach ($class->referrersMappings as $fieldName) {
            if ($actualData[$fieldName]) {
                if ($actualData[$fieldName] instanceof PersistentCollection && !$actualData[$fieldName]->isInitialized()) {
                    continue;
                }

                $mapping = $class->mappings[$fieldName];
                foreach ($actualData[$fieldName] as $referrer) {
                    $this->computeReferrerChanges($mapping, $referrer);
                }
            }
        }

        if (!$isNew) {
            // collect assignment move operations
            $destPath = $destName = false;

            if (isset($this->originalData[$oid][$class->parentMapping])
                && isset($actualData[$class->parentMapping])
                && $this->originalData[$oid][$class->parentMapping] !== $actualData[$class->parentMapping]
            ) {
                $destPath = $this->getDocumentId($actualData[$class->parentMapping]);
            }

            if (isset($this->originalData[$oid][$class->nodename])
                && isset($actualData[$class->nodename])
                && $this->originalData[$oid][$class->nodename] !== $actualData[$class->nodename]
            ) {
                $destName = $actualData[$class->nodename];
            }

            // there was assignment move
            if ($destPath || $destName) {
                // add the other field if only one was changed
                if (false === $destPath) {
                    $destPath = $this->getDocumentId($actualData[$class->parentMapping]);
                }
                if (false === $destName) {
                    $destName = $actualData[$class->nodename];
                }

                // prevent path from becoming "//foobar" when moving to root node.
                $targetPath = ('/' == $destPath) ? "/$destName" : "$destPath/$destName";

                $this->scheduleMove($document, $targetPath);
            }

            if (isset($this->originalData[$oid][$class->identifier])
                && isset($actualData[$class->identifier])
                && $this->originalData[$oid][$class->identifier] !== $actualData[$class->identifier]
            ) {
                throw new PHPCRException('The Id is immutable ('.$this->originalData[$oid][$class->identifier].' !== '.$actualData[$class->identifier].'). Please use DocumentManager::move to move the document: '.self::objToStr($document, $this->dm));
            }

            foreach ($class->childrenMappings as $fieldName) {
                $mapping = $class->mappings[$fieldName];
                if ($actualData[$fieldName] instanceof PersistentCollection
                    && !$actualData[$fieldName]->isInitialized()
                ) {
                    continue;
                }

                $childNames = array();
                if ($actualData[$fieldName]) {
                    foreach ($actualData[$fieldName] as $nodename => $child) {
                        $nodename = $this->getChildNodename($id, $nodename, $child);
                        $this->computeChildChanges($mapping, $child, $id, $nodename, $document);
                        $childNames[] = $nodename;
                    }
                }

                if ($this->originalData[$oid][$fieldName] instanceof ChildrenCollection) {
                    $originalNames = $this->originalData[$oid][$fieldName]->getOriginalNodenames();
                    foreach ($originalNames as $key => $childName) {
                        if (!in_array($childName, $childNames)) {
                            $child = $this->getDocumentById($id.'/'.$childName);
                            $this->scheduleRemove($child);
                            unset($originalNames[$key]);
                        }
                    }
                }

                if (!empty($childNames) && isset($originalNames)) {
                    // reindex the arrays to avoid holes in the indexes
                    $originalNames = array_values($originalNames);
                    $originalNames = array_merge($originalNames, array_diff($childNames, $originalNames));
                    if ($originalNames !== $childNames) {
                        $reordering = array();

                        $count = count($childNames);
                        if ($count === 2) {
                            // special handling for 2 children collections
                            $reordering[$childNames[0]] = $childNames[1];
                        } else {
                            for ($i = $count - 2; $i >= 0; $i--) {
                                $targetKey = array_search($childNames[$i], $originalNames);
                                if ($targetKey !== $i) {
                                    // child needs to be moved
                                    $reordering[$childNames[$i]] = $childNames[$i + 1];
                                    // update the original order to check if we have done all necessary steps
                                    $value = $originalNames[$targetKey];
                                    unset($originalNames[$targetKey]);
                                    $part1 = array_slice($originalNames, 0, $i);
                                    $part2 = array_slice($originalNames, $i);
                                    $originalNames = array_merge($part1, array($value), $part2);
                                    if ($originalNames === $childNames) {
                                        break;
                                    }
                                }
                            }
                        }

                        if (empty($this->documentChangesets[$oid])) {
                            $this->documentChangesets[$oid] = array('fields' => array(), 'reorderings' => array($reordering));
                        } else {
                            $this->documentChangesets[$oid]['reorderings'][] = $reordering;
                        }

                        $this->scheduledUpdates[$oid] = $document;
                    }
                }
            }

            if (!isset($this->documentLocales[$oid])
                || $this->documentLocales[$oid]['current'] === $this->documentLocales[$oid]['original']
            ) {
                // remove anything from $actualData that did not change
                foreach ($actualData as $fieldName => $fieldValue) {
                    if (isset($class->mappings[$fieldName])) {
                        if ($this->originalData[$oid][$fieldName] !== $fieldValue) {
                            continue;
                        } elseif ($fieldValue instanceof ReferenceManyCollection && $fieldValue->changed()) {
                            continue;
                        }
                    }

                    unset($actualData[$fieldName]);
                }
            }

            if (count($actualData)) {
                if (empty($this->documentChangesets[$oid])) {
                    $this->documentChangesets[$oid] = array('fields' => $actualData, 'reorderings' => array());
                } else {
                    $this->documentChangesets[$oid]['fields'] = $actualData;
                }

                $this->scheduledUpdates[$oid] = $document;
            }
        }
    }

    /**
     * Computes the changes of a child.
     *
     * @param array  $mapping  the mapping data
     * @param mixed  $child    the child document.
     * @param string $parentId
     * @param string $nodename
     * @param mixed  $parent
     */
    private function computeChildChanges($mapping, $child, $parentId, $nodename = null, $parent = null)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($child));
        $state = $this->getDocumentState($child);

        switch ($state) {
            case self::STATE_NEW:
                if (!($mapping['cascade'] & ClassMetadata::CASCADE_PERSIST) ) {
                    throw CascadeException::newDocumentFound(self::objToStr($child));
                }
                $nodename = $nodename ?: $mapping['name'];
                if ($nodename) {
                    $targetClass->setIdentifierValue($child, $parentId.'/'.$nodename);
                }
                $this->persistNew($targetClass, $child, ClassMetadata::GENERATOR_TYPE_ASSIGNED, $parent);
                $this->computeChangeSet($targetClass, $child);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException('A detached document was found through a child relationship during cascading a persist operation: '.self::objToStr($child, $this->dm));
        }
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
                throw new \InvalidArgumentException('A detached document was found through a reference during cascading a persist operation: '.self::objToStr($reference, $this->dm));
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
                throw new \InvalidArgumentException('A detached document was found through a referrer during cascading a persist operation: '.self::objToStr($referrer, $this->dm));
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
    public function persistNew($class, $document, $overrideIdGenerator = null, $parent = null)
    {
        if (isset($class->lifecycleCallbacks[Event::prePersist])) {
            $class->invokeLifecycleCallbacks(Event::prePersist, $document);
        }
        if ($this->evm->hasListeners(Event::prePersist)) {
            $this->evm->dispatchEvent(Event::prePersist, new LifecycleEventArgs($document, $this->dm));
        }

        $generator = $overrideIdGenerator ? $overrideIdGenerator : $class->idGenerator;

        $id = $this->getIdGenerator($generator)->generate($document, $class, $this->dm, $parent);
        $this->registerDocument($document, $id);

        if ($generator !== ClassMetadata::GENERATOR_TYPE_ASSIGNED) {
            $class->setIdentifierValue($document, $id);
        }
    }

    public function refresh($document)
    {
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
            throw new \InvalidArgumentException('Document has to be managed to be refreshed '.self::objToStr($document, $this->dm));
        }

        $this->session->refresh(true);
        $node = $this->session->getNode($this->getDocumentId($document));

        $class = $this->dm->getClassMetadata(get_class($document));
        $this->cascadeRefresh($class, $document, $visited);

        $hints = array('refresh' => true);
        $this->getOrCreateDocument(get_class($document), $node, $hints);
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
        } elseif ($mapping['cascade'] & ClassMetadata::CASCADE_MERGE == 0) {
            if ($this->getDocumentState($document) == self::STATE_MANAGED) {
                $prop->setValue($managedCopy, $document);
            } else {
                $targetClass = $this->dm->getClassMetadata(get_class($document));
                $id = $targetClass->getIdentifierValues($document);
                $proxy = $this->getOrCreateProxy($id, $targetClass->name);
                $prop->setValue($managedCopy, $proxy);
                $this->registerDocument($proxy, $id);
            }
        }
    }

    private function cascadeMergeCollection($managedCol, array $mapping)
    {
        if (!$managedCol instanceof PersistentCollection) {
            return;
        }

        if ($mapping['cascade'] & ClassMetadata::CASCADE_MERGE > 0) {
            $managedCol->initialize();
            if (!$managedCol->isEmpty()) {
                // clear managed collection, in casacadeMerge() the collection is filled again.
                $managedCol->unwrap()->clear();
                $managedCol->setDirty(true);
            }
        }
    }

    private function doMerge($document, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $class = $this->dm->getClassMetadata(get_class($document));

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED entities are ignored by the merge operation.
        if ($this->getDocumentState($document) == self::STATE_MANAGED) {
            $managedCopy = $document;
        } else {
            $id = $class->getIdentifierValue($document);
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
                        throw new \InvalidArgumentException("Removed document detected during merge at '$id'. Cannot merge with a removed document.");
                    }
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->dm->find($class->name, $id);
                }

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed document was not found.
                    if ($class->idGenerator !== ClassMetadata::GENERATOR_TYPE_ASSIGNED) {
                        throw new \InvalidArgumentException("Document not found in merge operation: $id");
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
                            array(),
                            isset($mapping['targetDocument']) ? $mapping['targetDocument'] : null
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
                            $mapping['fetchDepth']
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('referrers' === $mapping['type']) {
                    $managedCol = $prop->getValue($managedCopy);
                    if (!$managedCol) {
                        $managedCol = new ReferrersCollection(
                            $this->dm,
                            $managedCopy,
                            $mapping['referenceType'],
                            $mapping['filter']
                        );
                        $prop->setValue($managedCopy, $managedCol);
                        $this->originalData[$managedOid][$fieldName] = $managedCol;
                    }
                    $this->cascadeMergeCollection($managedCol, $mapping);
                } elseif ('parent' === $mapping['type']) {
                    $this->doMergeSingleDocumentProperty($managedCopy, $other, $prop, $mapping);
                } elseif (in_array($mapping['type'], array('locale', 'versionane', 'versioncreated', 'node', 'nodename'))) {
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
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['cascade'] & ClassMetadata::CASCADE_MERGE == 0) {
                continue;
            }
            $related = $class->reflFields[$fieldName]->getValue($document);
            if ($related instanceof Collection || is_array($related)) {
                if ($related instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
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
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['cascade'] & ClassMetadata::CASCADE_REFRESH) {
                $related = $class->reflFields[$fieldName]->getValue($document);
                if ($related instanceof Collection || is_array($related)) {
                    if ($related instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
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
            if ($mapping['cascade'] & ClassMetadata::CASCADE_DETACH == 0) {
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

        foreach ($class->referrersMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if ($mapping['cascade'] & ClassMetadata::CASCADE_DETACH == 0) {
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
    }

    /**
     * Commits the UnitOfWork
     *
     * @param object $document
     */
    public function commit($document = null)
    {
        // Raise preFlush
        if ($this->evm->hasListeners(Event::preFlush)) {
            $this->evm->dispatchEvent(Event::preFlush, new PreFlushEventArgs($this->dm));
        }

        if ($document === null) {
            $this->computeChangeSets();
        } elseif (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } elseif (is_array($document)) {
            foreach ($document as $object) {
                $this->computeSingleDocumentChangeSet($object);
            }
        }

        if ($this->evm->hasListeners(Event::onFlush)) {
            $this->evm->dispatchEvent(Event::onFlush, new OnFlushEventArgs($this->dm));
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

            $this->executeUpdates($this->scheduledAssociationUpdates, false);

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

        // Raise postFlush
        if ($this->evm->hasListeners(Event::postFlush)) {
            $this->evm->dispatchEvent(Event::postFlush, new PostFlushEventArgs($this->dm));
        }

        $this->documentTranslations =
        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledRemovals =
        $this->scheduledMoves =
        $this->scheduledReorders =
        $this->scheduledInserts =
        $this->visitedCollections =
        $this->documentChangesets =
        $this->changesetComputed = array();
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
                if ($aCount == $bCount) {
                    return ($order[$a] < $order[$b]) ? -1 : 1;
                }

                return ($aCount < $bCount) ? -1 : 1;
            }
        );

        $associationChangesets = array();

        foreach ($oids as $oid => $id) {
            $document = $documents[$oid];
            $class = $this->dm->getClassMetadata(get_class($document));
            $parentNode = $this->session->getNode(dirname($id) === '\\' ? '/' : dirname($id));

            $node = $parentNode->addNode(basename($id), $class->nodeType);

            try {
                $node->addMixin('phpcr:managed');
            } catch (NoSuchNodeTypeException $e) {
                throw new PHPCRException('Register phpcr:managed node type first. See https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr:managed');
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
                $class->reflFields[$class->parentMapping]->setValue($document, $this->getOrCreateDocument(null, $parentNode));
            }

            if ($this->writeMetadata) {
                $this->documentClassMapper->writeMetadata($this->dm, $node, $class->name);
            }

            $this->setMixins($class, $node);

            // set the uuid value if it needs to be set
            $uuidFieldName = $class->getUuidFieldName();
            if ($uuidFieldName && $node->hasProperty('jcr:uuid')) {
                $uuidValue = $node->getProperty('jcr:uuid')->getValue();
                $class->setFieldValue($document, $uuidFieldName, $uuidValue);
            }

            foreach ($this->documentChangesets[$oid]['fields'] as $fieldName => $fieldValue) {
                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields)) {
                    continue;
                }

                if (in_array($fieldName, $class->fieldMappings)) {
                    $mapping = $class->mappings[$fieldName];
                    $type = PropertyType::valueFromName($mapping['type']);
                    if (null === $fieldValue) {
                        $types = $node->getMixinNodeTypes();
                        array_push($types, $node->getPrimaryNodeType());
                        $protected = false;
                        foreach ($types as $nt) {
                            /** @var $nt \PHPCR\NodeType\NodeTypeInterface */
                            if (! $nt->canRemoveProperty($mapping['name'])) {
                                $protected = true;
                                break;
                            }
                        }

                        if ($protected) {
                            continue;
                        }
                    }

                    if ($mapping['multivalue'] && $fieldValue) {
                        $fieldValue = (array) $fieldValue;
                        if (isset($mapping['assoc'])) {
                            $node->setProperty($mapping['assoc'], array_keys($fieldValue), $type);
                            $fieldValue = array_values($fieldValue);
                        }
                    }

                    $node->setProperty($mapping['name'], $fieldValue, $type);
                } elseif (in_array($fieldName, $class->referenceMappings)) {
                    $this->scheduledAssociationUpdates[$oid] = $document;

                    //populate $associationChangesets to force executeUpdates($this->scheduledAssociationUpdates)
                    //to only update association fields
                    $data = isset($associationChangesets[$oid]['fields']) ? $associationChangesets[$oid]['fields'] : array();
                    $data[$fieldName] = $fieldValue;
                    $associationChangesets[$oid] = array('fields' => $data, 'reorderings' => array());
                }
            }

            $this->doSaveTranslation($document, $node, $class);

            if (isset($class->lifecycleCallbacks[Event::postPersist])) {
                $class->invokeLifecycleCallbacks(Event::postPersist, $document);
            }
            if ($this->evm->hasListeners(Event::postPersist)) {
                $this->evm->dispatchEvent(Event::postPersist, new LifecycleEventArgs($document, $this->dm));
            }
        }

        $this->documentChangesets = array_merge($this->documentChangesets, $associationChangesets);
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
                if (isset($class->lifecycleCallbacks[Event::preUpdate])) {
                    $class->invokeLifecycleCallbacks(Event::preUpdate, $document);
                    $this->changesetComputed = array_diff($this->changesetComputed, array($oid));
                    $this->computeChangeSet($class, $document);
                }
                if ($this->evm->hasListeners(Event::preUpdate)) {
                    $this->evm->dispatchEvent(Event::preUpdate, new LifecycleEventArgs($document, $this->dm));
                    $this->changesetComputed = array_diff($this->changesetComputed, array($oid));
                    $this->computeChangeSet($class, $document);
                }
            }

            foreach ($this->documentChangesets[$oid]['fields'] as $fieldName => $fieldValue) {
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
                            $node->setProperty($mapping['assoc'], array_keys($value), $type);
                            $value = array_values($value);
                        }
                        $node->setProperty($mapping['name'], $value, $type);
                    } else {
                        $node->setProperty($mapping['name'], $fieldValue, $type);
                    }
                } elseif ($mapping['type'] === $class::MANY_TO_ONE
                    || $mapping['type'] === $class::MANY_TO_MANY
) {
                    if (!$this->writeMetadata) {
                        continue;
                    }
                    if ($node->hasProperty($fieldName) && is_null($fieldValue)) {
                        $node->getProperty($fieldName)->remove();
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
                                    $this->setMixins($refClass, $associatedNode);
                                    if (!$associatedNode->isNodeType('mix:referenceable')) {
                                        throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), get_class($fv)));
                                    }
                                    $refNodesIds[] = $associatedNode->getIdentifier();
                                }
                            }

                            $refNodesIds = empty($refNodesIds) ? null : $refNodesIds;
                            $node->setProperty($fieldName, $refNodesIds, $strategy);
                        }
                    } elseif ($mapping['type'] === $class::MANY_TO_ONE) {
                        if (isset($fieldValue)) {
                            $associatedNode = $this->session->getNode($this->getDocumentId($fieldValue));

                            if ($strategy === PropertyType::PATH) {
                                $node->setProperty($fieldName, $associatedNode->getPath(), $strategy);
                            } else {
                                $refClass = $this->dm->getClassMetadata(get_class($fieldValue));
                                $this->setMixins($refClass, $associatedNode);
                                if (!$associatedNode->isNodeType('mix:referenceable')) {
                                    throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), get_class($fieldValue)));
                                }
                                $node->setProperty($fieldName, $associatedNode->getIdentifier(), $strategy);
                            }
                        }
                    }
                } elseif ('child' === $mapping['type']) {
                    if ($fieldValue === null) {
                        if ($node->hasNode($mapping['name'])) {
                            $child = $node->getNode($mapping['name']);
                            $childDocument = $this->getOrCreateDocument(null, $child);
                            $this->purgeChildren($childDocument);
                            $child->remove();
                        }
                    } elseif ($this->originalData[$oid][$fieldName] && $this->originalData[$oid][$fieldName] !== $fieldValue) {
                        throw PHPCRException::cannotMoveByAssignment(self::objToStr($fieldValue, $this->dm));
                    }
                }
            }

            foreach ($this->documentChangesets[$oid]['reorderings'] as $reorderings) {
                foreach ($reorderings as $srcChildRelPath => $destChildRelPath) {
                    $node->orderBefore($srcChildRelPath, $destChildRelPath);
                }
            }

            $this->doSaveTranslation($document, $node, $class);

            if ($dispatchEvents) {
                if (isset($class->lifecycleCallbacks[Event::postUpdate])) {
                    $class->invokeLifecycleCallbacks(Event::postUpdate, $document);
                }
                if ($this->evm->hasListeners(Event::postUpdate)) {
                    $this->evm->dispatchEvent(Event::postUpdate, new LifecycleEventArgs($document, $this->dm));
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

            if (isset($class->lifecycleCallbacks[Event::preMove])) {
                $class->invokeLifecycleCallbacks(Event::preMove, $document);
            }

            if ($this->evm->hasListeners(Event::preMove)) {
                $this->evm->dispatchEvent(Event::preMove, new MoveEventArgs($document, $this->dm, $sourcePath, $targetPath));
            }

            $this->session->move($sourcePath, $targetPath);

            // update fields nodename and parentMapping if they exist in this type
            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->session->getNode($targetPath); // get node from session, document class might not map it
            if ($class->nodename) {
                $class->setFieldValue($document, $class->nodename, $node->getName());
            }
            if ($class->parentMapping) {
                $class->setFieldValue($document, $class->parentMapping, $this->getOrCreateProxyFromNode($node->getParent()));
            }

            // update all cached children of the document to reflect the move (path id changes)
            foreach ($this->documentIds as $oid => $id) {
                if (0 !== strpos($id, $sourcePath)) {
                    continue;
                }

                $newId = $targetPath.substr($id, strlen($sourcePath));
                $this->documentIds[$oid] = $newId;

                $document = $this->getDocumentById($id);
                if (!$document) {
                    continue;
                }

                unset($this->identityMap[$id]);
                $this->identityMap[$newId] = $document;

                if ($document instanceof Proxy && !$document->__isInitialized()) {
                    $document->__setIdentifier($newId);
                } else {
                    $class = $this->dm->getClassMetadata(get_class($document));
                    if ($class->identifier) {
                        $class->setIdentifierValue($document, $newId);
                        $this->originalData[$oid][$class->identifier] = $newId;
                    }
                }
            }

            if (isset($class->lifecycleCallbacks[Event::postMove])) {
                $class->invokeLifecycleCallbacks(Event::postMove, $document);
            }

            if ($this->evm->hasListeners(Event::postMove)) {
                $this->evm->dispatchEvent(Event::postMove, new MoveEventArgs($document, $this->dm, $sourcePath, $targetPath));
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
        foreach ($documents as $oid => $value) {
            if (!$this->contains($oid)) {
                continue;
            }
            list($parent, $src, $target, $before) = $value;

            $parentNode = $this->session->getNode($this->getDocumentId($parent));
            $children = $parentNode->getNodes();

            // check for src and target ...
            $dest = $target;
            if (isset($children[$src]) && isset($children[$target])) {
                // there is no orderAfter, so we need to find the child after target to use it in orderBefore
                if (!$before) {
                    $dest = null;
                    $found = false;
                    foreach ($children as $name => $child) {
                        if ($name === $target) {
                            $found = true;
                        } elseif ($found) {
                            $dest = $name;
                            break;
                        }
                    }
                }
                $parentNode->orderBefore($src, $dest);
                // set all children collection to initialized = false to force reload after reordering
                $class = $this->dm->getClassMetadata(get_class($parent));
                foreach ($class->childrenMappings as $fieldName) {
                    $children = $class->reflFields[$fieldName]->getValue($parent);
                    $children->setInitialized(false);
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

            if (isset($class->lifecycleCallbacks[Event::postRemove])) {
                $class->invokeLifecycleCallbacks(Event::postRemove, $document);
            }
            if ($this->evm->hasListeners(Event::postRemove)) {
                $this->evm->dispatchEvent(Event::postRemove, new LifecycleEventArgs($document, $this->dm));
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
            throw new \InvalidArgumentException("Document with id $id is not versionable", $e->getCode(), $e);
        }

        try {
            $version = $history->getVersion($versionName);
            $node = $version->getFrozenNode();
        } catch (RepositoryException $e) {
            throw new \InvalidArgumentException("No version $versionName on document $id", $e->getCode(), $e);
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
        $path = $this->getFullVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkin($path); // Checkin Node aka make a new Version
    }

    /**
     * Check out operation - Save all current changes and then check out the
     * Node by path.
     */
    public function checkout($document)
    {
        $path = $this->getFullVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkout($path);
    }

    /**
     * Create a version of the document and check it out right again to
     * continue editing.
     */
    public function checkpoint($document)
    {
        $path = $this->getFullVersionedNodePath($document);
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
     *         oldest version first
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        $path = $this->getDocumentId($document);
        $metadata = $this->dm->getClassMetadata(get_class($document));

        if (!$metadata->versionable) {
            throw new \InvalidArgumentException(sprintf("The document of type '%s' is not versionable", $metadata->getName()));
        }

        $versions = $this->session
            ->getWorkspace()
            ->getVersionManager()
            ->getVersionHistory($path)
            ->getAllLinearVersions();

        $result = array();
        foreach ($versions as $version) {

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
     * @param string $documentVersion the version name to restore
     * @param boolean $removeExisting how to handle identifier collisions
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
     * Removes an document from the identity map. This effectively detaches the
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
            $this->scheduledAssociationUpdates[$oid],
            $this->originalData[$oid],
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
     * Tries to find an document with the given id in the identity map of
     * this UnitOfWork.
     *
     * @param string $id            The document id to look for.
     * @param string $rootClassName The name of the root class of the mapped document hierarchy.
     *
     * @return mixed Returns the document with the specified id if it exists in
     *               this UnitOfWork, FALSE otherwise.
     */
    public function getDocumentById($id)
    {
        if (isset($this->identityMap[$id])) {
            return $this->identityMap[$id];
        }

        return false;
    }

    /**
     * Get the child documents of a given document using an optional filter.
     *
     * This methods gets all child nodes as a collection of documents that matches
     * a given filter (same as PHPCR Node::getNodes)
     *
     * @param object       $document           document instance which children should be loaded
     * @param string|array $filter             optional filter to filter on children's names
     * @param integer      $fetchDepth         optional fetch depth if supported by the PHPCR session
     * @param boolean      $ignoreUntranslated if to ignore children that are not translated to the current locale
     *
     * @return Collection a collection of child documents
     */
    public function getChildren($document, $filter = null, $fetchDepth = null, $ignoreUntranslated = true)
    {
        $oldFetchDepth = $this->setFetchDepth($fetchDepth);
        $node = $this->session->getNode($this->getDocumentId($document));
        $this->setFetchDepth($oldFetchDepth);

        $metadata = $this->dm->getClassMetadata(get_class($document));
        $locale = $this->getLocale($document, $metadata);
        $childrenHints = array();
        if (!is_null($locale)) {
            $childrenHints['locale'] = $locale;
            $childrenHints['fallback'] = true; // if we set locale explicitly this is no longer automatically done
        }

        $childNodes = $node->getNodes($filter);
        $childDocuments = array();
        foreach ($childNodes as $name => $childNode) {
            try {
                $childDocuments[$name] = $this->getOrCreateDocument(null, $childNode, $childrenHints);
            } catch (MissingTranslationException $e) {
                if (!$ignoreUntranslated) {
                    throw $e;
                }
            }
        }

        return new ArrayCollection($childDocuments);
    }

    /**
     * Get all the documents that refer a given document using an optional name
     * and an optional reference type.
     *
     * This methods gets all nodes as a collection of documents that refer (weak
     * and hard) the given document. The property of the referrer node that refers
     * the document needs to match the given name and must store a reference of the
     * given type.
     *
     * @param object $document document instance which referrers should be loaded
     * @param string $type     optional type of the reference the referrer should
     *      have ('weak' or 'hard')
     * @param string $name     optional name to match on referrers reference
     *      property name
     *
     * @return ArrayCollection a collection of referrer documents
     */
    public function getReferrers($document, $type = null, $name = null)
    {
        $node = $this->session->getNode($this->getDocumentId($document));

        $referrerDocuments = array();
        $referrerPropertiesW = array();
        $referrerPropertiesH = array();

        if ($type === null) {
            $referrerPropertiesW = $node->getWeakReferences($name);
            $referrerPropertiesH = $node->getReferences($name);
        } elseif ($type === 'weak') {
            $referrerPropertiesW = $node->getWeakReferences($name);
        } elseif ($type === 'hard') {
            $referrerPropertiesH = $node->getReferences($name);
        }

        foreach ($referrerPropertiesW as $referrerProperty) {
            $referrerNode = $referrerProperty->getParent();
            $referrerDocuments[] = $this->getOrCreateDocument(null, $referrerNode);
        }

        foreach ($referrerPropertiesH as $referrerProperty) {
            $referrerNode = $referrerProperty->getParent();
            $referrerDocuments[] = $this->getOrCreateDocument(null, $referrerNode);
        }

        return new ArrayCollection($referrerDocuments);
    }

    /**
     * Get the object ID for the given document
     *
     * @param object|string $document document instance or document object hash
     *
     * @return string
     *
     * @throws PHPCRException
     */
    public function getDocumentId($document)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        if (empty($this->documentIds[$oid])) {
            $msg = 'Document is not managed and has no id';
            if (is_object($document)) {
                $msg.= ': '.self::objToStr($document);
            }
            throw new PHPCRException($msg);
        }

        return $this->documentIds[$oid];
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
        $this->documentChangesets =
        $this->changesetComputed =
        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledInserts =
        $this->scheduledMoves =
        $this->scheduledReorders =
        $this->scheduledRemovals =
        $this->visitedCollections =
        $this->documentHistory =
        $this->documentVersion = array();

        if ($this->evm->hasListeners(Event::onClear)) {
            $this->evm->dispatchEvent(Event::onClear, new OnClearEventArgs($this->dm));
        }

        $this->session->refresh(false);
    }

    public function getLocalesFor($document)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            throw new MissingTranslationException('This document is not translatable: : '.self::objToStr($document, $this->dm));
        }

        $oid = spl_object_hash($document);
        if ($this->contains($oid)) {
            $node = $this->session->getNode($this->getDocumentId($document));
            $locales = $this->dm->getTranslationStrategy($metadata->translator)->getLocalesFor($document, $node, $metadata);
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

        $locale = $this->getLocale($document, $metadata);
        if ($locale) {
            $this->bindTranslation($document, $locale);
        }

        $oid = spl_object_hash($document);
        if (!empty($this->documentTranslations[$oid])) {
            $strategy = $this->dm->getTranslationStrategy($metadata->translator);
            foreach ($this->documentTranslations[$oid] as $locale => $data) {
                if ($data) {
                    $strategy->saveTranslation($data, $node, $metadata, $locale);
                } else {
                    $strategy->removeTranslation($document, $node, $metadata, $locale);
                }
            }
        }
    }

    /**
     * Load the translatable fields of the document.
     *
     * If locale is not set then it is guessed using the
     * LanguageChooserStrategy class.
     *
     * If the document is not translatable, this method returns immediately.
     *
     * @param object        $document
     * @param ClassMetadata $metadata
     * @param string        $locale   The locale to use or null if the default locale should be used
     * @param boolean       $fallback Whether to do try other languages
     */
    public function doLoadTranslation($document, ClassMetadata $metadata, $locale = null, $fallback = false)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $oid = spl_object_hash($document);
        // Determine which languages we will try to load
        if (!$fallback) {
            if (null === $locale) {
                $localesToTry = array($this->dm->getLocaleChooserStrategy()->getDefaultLocale());
            } else {
                $localesToTry = array($locale);
            }
        } else {
            $localesToTry = $this->getFallbackLocales($document, $metadata, $locale);
        }

        // Load translated fields for current locale
        $node = $this->session->getNode($this->getDocumentId($oid));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);

        foreach ($localesToTry as $desiredLocale) {
            if ($strategy->loadTranslation($document, $node, $metadata, $desiredLocale)) {
                $localeUsed = $desiredLocale;
                break;
            }
        }

        if (empty($localeUsed)) {
            // We tried each possible language without finding the translations
            throw new MissingTranslationException('No translation for '.$node->getPath()." found with strategy '".$metadata->translator.'". Tried the following locales: '.var_export($localesToTry, true));
        }

        // Set the locale
        if ($localeField = $metadata->localeMapping) {
            $metadata->reflFields[$localeField]->setValue($document, $localeUsed);
        }

        $this->documentLocales[$oid] = array('original' => $locale, 'current' => $locale);
    }

    public function removeTranslation($document, $locale)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        if (1 === count($this->getLocalesFor($document))) {
            throw new \RuntimeException('The last translation of a translatable document may not be removed');
        }

        $oid = spl_object_hash($document);
        $this->documentTranslations[$oid][$locale] = null;

        $localeField = $metadata->localeMapping;
        if ($metadata->reflFields[$localeField]->getValue($document) === $locale) {
            $this->documentLocales[$oid] = array('original' => $locale, 'current' => null);

            // Empty the locale field if what we removed was the current language
            $localeField = $metadata->localeMapping;
            if ($localeField) {
                $metadata->reflFields[$localeField]->setValue($document, null);
            }
        }
    }

    private function doRemoveAllTranslations($document, ClassMetadata $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $node = $this->session->getNode($this->getDocumentId($document));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);
        $strategy->removeAllTranslations($document, $node, $metadata);
    }

    private function getLocale($document, ClassMetadata $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return null;
        }

        $localeField = $metadata->localeMapping;
        if ($localeField) {
            $locale = $metadata->reflFields[$localeField]->getValue($document);
        }

        if (empty($locale)) {
            $oid = spl_object_hash($document);
            $locale = isset($this->documentLocales[$oid]['current'])
                ? $this->documentLocales[$oid]['current']
                : $this->dm->getLocaleChooserStrategy()->getLocale();
        }

        return $locale;
    }

    /**
     * Use the LocaleStrategyChooser to return list of fallback locales
     *
     * @param object        $document The document object
     * @param ClassMetadata $metadata The metadata of the document class
     * @param $desiredLocale
     *
     * @return array
     */
    private function getFallbackLocales($document, ClassMetadata $metadata, $desiredLocale)
    {
        $strategy = $this->dm->getLocaleChooserStrategy();

        return $strategy->getPreferredLocalesOrder($document, $metadata, $desiredLocale);
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
            : get_class($obj).'@'.spl_object_hash($obj);

        if ($dm) {
            try {
                $id = $dm->getUnitOfWork()->getDocumentId($obj);
                $string .= " ($id)";
            } catch (\Exception $e) {
                $class = $dm->getClassMetadata(get_class($obj));
                $id = $class->getIdentifierValue($obj);
                $string .= " ($id)";
            }
        }

        return $string;
    }

    private function getFullVersionedNodePath($document)
    {
        $path = $this->getDocumentId($document);
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if ($metadata->versionable !== 'full') {
            throw new \InvalidArgumentException(sprintf("The document at '%s' is not full versionable", $path));
        }

        $node = $this->session->getNode($path);
        $node->addMixin('mix:versionable');

        return $path;
    }

    private function setMixins(Mapping\ClassMetadata $metadata, NodeInterface $node)
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

        // we manually set the uuid to allow creating referenced and referencing document without flush in between.
        if ($node->isNodeType('mix:referenceable') && !$node->hasProperty('jcr:uuid')) {
            // TODO do we need to check with the storage backend if the generated id really is unique?
            $node->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        }
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
}
