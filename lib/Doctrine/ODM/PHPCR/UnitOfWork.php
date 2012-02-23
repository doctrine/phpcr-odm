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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event\OnFlushEventArgs;
use Doctrine\ODM\PHPCR\Event\OnClearEventArgs;
use Doctrine\ODM\PHPCR\Proxy\Proxy;

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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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
    const STATE_MOVED = 5;

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
    private $documentRevisions = array();

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
    private $scheduledRemovals = array();

    /**
     * @var array
     */
    private $visitedCollections = array();

    /**
     * @var array
     */
    private $multivaluePropertyCollections = array();

    /**
     * @var array
     */
    private $idGenerators = array();

    /**
     * \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var EventManager
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
    }

    /**
     * Create a document given class, data and the doc-id and revision
     *
     * Supported hints are
     * - refresh: reload the fields from the database
     * - locale: use this locale instead of the one from the annotation or the default
     * - fallback: whether to try other languages or throw a not found
     *      exception if the desired locale is not found. defaults to true if
     *      not set and locale is not given either.
     *
     * @param null|string $className
     * @param \PHPCR\NodeInterface $node
     * @param array $hints
     * @return object
     */
    public function createDocument($className, $node, array &$hints = array())
    {
        $requestedClassName = $className;
        $className = $this->documentClassMapper->getClassName($this->dm, $node, $className);
        $class = $this->dm->getClassMetadata($className);

        $documentState = array();
        $nonMappedData = array();
        $id = $node->getPath();

        // second param is false to get uuid rather than dereference reference properties to node instances
        $properties = $node->getPropertiesValues(null, false);

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (isset($properties[$mapping['name']])) {
                if ($mapping['multivalue']) {
                    $collection = $properties[$mapping['name']] instanceof Collection
                        ? $properties[$mapping['name']]
                        : new ArrayCollection((array)$properties[$mapping['name']])
                    ;
                    $documentState[$fieldName] = new MultivaluePropertyCollection($collection);
                    $this->multivaluePropertyCollections[] = $documentState[$fieldName];
                } else {
                    $documentState[$fieldName] = $properties[$mapping['name']];
                }
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
        foreach ($class->associationsMappings as $assocOptions) {
            if (!$node->hasProperty($assocOptions['fieldName'])) {
                continue;
            }

            if ($assocOptions['type'] & ClassMetadata::MANY_TO_ONE) {
                $refNodeUUIDs[] = $node->getProperty($assocOptions['fieldName'])->getString();
            }
        }

        if (count($refNodeUUIDs)) {
            $this->session->getNodesByIdentifier($refNodeUUIDs);
        }

        // initialize inverse side collections
        foreach ($class->associationsMappings as $assocName => $assocOptions) {
            if ($assocOptions['type'] & ClassMetadata::MANY_TO_ONE) {
                if (!$node->hasProperty($assocOptions['fieldName'])) {
                    continue;
                }

                $referencedNode = $node->getPropertyValue($assocOptions['fieldName']);
                $referencedClass = isset($assocOptions['targetDocument'])
                    ? $this->dm->getMetadataFactory()->getMetadataFor(ltrim($assocOptions['targetDocument'], '\\'))->name : null;
                $proxy = $referencedClass
                    ? $this->createProxy($referencedNode->getPath(), $referencedClass)
                    : $this->createProxyFromNode($referencedNode);
                $documentState[$class->associationsMappings[$assocName]['fieldName']] = $proxy;
            } elseif ($assocOptions['type'] & ClassMetadata::MANY_TO_MANY) {
                if (!$node->hasProperty($assocOptions['fieldName'])) {
                    continue;
                }
                $referencedDocUUIDs = array();
                foreach ($node->getProperty($assocOptions['fieldName'])->getString() as $uuid) {
                    $referencedDocUUIDs[] = $uuid;
                }
                if (count($referencedDocUUIDs) > 0) {
                    $coll = new ReferenceManyCollection($this->dm, $referencedDocUUIDs, $assocOptions['targetDocument']);
                    $documentState[$class->associationsMappings[$assocName]['fieldName']] = $coll;
                }
            }
        }

        if ($class->parentMapping && $node->getDepth() > 0) {
            // do not map parent to self if we are at root
            $documentState[$class->parentMapping] = $this->createProxyFromNode($node->getParent());
        }

        foreach ($class->childMappings as $childName => $mapping) {
            $documentState[$class->childMappings[$childName]['fieldName']] = $node->hasNode($mapping['name'])
                ? $this->createProxyFromNode($node->getNode($mapping['name']))
                : null;
        }

        $document = $this->getDocumentById($id);
        if ($document) {
            $overrideLocalValuesOid = empty($hints['refresh']) ? false : spl_object_hash($document);
        } else {
            $document = $class->newInstance();
            $overrideLocalValuesOid = $this->registerDocument($document, $id);
        }

        if (isset($requestedClassName) && $this->validateDocumentName && !($document instanceof $requestedClassName)) {
            $msg = "Doctrine metadata mismatch! Requested type '$requestedClassName' type does not match type '".get_class($document)."' stored in the metadata";
            throw new \InvalidArgumentException($msg);
        }

        foreach ($class->childrenMappings as $mapping) {
            $documentState[$mapping['fieldName']] = new ChildrenCollection($this->dm, $document, $mapping['filter']);
        }

        foreach ($class->referrersMappings as $mapping) {
            $documentState[$mapping['fieldName']] = new ReferrersCollection($this->dm, $document, $mapping['referenceType'], $mapping['filter']);
        }

        if ($overrideLocalValuesOid) {
            $this->nonMappedData[$overrideLocalValuesOid] = $nonMappedData;
            foreach ($class->reflFields as $prop => $reflFields) {
                $value = isset($documentState[$prop]) ? $documentState[$prop] : null;
                $reflFields->setValue($document, $value);
                $this->originalData[$overrideLocalValuesOid][$prop] = $value;
            }
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

    public function createProxyFromNode($node)
    {
        $targetId = $node->getPath();
        $className = $this->documentClassMapper->getClassName($this->dm, $node);
        return $this->createProxy($targetId, $className);
    }

    /**
     * Create a proxy instance or return an existing document
     *
     * @param $targetId
     * @param $className
     *
     * @return object
     */
    public function createProxy($targetId, $className)
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
     * @param Proxy $document
     * @return void
     */
    public function refreshDocumentForProxy($className, Proxy $document)
    {
        $node = $this->session->getNode($document->__getIdentifier());
        $hints = array('refresh' => true);
        $this->createDocument($className, $node, $hints);
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
        $state = $this->getDocumentState($document);
        if ($state !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Document has to be managed to be able to bind a translation '.self::objToStr($document, $this->dm));
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($class)) {
            throw new PHPCRException('This document is not translatable, do not use bindTranslation: '.self::objToStr($document, $this->dm));
        }

        // Set the @Locale field
        $localeField = $class->localeMapping;
        if ($localeField) {
            $class->reflFields[$localeField]->setValue($document, $locale);
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
     * Schedule insertion of this document and cascade if neccessary.
     *
     * @param object $document
     * @param string $id
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

        $this->cascadeScheduleParentInsert($class, $document, $visited);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                $this->persistNew($class, $document, $overrideIdGenerator);
                break;
            case self::STATE_MANAGED:
                // TODO: Change Tracking Deferred Explicit
                break;
            case self::STATE_MOVED:
                unset($this->scheduledMoves[$oid]);
                $this->setDocumentState($oid, self::STATE_MANAGED);
                break;
            case self::STATE_REMOVED:
                unset($this->scheduledRemovals[$oid]);
                $this->setDocumentState($oid, self::STATE_MANAGED);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException('Detached document passed to persist(): '.self::objToStr($document, $this->dm));
                break;
        }

        $this->cascadeScheduleInsert($class, $document, $visited);
    }

    /**
     *
     * @param ClassMetadata $class
     * @param object $document
     * @param array $visited
     */
    private function cascadeScheduleInsert($class, $document, &$visited)
    {
        foreach ($class->associationsMappings as $assocName => $assoc) {
            $related = $class->reflFields[$assocName]->getValue($document);
            if ($related !== null) {
                if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::TO_ONE) {
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

        $id = $class->getIdentifierValue($document);
        foreach ($class->childMappings as $childName => $mapping) {
            $child = $class->reflFields[$childName]->getValue($document);
            if ($child !== null && $this->getDocumentState($child) === self::STATE_NEW) {
                $childClass = $this->dm->getClassMetadata(get_class($child));
                $childId = $id.'/'.$mapping['name'];
                $childClass->setIdentifierValue($child, $childId);
                $this->doScheduleInsert($child, $visited, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            }
        }

        foreach ($class->childrenMappings as $childName => $mapping) {
            $children = $class->reflFields[$childName]->getValue($document);
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
                break;
        }

        $this->scheduledMoves[$oid] = array($document, $targetPath);
        $this->setDocumentState($oid, self::STATE_MOVED);
    }

    public function scheduleRemove($document)
    {
        $oid = spl_object_hash($document);

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_NEW:
                unset($this->scheduledInserts[$oid]);
                break;
            case self::STATE_MOVED:
                unset($this->scheduledMoves[$oid]);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException('Detached document passed to remove(): '.self::objToStr($document, $this->dm));
                break;
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
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->childMappings as $childName => $mapping) {
            $child = $class->reflFields[$childName]->getValue($document);
            if ($child !== null) {
                $this->purgeChildren($child);
                $this->unregisterDocument($child);
            }
        }
    }

    /**
     * @param object|string $document
     * @param int $state
     */
    private function setDocumentState($document, $state)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        $this->documentState[$oid] = $state;
    }

    /**
     * @param object $document
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

            if ($class->idGenerator === ClassMetadata::GENERATOR_TYPE_ASSIGNED
                || $class->idGenerator === ClassMetadata::GENERATOR_TYPE_PARENT
            ) {
                if ($this->getDocumentById($id)) {
                    return self::STATE_DETACHED;
                }

                return $this->dm->getPhpcrSession()->nodeExists($id)
                    ? self::STATE_DETACHED : self::STATE_NEW;
            }

            return self::STATE_DETACHED;
        }

        return $this->documentState[$oid];
    }

    /**
     * Detects the changes that need to be persisted
     *
     * @param object $document
     *
     * @return void
     */
    private function detectChangedDocuments($document = null)
    {
        if ($document) {
            $state = $this->getDocumentState($document);
            if ($state !== self::STATE_MANAGED) {
                if ($state === self::STATE_MOVE) {
                    return;
                }

                throw new \InvalidArgumentException('Document has to be managed or moved for single computation '.self::objToStr($document, $this->dm));
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
        } else {
            foreach ($this->identityMap as $document) {
                $state = $this->getDocumentState($document);
                if ($state === self::STATE_MANAGED) {
                    $class = $this->dm->getClassMetadata(get_class($document));
                    $this->computeChangeSet($class, $document);
                }
            }
        }
    }

    /**
     * @param ClassMetadata $class
     * @param object $document
     * @return void
     */
    private function computeChangeSet(ClassMetadata $class, $document)
    {
        if ($document instanceof Proxy && !$document->__isInitialized()) {
            return;
        }

        $actualData = array();
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            $value = $reflProperty->getValue($document);
            if ($class->isCollectionValuedAssociation($fieldName)
                && $value !== null
                && !($value instanceof PersistentCollection)
            ) {
                if (!$value instanceof Collection) {
                    $value = new MultivaluePropertyCollection(new ArrayCollection($value), true);
                    $this->multivaluePropertyCollections[] = $value;
                }

                $collection = $value;

                $class->reflFields[$fieldName]->setValue($document, $collection);

                $actualData[$fieldName] = $collection;
            } else {
                $actualData[$fieldName] = $value;
            }
        }

        // unset the version info fields if they have values, they are not to be managed by the user in write scenarios.
        if ($class->versionable) {
            unset($actualData[$class->versionNameField]);
            unset($actualData[$class->versionCreatedField]);
        }

        $oid = spl_object_hash($document);
        if (!isset($this->originalData[$oid])) {
            // Document is New and should be inserted
            $this->originalData[$oid] = $actualData;
            $this->documentChangesets[$oid] = $actualData;
            $this->scheduledInserts[$oid] = $document;
        } else {
            if (isset($this->originalData[$oid][$class->nodename])
                && isset($actualData[$class->nodename])
                && $this->originalData[$oid][$class->nodename] !== $actualData[$class->nodename]
            ) {
                throw new PHPCRException('The Nodename property is immutable ('.$this->originalData[$oid][$class->nodename].' !== '.$actualData[$class->nodename].'). Please use DocumentManager::move to rename the document: '.self::objToStr($document, $this->dm));
            }
            if (isset($this->originalData[$oid][$class->parentMapping])
                && isset($actualData[$class->parentMapping])
                && $this->originalData[$oid][$class->parentMapping] !== $actualData[$class->parentMapping]
            ) {
                throw new PHPCRException('The ParentDocument property is immutable ('.$class->getIdentifierValue($this->originalData[$oid][$class->parentMapping]).' !== '.$class->getIdentifierValue($actualData[$class->parentMapping]).'). Please use PHPCR\Session::move to move the document: '.self::objToStr($document, $this->dm));
            }
            if (isset($this->originalData[$oid][$class->identifier])
                && isset($actualData[$class->identifier])
                && $this->originalData[$oid][$class->identifier] !== $actualData[$class->identifier]
            ) {
                throw new PHPCRException('The Id is immutable ('.$this->originalData[$oid][$class->identifier].' !== '.$actualData[$class->identifier].'). Please use DocumentManager::move to move the document: '.self::objToStr($document, $this->dm));
            }

            // Document is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data

            $changed = false;
            foreach ($actualData as $fieldName => $fieldValue) {
                if (!isset($class->fieldMappings[$fieldName])
                    && !isset($class->childMappings[$fieldName])
                    && !isset($class->associationsMappings[$fieldName])
                    && !isset($class->referrersMappings[$fieldName])
                    && !isset($class->parentMapping[$fieldName])
                    && !isset($class->nodename)
                ) {
                    continue;
                }
                if ($class->isCollectionValuedAssociation($fieldName)) {
                    if (!$fieldValue instanceof PersistentCollection) {
                        // if its not a persistent collection and the original value changed. otherwise it could just be null
                        $changed = true;
                        break;
                    } elseif ($fieldValue->changed()) {
                        $this->visitedCollections[] = $fieldValue;
                        $changed = true;
                        break;
                    }
                } elseif ($this->originalData[$oid][$fieldName] !== $fieldValue) {
                    $changed = true;
                    break;
                } elseif ($fieldValue instanceof ReferenceManyCollection) {
                    if ($fieldValue->changed()) {
                        $changed = true;
                    }
                }
            }

            if (isset($this->documentLocales[$oid])
                && $this->documentLocales[$oid]['current'] !== $this->documentLocales[$oid]['original']
            ) {
                $changed = true;
            }

            if ($changed) {
                $this->documentChangesets[$oid] = $actualData;
                $this->scheduledUpdates[$oid] = $document;
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

        $id = $class->getIdentifierValue($document);
        foreach ($class->childMappings as $name => $childMapping) {
            if ($actualData[$name]) {
                if ($this->originalData[$oid][$name] && $this->originalData[$oid][$name] !== $actualData[$name]) {
                    throw new PHPCRException('Cannot move/copy children by assignment as it would be ambiguous. Please use the DocumentManager::move() or PHPCR\Session::copy() operations for this: '.self::objToStr($document, $this->dm));
                }
                $this->computeChildChanges($childMapping, $actualData[$name], $id);
            }
        }

        foreach ($class->associationsMappings as $assocName => $assoc) {
            if ($actualData[$assocName]) {
                if (is_array($actualData[$assocName]) || $actualData[$assocName] instanceof Collection) {
                    foreach ($actualData[$assocName] as $ref) {
                        if ($ref !== null) {
                            $this->computeReferenceChanges($ref);
                        }
                    }
                } else {
                    $this->computeReferenceChanges($actualData[$assocName]);
                }
            }
        }

        foreach ($class->referrersMappings as $name => $referrerMapping) {
            if ($this->originalData[$oid][$name]) {
                foreach ($this->originalData[$oid][$name] as $referrer) {
                    $this->computeReferrerChanges($referrer);
                }
            }
        }
    }

    /**
     * Computes the changes of a child.
     *
     * @param mixed $child the child document.
     */
    private function computeChildChanges($mapping, $child, $parentId)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($child));
        $state = $this->getDocumentState($child);

        if ($state === self::STATE_NEW) {
            $targetClass->setIdentifierValue($child, $parentId.'/'.$mapping['name']);
            $this->persistNew($targetClass, $child, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            $this->computeChangeSet($targetClass, $child);
        } elseif ($state === self::STATE_REMOVED) {
            throw new \InvalidArgumentException('Removed child document detected during flush');
        } elseif ($state === self::STATE_DETACHED) {
            throw new \InvalidArgumentException('A detached document was found through a child relationship during cascading a persist operation.');
        }
    }

    /**
     * Computes the changes of a reference.
     *
     * @param mixed $reference the referenced document.
     */
    private function computeReferenceChanges($reference)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($reference));
        $state = $this->getDocumentState($reference);

        switch ($state) {
            case self::STATE_NEW:
                $this->persistNew($targetClass, $reference, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
                $this->computeChangeSet($targetClass, $reference);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException('A detached document was found through a reference during cascading a persist operation.');
        }
    }

    /**
     * Computes the changes of a referrer.
     *
     * @param mixed $referrer the referenced document.
     */
    private function computeReferrerChanges($referrer)
    {
        $targetClass = $this->dm->getClassMetadata(get_class($referrer));
        $state = $this->getDocumentState($referrer);

        switch ($state) {
            case self::STATE_NEW:
                $this->persistNew($targetClass, $referrer, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
                $this->computeChangeSet($targetClass, $referrer);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException('A detached document was found through a referrer during cascading a persist operation.');
        }
    }

    /**
     * Persist new document, marking it managed and generating the id and the node.
     *
     * This method is either called through `DocumentManager#persist()` or during `DocumentManager#flush()`,
     * when persistence by reachability is applied.
     *
     * @param ClassMetadata $class
     * @param object $document
     * @return void
     */
    public function persistNew($class, $document, $overrideIdGenerator = null)
    {
        $generator = $overrideIdGenerator ? $overrideIdGenerator : $class->idGenerator;
        $id = $this->getIdGenerator($generator)->generate($document, $class, $this->dm);
        $this->registerDocument($document, $id);

        if ($generator === ClassMetadata::GENERATOR_TYPE_ASSIGNED
            || $generator === ClassMetadata::GENERATOR_TYPE_PARENT
        ) {
            $class->setIdentifierValue($document, $id);
        }

        if (isset($class->lifecycleCallbacks[Event::prePersist])) {
            $class->invokeLifecycleCallbacks(Event::prePersist, $document);
        }
        if ($this->evm->hasListeners(Event::prePersist)) {
            $this->evm->dispatchEvent(Event::prePersist, new LifecycleEventArgs($document, $this->dm));
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
     * Executes a detach operation on the given entity.
     *
     * @param object $document
     * @param array $visited
     */
    private function doDetach($document, array &$visited)
    {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        $state = $this->getDocumentState($document);
        switch ($state) {
            case self::STATE_MANAGED:
                $this->unregisterDocument($document);
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }

        $this->cascadeDetach($document, $visited);
    }

    /**
     * Cascades a detach operation to associated documents.
     *
     * @param object $document
     * @param array $visited
     */
    private function cascadeDetach($document, array &$visited)
    {
        $class = $this->dm->getClassMetadata(get_class($document));

        foreach ($class->childrenMappings as $assoc) {
            $relatedDocuments = $class->reflFields[$assoc['fieldName']]->getValue($document);
            if ($relatedDocuments instanceof Collection) {
                if ($relatedDocuments instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedDocuments = $relatedDocuments->unwrap();
                }
                foreach ($relatedDocuments as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } else if ($relatedDocuments !== null) {
                $this->doDetach($relatedDocuments, $visited);
            }
        }

        foreach ($class->referrersMappings as $assoc) {
            $relatedDocuments = $class->reflFields[$assoc['fieldName']]->getValue($document);
            if ($relatedDocuments instanceof Collection) {
                if ($relatedDocuments instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedDocuments = $relatedDocuments->unwrap();
                }
                foreach ($relatedDocuments as $relatedDocument) {
                    $this->doDetach($relatedDocument, $visited);
                }
            } else if ($relatedDocuments !== null) {
                $this->doDetach($relatedDocuments, $visited);
            }
        }
    }

    /**
     * Commits the UnitOfWork
     *
     * @param object $document
     *
     * @return void
     */
    public function commit($document = null)
    {
        $this->detectChangedDocuments($document);

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

        foreach ($this->multivaluePropertyCollections as $col) {
            $col->takeSnapshot();
        }

        $this->documentTranslations =
        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledRemovals =
        $this->scheduledMoves =
        $this->scheduledInserts =
        $this->visitedCollections = array();
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
        uasort($oids, function ($a, $b) use ($order)
            {
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

        foreach ($oids as $oid => $id) {
            $document = $documents[$oid];
            $class = $this->dm->getClassMetadata(get_class($document));
            $parentNode = $this->session->getNode(dirname($id) === '\\' ? '/' : dirname($id));

            $node = $parentNode->addNode(basename($id), $class->nodeType);

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
                $class->reflFields[$class->parentMapping]->setValue($document, $this->createDocument(null, $parentNode));
            }

            if ($this->writeMetadata) {
                $this->documentClassMapper->writeMetadata($this->dm, $node, $class->name);
            }

            try {
                $node->addMixin('phpcr:managed');
            } catch (NoSuchNodeTypeException $e) {
                throw new PHPCRException('Register phpcr:managed node type first. See https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr:managed');
            }

            $this->setMixins($class, $node);

            foreach ($this->documentChangesets[$oid] as $fieldName => $fieldValue) {
                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields)) {
                    continue;
                }

                if (isset($class->fieldMappings[$fieldName])) {
                    $type = PropertyType::valueFromName($class->fieldMappings[$fieldName]['type']);
                    if (null === $fieldValue && $node->hasProperty($class->fieldMappings[$fieldName]['name'])) {
                        // Check whether we can remove the property first
                        $property = $node->getProperty($class->fieldMappings[$fieldName]['name']);
                        $definition = $property->getDefinition();
                        if ($definition && ($definition->isMandatory() || $definition->isProtected())) {
                            continue;
                        }
                    }

                    if ($class->fieldMappings[$fieldName]['multivalue']) {
                        $value = $fieldValue === null ? null : $fieldValue->toArray();
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $value, $type);
                    } else {
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $fieldValue, $type);
                    }
                } elseif (isset($class->associationsMappings[$fieldName])) {
                    $this->scheduledAssociationUpdates[$oid] = $document;
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
    }

    /**
     * Executes all document updates
     *
     * @param array $documents array of all to be updated documents
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
                    $this->computeChangeSet($class, $document);
                }
                if ($this->evm->hasListeners(Event::preUpdate)) {
                    $this->evm->dispatchEvent(Event::preUpdate, new LifecycleEventArgs($document, $this->dm));
                    $this->computeChangeSet($class, $document);
                }
            }

            foreach ($this->documentChangesets[$oid] as $fieldName => $fieldValue) {
                // Ignore translatable fields (they will be persisted by the translation strategy)
                if (in_array($fieldName, $class->translatableFields)) {
                    continue;
                }

                if (isset($class->fieldMappings[$fieldName])) {
                    $type = PropertyType::valueFromName($class->fieldMappings[$fieldName]['type']);
                    if ($class->fieldMappings[$fieldName]['multivalue']) {
                        $value = $fieldValue === null ? null : $fieldValue->toArray();
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $value, $type);
                    } else {
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $fieldValue, $type);
                    }
                } elseif (isset($class->associationsMappings[$fieldName]) && $this->writeMetadata) {

                    if ($node->hasProperty($class->associationsMappings[$fieldName]['fieldName']) && is_null($fieldValue)) {
                        $node->getProperty($class->associationsMappings[$fieldName]['fieldName'])->remove();
                        continue;
                    }

                    $type = $class->associationsMappings[$fieldName]['weak']
                        ? PropertyType::WEAKREFERENCE : PropertyType::REFERENCE;

                    if ($class->associationsMappings[$fieldName]['type'] === $class::MANY_TO_MANY) {
                        if (isset($fieldValue)) {
                            $refNodesIds = array();
                            foreach ($fieldValue as $fv) {
                                if ($fv === null) {
                                    continue;
                                }

                                $associatedNode = $this->session->getNode($this->getDocumentId($fv));
                                $refClass = $this->dm->getClassMetadata(get_class($fv));
                                $this->setMixins($refClass, $associatedNode);
                                if (!$associatedNode->isNodeType('mix:referenceable')) {
                                    throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), get_class($fv)));
                                }
                                $refNodesIds[] = $associatedNode->getIdentifier();
                            }

                            if (!empty($refNodesIds)) {
                                $node->setProperty($class->associationsMappings[$fieldName]['fieldName'], $refNodesIds, $type);
                            }
                        }
                    } elseif ($class->associationsMappings[$fieldName]['type'] === $class::MANY_TO_ONE) {
                        if (isset($fieldValue)) {
                            $associatedNode = $this->session->getNode($this->getDocumentId($fieldValue));
                            $refClass = $this->dm->getClassMetadata(get_class($fieldValue));
                            $this->setMixins($refClass, $associatedNode);
                            if (!$associatedNode->isNodeType('mix:referenceable')) {
                                throw new PHPCRException(sprintf('Referenced document %s is not referenceable. Use referenceable=true in Document annotation: '.self::objToStr($document, $this->dm), get_class($fieldValue)));
                            }
                            $node->setProperty($class->associationsMappings[$fieldName]['fieldName'], $associatedNode->getIdentifier(), $type);
                        }
                    }
                } elseif (isset($class->childMappings[$fieldName])) {
                    if ($fieldValue === null) {
                        if ($node->hasNode($class->childMappings[$fieldName]['name'])) {
                            $child = $node->getNode($class->childMappings[$fieldName]['name']);
                            $childDocument = $this->createDocument(null, $child);
                            $this->purgeChildren($childDocument);
                            $child->remove();
                        }
                    } elseif ($this->originalData[$oid][$fieldName] && $this->originalData[$oid][$fieldName] !== $fieldValue) {
                        throw new PHPCRException('Cannot move/copy children by assignment as it would be ambiguous. Please use the DocumentManager::move() or PHPCR\Session::copy() operations for this.');
                    }
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

            $class = $this->dm->getClassMetadata(get_class($document));
            $path = $class->getIdentifierValue($document);
            if ($path === $targetPath) {
                continue;
            }

            $this->session->move($path, $targetPath);

            foreach ($this->documentIds as $oid => $id) {
                if (0 !== strpos($id, $path)) {
                    continue;
                }

                $newId = $targetPath.substr($id, strlen($path));
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
            if (!$this->contains($oid)) {
                continue;
            }

            $id = $this->getDocumentId($document);
            $node = $this->session->getNode($id);

            $class = $this->dm->getClassMetadata(get_class($document));
            $this->doRemoveAllTranslations($document, $class);

            $node->remove();
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

        $hints = array('versionName' => $versionName);
        $frozenDocument = $this->createDocument($className, $node, $hints);
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
     *
     * @return void
     */
    public function checkin($document)
    {
        $path = $this->getFullVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkin($path); // Checkin Node aka make a new Version
    }

    /**
     * Check out operation - Save all current changes and then check out the Node by path.
     *
     * @return void
     */
    public function checkout($document)
    {
        $path = $this->getFullVersionedNodePath($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkout($path);
    }

    /**
     * Check point operation
     *
     * @return void
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
     * @param int $limit an optional limit to only get the latest $limit information
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
     * Check restore - Save all current changes and then restore the Node by path.
     *
     * @return void
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
     * @ignore
     * @param object $document
     */
    private function unregisterDocument($document)
    {
        $oid = spl_object_hash($document);

        if ($this->contains($document)) {
            $id = $this->getDocumentId($document);
            unset($this->identityMap[$id]);
        }

        unset($this->scheduledRemovals[$oid],
            $this->scheduledUpdates[$oid],
            $this->scheduledMoves[$oid],
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
    }

    /**
     * @param object $document
     * @param string $id The document id to look for.
     * @return the generated object id
     */
    private function registerDocument($document, $id)
    {
        $oid = spl_object_hash($document);
        $this->documentIds[$oid] = $id;
        $this->identityMap[$id] = $document;
        $this->setDocumentState($oid, self::STATE_MANAGED);

        return $oid;
    }

    /**
     * @param object $document
     * @return bool
     */
    public function contains($document)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        return isset($this->documentIds[$oid]);
    }

    /**
     * Tries to find an document with the given id in the identity map of
     * this UnitOfWork.
     *
     * @param string $id The document id to look for.
     * @param string $rootClassName The name of the root class of the mapped document hierarchy.
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
     * @param $document document instance which children should be loaded
     * @param string|array $filter optional filter to filter on children's names
     * @return a collection of child documents
     */
    public function getChildren($document, $filter = null)
    {
        $node = $this->session->getNode($this->getDocumentId($document));
        $childNodes = $node->getNodes($filter);
        $childDocuments = array();
        foreach ($childNodes as $name => $childNode) {
            $childDocuments[$name] = $this->createDocument(null, $childNode);
        }
        return new ArrayCollection($childDocuments);
    }

    /**
     * Get all the documents that refer a given document using an optional name
     * and an optional reference type.
     *
     * This methods gets all nodes as a collection of documents that refer (weak
     * and hard) the given document. The property of the referrer node that referes
     * the document needs to match the given name and must store a reference of the
     * given type.
     * @param $document document instance which referrers should be loaded
     * @param string $type optional type of the reference the referrer should have ('weak' or 'hard')
     * @param string $name optional name to match on referrers reference property
     * name
     * @return a collection of referrer documents
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
            $referrerDocuments[] = $this->createDocument(null, $referrerNode);
        }

        foreach ($referrerPropertiesH as $referrerProperty) {
            $referrerNode = $referrerProperty->getParent();
            $referrerDocuments[] = $this->createDocument(null, $referrerNode);
        }

        return new ArrayCollection($referrerDocuments);
    }

    /**
     * Get the object ID for the given document
     *
     * @throws PHPCRException
     * @param  object $document
     * @return string
     */
    public function getDocumentId($document)
    {
        $oid = is_object($document) ? spl_object_hash($document) : $document;
        if (empty($this->documentIds[$oid])) {
            $msg = 'Document is not managed and has no id';
            if (is_object($document)) {
                $msg.= ': '.self::objToStr($document, $this->dm);
            }
            throw new PHPCRException($msg);
        }

        return $this->documentIds[$oid];
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * @param object
     * @return void
     */
    public function initializeObject($obj)
    {
        if ($obj instanceof Proxy) {
            $obj->__load();
        } else if ($obj instanceof PersistentCollection) {
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
        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledInserts =
        $this->scheduledMoves =
        $this->scheduledRemovals =
        $this->visitedCollections =
        $this->documentHistory =
        $this->documentVersion = array();

        if ($this->evm->hasListeners(Event::onClear)) {
            $this->evm->dispatchEvent(Event::onClear, new OnClearEventArgs($this->dm));
        }

        return $this->session->refresh(false);
    }

    public function getLocalesFor($document)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        if (!$this->isDocumentTranslatable($metadata)) {
            throw new PHPCRException('This document is not translatable: : '.self::objToStr($document, $this->dm));
        }

        $oid = spl_object_hash($document);
        if ($this->contains($oid)) {
            $node = $this->session->getNode($this->getDocumentId($document));
            $locales = $this->dm->getTranslationStrategy($metadata->translator)->getLocalesFor($document, $node, $metadata);
        } else {
            $locales = array();
        }

        if (isset($this->documentTranslations[$oid])) {
            $locales = array_unique(array_merge($locales, array_keys($this->documentTranslations[$oid])));
        }

        return $locales;
    }

    private function doSaveTranslation($document, $node, $metadata)
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
                $strategy->saveTranslation($data, $node, $metadata, $locale);
            }
        }
    }

    /**
     * Load the translatable fields of the document.
     *
     * If locale is not set then it is guessed using the
     * LanguageChooserStrategy class.
     *
     * If the document is not translatable, this method returns immediatly.
     *
     * @param $document
     * @param $metadata
     * @param string $locale The locale to use or null if the default locale should be used
     * @param boolean $fallback Whether to do try other languages
     *
     * @return void
     */
    public function doLoadTranslation($document, $metadata = null, $locale = null, $fallback = false)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        // TODO: if locale is null, just get default locale, regardless of fallback or not

        // Determine which languages we will try to load
        if (!$fallback) {
            if (null === $locale) {
                throw new \InvalidArgumentException('Error while loading the translations: no locale specified and the language fallback is disabled: '.self::objToStr($document, $this->dm));
            }

            $localesToTry = array($locale);
        } else {
            $localesToTry = $this->getFallbackLocales($document, $metadata, $locale);
        }

        $oid = spl_object_hash($document);
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
            throw new \RuntimeException('No translation for '.$node->getPath()." found with strategy '".$metadata->translator.'". Tried the following locales: '.var_export($localesToTry, true));
        }

        // Set the locale
        if ($localeField = $metadata->localeMapping) {
            $metadata->reflFields[$localeField]->setValue($document, $localeUsed);
        }

        $this->documentLocales[$oid] = array('original' => $locale, 'current' => $locale);
    }

    private function doRemoveTranslation($document, $metadata, $locale)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $node = $this->session->getNode($this->getDocumentId($document));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);
        $strategy->removeTranslation($document, $node, $metadata, $locale);

        // Empty the locale field if what we removed was the current language
        if ($localeField = $metadata->localeMapping) {
            if ($metadata->reflFields[$localeField]->getValue($document) === $locale) {
                $metadata->reflFields[$localeField]->setValue($document, null);
            }
        }
    }

    private function doRemoveAllTranslations($document, $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $node = $this->session->getNode($this->getDocumentId($document));
        $strategy = $this->dm->getTranslationStrategy($metadata->translator);
        $strategy->removeAllTranslations($document, $node, $metadata);
    }

    private function getLocale($document, $metadata)
    {
        if (!$this->isDocumentTranslatable($metadata)) {
            return;
        }

        $localeField = $metadata->localeMapping;
        if ($localeField) {
            $locale = $metadata->reflFields[$localeField]->getValue($document);
        }

        if (!$locale) {
            $oid = spl_object_hash($document);
            if (isset($this->documentLocales[$oid]['current'])) {
                $locale = $this->documentLocales[$oid]['current'];
            } else {
                $locale = $this->dm->getLocaleChooserStrategy()->getLocale();
            }
        }

        return $locale;
    }

    /**
     * Use the LocaleStrategyChooser to return list of fallback locales
     * @param $desiredLocale
     * @return array
     */
    private function getFallbackLocales($document, $metadata, $desiredLocale)
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
     * @param $metadata the document meta data
     * @return bool
     */
    private function isDocumentTranslatable($metadata)
    {
        return !empty($metadata->translator)
            && is_string($metadata->translator)
            && count($metadata->translatableFields) !== 0;
    }

    private static function objToStr($obj, DocumentManager $dm = null)
    {
        if (method_exists($obj, '__toString')) {
            return (string)$obj;
        }

        $string = get_class($obj).'@'.spl_object_hash($obj);

        if ($dm) {
            try {
                $metadata = $dm->getClassMetadata(get_class($obj));
                if ($metadata->getIdentifierValue($obj)) {
                    $string .= ' ('.$metadata->getIdentifierValue($obj).')';
                }
            } catch (\Exception $e) {
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
        if ($metadata->versionable === 'full') {
            $node->addMixin('mix:versionable');
        } else {
            if ($metadata->versionable === 'simple') {
                $node->addMixin('mix:simpleVersionable');
            }

            if ($metadata->referenceable) {
                $node->addMixin('mix:referenceable');
            }
        }

        // we manually set the uuid to allow creating referenced and referencing document without flush in between.
        if ($node->isNodeType('mix:referenceable') && !$node->hasProperty('jcr:uuid')) {
            // TODO do we need to check with the storage backend if the generated id really is unique?
            $node->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        }
    }
}
