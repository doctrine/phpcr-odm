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
use Doctrine\ODM\PHPCR\Proxy\ReferenceProxyFactory;

use PHPCR\PropertyType;

/**
 * Unit of work class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
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
    private $nodesMap = array();

    /**
     * @var array
     */
    private $documentIds = array();

    /**
     * @var array
     */
    private $documentRevisions = array();

    /**
     * @var array
     */
    private $documentState = array();

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
    private $scheduledRemovals = array();

    /**
     * @var array
     */
    private $visitedCollections = array();

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
     * @var DocumentNameMapperInterface
     */
    private $documentNameMapper;

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
    public function __construct(DocumentManager $dm, DocumentNameMapperInterface $documentNameMapper = null)
    {
        $this->dm = $dm;
        $this->session = $this->dm->getPhpcrSession();
        $this->evm = $dm->getEventManager();
        $this->documentNameMapper = $documentNameMapper;
        $this->validateDocumentName = $this->dm->getConfiguration()->getValidateDoctrineMetadata();
        $this->writeMetadata = $this->dm->getConfiguration()->getWriteDoctrineMetadata();
    }

    /**
     * Get an already fetched node for the proxyDocument from the nodesMap and create the associated document
     *
     * @param string $documentName
     * @param Proxy $document
     * @return void
     */
    public function refreshDocumentForProxy($documentName, $document)
    {
        $node = $this->nodesMap[spl_object_hash($document)];
        $hints = array('refresh' => true);
        $this->createDocument($documentName, $node, $hints);
    }

    /**
     * Create a document given class, data and the doc-id and revision
     *
     * @param string $documentName
     * @param \PHPCR\NodeInterface $node
     * @param array $hints
     * @return object
     */
    public function createDocument($documentName, $node, array &$hints = array())
    {
        // second param is false to get uuid rather than dereference reference properties to node instances
        $properties = $node->getPropertiesValues(null, false);

        if ($this->documentNameMapper) {
            $type = $this->documentNameMapper->getDocumentName($this->dm, $documentName, $node, $this->writeMetadata);
            $class = $this->dm->getClassMetadata($type);
        } else {
            if (isset($documentName)) {
                $class = $this->dm->getClassMetadata($documentName);
            } elseif (isset($properties['phpcr:class'])) {
                $class = $this->dm->getClassMetadata($properties['phpcr:class']);
            } elseif (isset($properties['phpcr:alias'])) {
                $class = $this->dm->getMetadataFactory()->getMetadataForAlias($properties['phpcr:alias']);
            }

            if ($this->writeMetadata && empty($properties['phpcr:class']) && isset($class)) {
                $node->setProperty('phpcr:class', $class->name, PropertyType::STRING);
            }
        }

        if (empty($class)) {
            throw new \InvalidArgumentException("Could not determine Doctrine metadata for node");
        }

        $documentState = array();
        $nonMappedData = array();
        $id = $node->getPath();

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (isset($properties[$mapping['name']])) {
                if ($mapping['multivalue']) {
                    $documentState[$fieldName] = new ArrayCollection((array)$properties[$mapping['name']]);
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
        if ($class->versionField) {
            $documentState[$class->versionField] = $properties['jcr:baseVersion'];
        }
        if ($class->identifier) {
            $documentState[$class->identifier] = $node->getPath();
        }

        // collect uuids of all referenced nodes and get them all within one single call
        // they will get cached so you have more performance when they are accessed later
        $refNodeUUIDs = array();
        foreach ($class->associationsMappings as $assocOptions) {
            if (!$node->hasProperty($assocOptions['fieldName'])) {
                continue;
            }

            if ($assocOptions['type'] & ClassMetadata::MANY_TO_ONE) {
                $refNodeUUIDs[] = $node->getProperty($assocOptions['fieldName'])->getString();
            } elseif ($assocOptions['type'] & ClassMetadata::MANY_TO_MANY) {
                foreach ($node->getProperty($assocOptions['fieldName'])->getString() as $uuid) {
                    $refNodeUUIDs[] = $uuid;
                }
            }
        }

        if (count($refNodeUUIDs) > 0) {
            // ensure that the given nodes are in the in memory cache
            $this->session->getNodesByIdentifier($refNodeUUIDs);
        }

        // initialize inverse side collections
        foreach ($class->associationsMappings as $assocName => $assocOptions) {
            if ($assocOptions['type'] & ClassMetadata::MANY_TO_ONE) {
                // TODO figure this one out which collection should be used
                if (! $node->hasProperty($assocOptions['fieldName'])) {
                    continue;
                }

                // get the already cached referenced node
                $referencedNode = $node->getPropertyValue($assocOptions['fieldName']);
                $referencedId = $referencedNode->getPath();

                // check if referenced document already exists
                if (isset($this->identityMap[$referencedId])) {
                    $documentState[$class->associationsMappings[$assocName]['fieldName']] = $this->identityMap[$referencedId];
                } else {
                    $config = $this->dm->getConfiguration();
                    $this->referenceProxyFactory = new ReferenceProxyFactory($this->dm, $config->getProxyDir(), $config->getProxyNamespace(), true);

                    $referencedClass = $this->dm->getMetadataFactory()->getMetadataFor(ltrim($assocOptions['targetDocument'], '\\'));
                    $proxyDocument = $this->referenceProxyFactory->getProxy($referencedClass->name, $referencedId);

                    // register the referenced document under its own id
                    $this->registerManaged($proxyDocument, $referencedId, null);

                    $documentState[$class->associationsMappings[$assocName]['fieldName']] = $proxyDocument;

                    // save node for the case that the referenced document will be created
                    $proxyOid = spl_object_hash($proxyDocument);
                    $this->nodesMap[$proxyOid] = $referencedNode;
                }
            } elseif ($assocOptions['type'] & ClassMetadata::MANY_TO_MANY) {
                if (! $node->hasProperty($assocOptions['fieldName'])) {
                    continue;
                }

                // get the already cached referenced nodes
                $proxyNodes = $node->getPropertyValue($assocOptions['fieldName']);
                if (!is_array($proxyNodes)) {
                    throw new PHPCRException("Expected referenced nodes passed as array.");
                }

                $config = $this->dm->getConfiguration();
                $this->referenceProxyFactory = new Proxy\ReferenceProxyFactory($this->dm, $config->getProxyDir(), $config->getProxyNamespace(), true);

                foreach ($proxyNodes as $referencedNode) {
                    $referencedId = $referencedNode->getPath();
                    // check if referenced document already exists
                    if (isset($this->identityMap[$referencedId])) {
                        $documentState[$class->associationsMappings[$assocName]['fieldName']][] = $this->identityMap[$referencedId];
                    } else {
                        $referencedClass = $this->dm->getMetadataFactory()->getMetadataFor(ltrim($assocOptions['targetDocument'], '\\'));
                        $proxyDocument = $this->referenceProxyFactory->getProxy($referencedClass->name, $referencedId);

                        // register the referenced document under its own id
                        $this->registerManaged($proxyDocument, $referencedId, null);

                        $documentState[$class->associationsMappings[$assocName]['fieldName']][] = $proxyDocument;
                        // save node for the case that the referenced document will be created
                        $proxyOid = spl_object_hash($proxyDocument);
                        $this->nodesMap[$proxyOid] = $referencedNode;
                    }
                }
            }
        }

        foreach ($class->childMappings as $childName => $mapping) {
            $documentState[$class->childMappings[$childName]['fieldName']] = $node->hasNode($mapping['name'])
                ? $this->createDocument(null, $node->getNode($mapping['name']))
                : null;
        }

        if (isset($this->identityMap[$id])) {
            $document = $this->identityMap[$id];
            $overrideLocalValues = false;

            if (isset($hints['refresh'])) {
                $overrideLocalValues = true;
                $oid = spl_object_hash($document);
            }
        } else {
            $document = $class->newInstance();
            $this->identityMap[$id] = $document;

            $oid = spl_object_hash($document);
            $this->documentState[$oid] = self::STATE_MANAGED;
            $this->documentIds[$oid] = $id;
            $overrideLocalValues = true;
        }

        foreach ($class->childrenMappings as $mapping) {
            $documentState[$mapping['fieldName']] = new ChildrenCollection($this->dm, $document, $mapping['filter']);
        }

        foreach ($class->referrersMappings as $mapping) {
            $documentState[$mapping['fieldName']] = new ReferrersCollection($this->dm, $document, $mapping['referenceType'], $mapping['filterName']);
        }

        if (isset($documentName) && $this->validateDocumentName && !($document instanceof $documentName)) {
            $msg = "Doctrine metadata mismatch! Requested type '$documentName' type does not match type '{$class->name}' stored in the metadata";
            throw new \InvalidArgumentException($msg);
        }

        if ($overrideLocalValues) {
            $this->nodesMap[$oid] = $node;

            $this->nonMappedData[$oid] = $nonMappedData;
            foreach ($class->reflFields as $prop => $reflFields) {
                $value = isset($documentState[$prop]) ? $documentState[$prop] : null;
                $reflFields->setValue($document, $value);
                $this->originalData[$oid][$prop] = $value;
            }
        }

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
     * @param  object $document
     * @return array
     */
    public function getOriginalData($document)
    {
        return $this->originalData[spl_object_hash($document)];
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
        if (isset($visited[$oid])) {
            return;
        }
        $visited[$oid] = true;

        $class = $this->dm->getClassMetadata(get_class($document));
        $state = $this->getDocumentState($document);

        switch ($state) {
            case self::STATE_NEW:
                $this->persistNew($class, $document, $overrideIdGenerator);
                break;
            case self::STATE_MANAGED:
                // TODO: Change Tracking Deferred Explicit
                break;
            case self::STATE_REMOVED:
                // document becomes managed again
                unset($this->scheduledRemovals[$oid]);
                $this->documentState[$oid] = self::STATE_MANAGED;
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException("Detached document passed to persist().");
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
                    if (is_array($related)) {
                        throw new PHPCRException("Referenced document is not stored correctly in a reference-one property. Don't use array notation.");
                    }

                    if ($this->getDocumentState($related) == self::STATE_NEW) {
                        $this->doScheduleInsert($related, $visited);
                    }
                } else {
                    // $related can never be a persistent collection in case of a new document.
                    if (!is_array($related)) {
                        throw new PHPCRException("Referenced document is not stored correctly in a reference-many property. Use array notation.");
                    }
                    foreach ($related as $relatedDocument) {
                        if (isset($relatedDocument) && $this->getDocumentState($relatedDocument) == self::STATE_NEW) {
                            $this->doScheduleInsert($relatedDocument, $visited);
                        }
                    }
                }
            }
        }

        foreach ($class->childMappings as $childName => $mapping) {
            $child = $class->reflFields[$childName]->getValue($document);
            if ($child !== null && $this->getDocumentState($child) == self::STATE_NEW) {
                $childClass = $this->dm->getClassMetadata(get_class($child));
                $id = $class->reflFields[$class->identifier]->getValue($document);
                $childClass->reflFields[$childClass->identifier]->setValue($child , $id . '/'. $mapping['name']);
                $this->doScheduleInsert($child, $visited, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            }
        }

        foreach ($class->referrersMappings as $referrerName => $mapping) {
            $referrer = $class->reflFields[$referrerName]->getValue($document);
            if ($referrer !== null && $this->getDocumentState($referrer) == self::STATE_NEW) {
                $this->doScheduleInsert($referrer, $visited);
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

    public function scheduleRemove($document)
    {
        $oid = spl_object_hash($document);
        $this->scheduledRemovals[$oid] = $document;
        $this->documentState[$oid] = self::STATE_REMOVED;

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
     */
    private function purgeChildren($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->childMappings as $childName => $mapping) {
            $child = $class->reflFields[$childName]->getValue($document);
            if ($child !== null) {
                $this->purgeChildren($child);

                $oid = spl_object_hash($child);
                unset(
                    $this->scheduledRemovals[$oid],
                    $this->scheduledInserts[$oid],
                    $this->scheduledUpdates[$oid],
                    $this->scheduledAssociationUpdates[$oid]
                );

                $this->removeFromIdentityMap($child);
            }
        }
    }

    public function getDocumentState($document)
    {
        $oid = spl_object_hash($document);
        if (isset($this->documentState[$oid])) {
            return $this->documentState[$oid];
        }
        return self::STATE_NEW;
    }

    private function detectChangedDocuments()
    {
        foreach ($this->identityMap as $document) {
            $state = $this->getDocumentState($document);
            if ($state == self::STATE_MANAGED) {
                $class = $this->dm->getClassMetadata(get_class($document));
                $this->computeChangeSet($class, $document);
            }
        }
    }

    /**
     * @param ClassMetadata $class
     * @param object $document
     * @return void
     */
    public function computeChangeSet(ClassMetadata $class, $document)
    {
        $oid = spl_object_hash($document);
        $actualData = array();
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            $value = $reflProperty->getValue($document);
            if ($class->isCollectionValuedAssociation($fieldName)
                && $value !== null
                && !($value instanceof PersistentCollection)
            ) {
                if (!$value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // TODO coll should be a new PersistentCollection
                $coll = $value;

                $class->reflFields[$fieldName]->setValue($document, $coll);

                $actualData[$fieldName] = $coll;
            } else {
                $actualData[$fieldName] = $value;
            }
        }
        // unset the revision field if necessary, it is not to be managed by the user in write scenarios.
        if ($class->versionable) {
            unset($actualData[$class->versionField]);
        }

        if (!isset($this->originalData[$oid])) {
            // Document is New and should be inserted
            $this->originalData[$oid] = $actualData;
            $this->documentChangesets[$oid] = $actualData;
            $this->scheduledInserts[$oid] = $document;
        } else {
            // Document is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data

            $changed = false;
            foreach ($actualData as $fieldName => $fieldValue) {
                if (!isset($class->fieldMappings[$fieldName])
                    && !isset($class->childMappings[$fieldName])
                    && !isset($class->associationsMappings[$fieldName])
                    && !isset($class->referrersMappings[$fieldName])
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
                    if ($class->nodename == $fieldName) {
                        throw new PHPCRException('The Nodename property is immutable');
                    }
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $this->documentChangesets[$oid] = $actualData;
                $this->scheduledUpdates[$oid] = $document;
            }
        }

        $id = $class->reflFields[$class->identifier]->getValue($document);
        foreach ($class->childMappings as $name => $childMapping) {
            if ($this->originalData[$oid][$name]) {
                $this->computeChildChanges($childMapping, $this->originalData[$oid][$name], $id);
            }
        }

        foreach ($class->associationsMappings as $assocName => $assoc) {
            if ($actualData[$assocName]) {
                if (is_array($actualData[$assocName])) {
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

        if ($state == self::STATE_NEW) {
            $targetClass->reflFields[$targetClass->identifier]->setValue($child , $parentId . '/'. $mapping['name']);
            $this->persistNew($targetClass, $child, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            $this->computeChangeSet($targetClass, $child);
        } elseif ($state == self::STATE_REMOVED) {
            throw new \InvalidArgumentException("Removed child document detected during flush");
        } elseif ($state == self::STATE_DETACHED) {
            throw new \InvalidArgumentException("A detached document was found through a child relationship during cascading a persist operation.");
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

        if ($state == self::STATE_NEW) {
            $this->persistNew($targetClass, $reference, ClassMetadata::GENERATOR_TYPE_ASSIGNED);
            $this->computeChangeSet($targetClass, $reference);
        } elseif ($state == self::STATE_DETACHED) {
            throw new \InvalidArgumentException("A detached document was found through a "
                . "reference during cascading a persist operation.");
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
                // TODO: can this actually happen?
                throw new \InvalidArgumentException("A detached document was found through a referrer during cascading a persist operation.");
        }
    }

    /**
     * Gets the changeset for an document.
     *
     * @return array
     */
    public function getDocumentChangeSet($document)
    {
        $oid = spl_object_hash($document);
        if (isset($this->documentChangesets[$oid])) {
            return $this->documentChangesets[$oid];
        }
        return array();
    }

    /**
     * Persist new document, marking it managed and generating the id.
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
        $id = $this->getIdGenerator($overrideIdGenerator ? $overrideIdGenerator : $class->idGenerator)->generate($document, $class, $this->dm);

        $oid = $this->registerManaged($document, $id, null);

        $parentNode = $this->session->getNode(dirname($id) === '\\' ? '/' : dirname($id));
        $node = $parentNode->addNode(basename($id), $class->nodeType);
        $this->nodesMap[$oid] = $node;

        if ($class->identifier) {
            $class->reflFields[$class->identifier]->setValue($document, $id);
        }
        if ($class->node) {
            $class->reflFields[$class->node]->setValue($document, $node);
        }
        if ($class->nodename) {
            $class->reflFields[$class->nodename]->setValue($document, $node->getName());
        }

        if (isset($class->lifecycleCallbacks[Event::prePersist])) {
            $class->invokeLifecycleCallbacks(Event::prePersist, $document);
        }
        if ($this->evm->hasListeners(Event::prePersist)) {
            $this->evm->dispatchEvent(Event::prePersist, new LifecycleEventArgs($document, $this->dm));
        }
    }

    /**
     * Commits the UnitOfWork
     *
     * @return void
     */
    public function commit()
    {
        $this->detectChangedDocuments();

        if ($this->evm->hasListeners(Event::onFlush)) {
            $this->evm->dispatchEvent(Event::onFlush, new OnFlushEventArgs($this));
        }

        try {
            $utx = $this->session->getWorkspace()->getTransactionManager();
            if ($utx->inTransaction()) {
                $utx = null;
            } else {
                $utx->begin();
            }
        } catch (\PHPCR\UnsupportedRepositoryOperationException $e) {
            $utx = null;
        }

        try {
            $this->executeInserts($this->scheduledInserts);

            $this->executeUpdates($this->scheduledUpdates);

            $this->executeUpdates($this->scheduledAssociationUpdates, false);

            $this->executeRemovals($this->scheduledRemovals);

            $this->session->save();

            if ($utx) {
                $utx->commit();
            }
        } catch (\Exception $e) {
            $this->dm->close();

            if ($utx) {
                $utx->rollback();
            }

            throw $e;
        }

        foreach ($this->visitedCollections as $col) {
            $col->takeSnapshot();
        }

        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledRemovals =
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
        foreach ($documents as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->nodesMap[$oid];

            if ($this->writeMetadata) {
                $node->setProperty('phpcr:class', $class->name, PropertyType::STRING);
            }

            try {
                $node->addMixin('phpcr:managed');
            } catch (\PHPCR\NodeType\NoSuchNodeTypeException $e) {
                throw new PHPCRException("You need to register the node type phpcr:managed first. See https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr:managed");
            }

            if ($class->versionable) {
                $node->addMixin('mix:versionable');
            } elseif ($class->referenceable) {
                // referenceable is a supertype of versionable, only set if not versionable
                $node->addMixin('mix:referenceable');
            }

            // we manually set the uuid to allow creating referenced and referencing document without flush in between.
            // this check has to be done after any mixin types are set.
            if ($node->isNodeType('mix:referenceable')) {
                // TODO do we need to check with the storage backend if the generated id really is unique?
                $node->setProperty("jcr:uuid", \PHPCR\Util\UUIDHelper::generateUUID());
            }

            foreach ($this->documentChangesets[$oid] as $fieldName => $fieldValue) {
                if (isset($class->fieldMappings[$fieldName])) {
                    $type = \PHPCR\PropertyType::valueFromName($class->fieldMappings[$fieldName]['type']);
                    if ($class->fieldMappings[$fieldName]['multivalue']) {
                        $value = $fieldValue === null ? null : $fieldValue->toArray();
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $value, $type);
                    } else {
                        $node->setProperty($class->fieldMappings[$fieldName]['name'], $fieldValue, $type);
                    }
                }  elseif (isset($class->associationsMappings[$fieldName])) {
                    $this->scheduledAssociationUpdates[$oid] = $document;
                }
            }

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
            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->nodesMap[$oid];

            if ($this->writeMetadata) {
                $node->setProperty('phpcr:class', $class->name, PropertyType::STRING);
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
                if (isset($class->fieldMappings[$fieldName])) {
                    $type = \PHPCR\PropertyType::valueFromName($class->fieldMappings[$fieldName]['type']);
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
                        ? \PHPCR\PropertyType::WEAKREFERENCE : \PHPCR\PropertyType::REFERENCE;

                    if ($class->associationsMappings[$fieldName]['type'] === $class::MANY_TO_MANY) {
                        if (isset($fieldValue)) {
                            $refNodesIds = array();
                            foreach ($fieldValue as $fv ) {
                                if ($fv === null) {
                                    continue;
                                }
                                $refOid = spl_object_hash($fv);
                                $refNodesIds[] = $this->nodesMap[$refOid]->getIdentifier();
                            }
                            $node->setProperty($class->associationsMappings[$fieldName]['fieldName'], $refNodesIds, $type);
                            unset($refNodesIds);
                        }

                    } elseif ($class->associationsMappings[$fieldName]['type'] === $class::MANY_TO_ONE) {
                        if (isset($fieldValue)) {
                            $refOid = spl_object_hash($fieldValue);
                            $refNodeId = $this->nodesMap[$refOid]->getIdentifier();
                            $node->setProperty($class->associationsMappings[$fieldName]['fieldName'], $refNodeId, $type);
                        }
                    }

                // child is set to null ... remove the node ...
                } elseif (isset($class->childMappings[$fieldName])) {
                    // child is set to null ... remove the node ...
                    if ($fieldValue === null) {
                        if ($node->hasNode($class->childMappings[$fieldName]['name'])) {
                            $child = $node->getNode($class->childMappings[$fieldName]['name']);
                            $childDocument = $this->createDocument(null, $child);
                            $this->purgeChildren($childDocument);
                            $child->remove();
                        }
                    } elseif (is_null($this->originalData[$oid][$fieldName])) {
                        // TODO: store this new child
                    } elseif (isset($this->originalData[$oid][$fieldName])) {
                        // TODO: is this the correct test? if you put a different document as child and already had one, it means you moved stuff?
                        if ($fieldValue === $this->originalData[$oid][$fieldName]) {
                            // TODO: save
                        } else {
                            // TODO this is currently not implemented the old child needs to be removed and the new child might be moved
                            throw new PHPCRException("You can not move or copy children by assignment as it would be ambiguous. Please use the PHPCR\Session::move() or PHPCR\Session::copy() operations for this.");
                        }
                    }
                }
            }

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
     * Executes all document removales
     *
     * @param array $documents array of all to be removed documents
     */
    private function executeRemovals($documents)
    {
        foreach ($documents as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            $this->nodesMap[$oid]->remove();
            $this->removeFromIdentityMap($document);
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
     * Checkin operation - Save all current changes and then check in the Node by id.
     *
     * @return void
     */
    public function checkIn($document)
    {
        $path = $this->getDocumentId($document);
        $node = $this->session->getNode($path);
        $node->addMixin("mix:versionable");
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkIn($path); // Checkin Node aka make a new Version
    }

    /**
     * Check out operation - Save all current changes and then check out the Node by path.
     *
     * @return void
     */
    public function checkOut($document)
    {
        $path = $this->getDocumentId($document);
        $node = $this->session->getNode($path);
        $node->addMixin("mix:versionable");
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->checkOut($path);
    }

    /**
     * Check restore - Save all current changes and then restore the Node by path.
     *
     * @return void
     */
    public function restore($version, $document, $removeExisting)
    {
        $path = $this->getDocumentId($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vm->restore($removeExisting, $version, $path);
    }

    /**
     * Gets all the predecessor objects of an object
     *
     * TODO: this uses jackalope specific hacks and relies on a bug in jackalope with getPredecessors
     *
     * @param object $document
     * @return array of \PHPCR\Version\VersionInterface
     */
    public function getPredecessors($document)
    {
        $path = $this->getDocumentId($document);
        $vm = $this->session->getWorkspace()->getVersionManager();
        $vh = $vm->getVersionHistory($path);
        return (array)$vh->getAllVersions();
    }

    /**
     * INTERNAL:
     * Removes an document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @ignore
     * @param object $document
     * @return boolean
     */
    public function removeFromIdentityMap($document)
    {
        $oid = spl_object_hash($document);

        if (isset($this->identityMap[$this->documentIds[$oid]])) {
            unset($this->identityMap[$this->documentIds[$oid]],
                  $this->documentIds[$oid],
                  $this->documentRevisions[$oid],
                  $this->documentState[$oid]);

            return true;
        }

        return false;
    }

    /**
     * @param object $document
     * @return bool
     */
    public function contains($document)
    {
        return isset($this->documentIds[spl_object_hash($document)]);
    }

    /**
     * @param object $document
     * @param string $id The document id to look for.
     * @param string $revision The revision of the document.
     * @return the generated object id
     */
    public function registerManaged($document, $id, $revision)
    {
        $oid = spl_object_hash($document);
        $this->documentState[$oid] = self::STATE_MANAGED;
        $this->documentIds[$oid] = $id;
        $this->documentRevisions[$oid] = $revision;
        $this->identityMap[$id] = $document;
        return $oid;
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
    public function tryGetById($id)
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
        $oid = spl_object_hash($document);
        $node = $this->nodesMap[$oid];
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
     * @param string $type optional type of the reference the referrer should have
     * ("weak" or "hard")
     * @param string $name optional name to match on referrers reference property
     * name
     * @return a collection of referrer documents
     */
    public function getReferrers($document, $type = null, $name = null)
    {
        $oid = spl_object_hash($document);
        $node = $this->nodesMap[$oid];

        $referrerDocuments = array();
        $referrerPropertiesW = array();
        $referrerPropertiesH = array();

        if ($type === null) {
            $referrerPropertiesW = $node->getWeakReferences($name);
            $referrerPropertiesH = $node->getReferences($name);
        } elseif ($type === "weak") {
            $referrerPropertiesW = $node->getWeakReferences($name);
        } elseif ($type === "hard") {
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
     * Get the PHPCR revision of the document that was current upon retrieval.
     *
     * @throws PHPCRException
     * @param  object $document
     * @return string
     */
    public function getDocumentRevision($document)
    {
        $oid = spl_object_hash($document);
        if (empty($this->documentRevisions[$oid])) {
            return null;
        }
        return $this->documentRevisions[$oid];
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
        $oid = spl_object_hash($document);
        if (empty($this->documentIds[$oid])) {
            throw new PHPCRException("Document is not managed and has no id.");
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
            $obj->__doctrineLoad__();
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
        $this->nodesMap =
        $this->documentIds =
        $this->documentRevisions =
        $this->documentState =
        $this->nonMappedData =
        $this->originalData =
        $this->documentChangesets =
        $this->scheduledUpdates =
        $this->scheduledAssociationUpdates =
        $this->scheduledInserts =
        $this->scheduledRemovals =
        $this->visitedCollections = array();

        if ($this->evm->hasListeners(Event::onClear)) {
            $this->evm->dispatchEvent(Event::onClear, new OnClearEventArgs($this->dm));
        }

        return $this->session->refresh(false);
    }
}
