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
use PHPCR\PropertyType;

/**
 * Unit of work class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
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
     * @var EventManager
     */
    private $evm;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->evm = $dm->getEventManager();
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
        // TODO create a doctrine: namespace and register node types with doctrine:name

        if ($node->hasProperty('phpcr:alias')) {
            $metadata = $this->dm->getMetadataFactory()->getMetadataForAlias($node->getPropertyValue('phpcr:alias'));
            $type = $metadata->name;
            if (isset($documentName) && $this->dm->getConfiguration()->getValidateDoctrineMetadata()) {
                $validate = true;
            }
        } elseif (isset($documentName)) {
            $type = $documentName;
            if ($this->dm->getConfiguration()->getWriteDoctrineMetadata()) {
                $node->setProperty('phpcr:alias', $documentName, PropertyType::STRING);
            }
        } else {
            throw new \InvalidArgumentException("Missing Doctrine metadata in the Document, cannot hydrate (yet)!");
        }

        $class = $this->dm->getClassMetadata($type);

        $documentState = array();
        $nonMappedData = array();
        $id = $node->getPath();

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if ($node->hasProperty($mapping['name'])) {
                $property = $node->getProperty($mapping['name']);
                if ($mapping['multivalue']) {
                    // TODO might need to be a PersistentCollection
                    // TODO the array cast should be unnecessary once jackalope is fixed to handle properly multivalues
                    $documentState[$fieldName] = new ArrayCollection((array) $property->getValue());
                } else {
                    $documentState[$fieldName] = $property->getValue();
                }
            }
//            } elseif ($jsonName == 'doctrine_metadata') {
//                if (!isset($jsonValue['associations'])) {
//                    continue;
//                }
//
//                foreach ($jsonValue['associations'] as $assocName => $assocValue) {
//                    if (isset($class->associationsMappings[$assocName])) {
//                        if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::TO_ONE) {
//                            if ($assocValue) {
//                                $assocValue = $this->dm->getReference($class->associationsMappings[$assocName]['targetDocument'], $assocValue);
//                            }
//                            $documentState[$class->associationsMappings[$assocName]['fieldName']] = $assocValue;
//                        } elseif ($class->associationsMappings[$assocName]['type'] & ClassMetadata::MANY_TO_MANY) {
//                            if ($class->associationsMappings[$assocName]['isOwning']) {
//                                $documentState[$class->associationsMappings[$assocName]['fieldName']] = new PersistentIdsCollection(
//                                    new \Doctrine\Common\Collections\ArrayCollection(),
//                                    $class->associationsMappings[$assocName]['targetDocument'],
//                                    $this->dm,
//                                    $assocValue
//                                );
//                            }
//                        }
//                    }
//                }
//            } else {
//                $nonMappedData[$jsonName] = $jsonValue;
//            }
        }

        if ($class->node) {
            $documentState[$class->node] = $node;
        }
        if ($class->versionField) {
            $documentState[$class->versionField] = $node->getProperty('jcr:baseVersion')->getValue();
        }
        if ($class->identifier) {
            $documentState[$class->identifier] = $node->getPath();
        }

        // initialize inverse side collections
        foreach ($class->associationsMappings as $assocName => $assocOptions) {
            if (!$assocOptions['isOwning'] && $assocOptions['type'] & ClassMetadata::TO_MANY) {
                // TODO figure this one out which collection should be used
                $documentState[$class->associationsMappings[$assocName]['fieldName']] = null;
//                $documentState[$class->associationsMappings[$assocName]['fieldName']] = new PersistentViewCollection(
//                    new \Doctrine\Common\Collections\ArrayCollection(),
//                    $this->dm,
//                    $id,
//                    $class->associationsMappings[$assocName]['mappedBy']
//                );
            }
        }

        foreach ($class->childMappings as $childName => $mapping) {
            if ($node->hasNode($mapping['name'])) {
                $documentState[$class->childMappings[$childName]['fieldName']] = $this->createDocument(null, $node->getNode($mapping['name']));
            } else {
                $documentState[$class->childMappings[$childName]['fieldName']] = null;
            }
        }

        if (isset($this->identityMap[$id])) {
            $document = $this->identityMap[$id];
            $overrideLocalValues = false;

            if ( ($document instanceof Proxy && !$document->__isInitialized__) || isset($hints['refresh'])) {
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

        if (isset($validate) && !($document instanceof $documentName)) {
            throw new \InvalidArgumentException("Doctrine metadata mismatch! Requested type '$documentName' type does not match type '$type' stored in the metdata");
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
        if (isset($metadata->lifecycleCallbacks[Event::postLoad])) {
            $metadata->invokeLifecycleCallbacks(Event::postLoad, $document);
        }
        if ($this->evm->hasListeners(Event::postLoad)) {
            $this->evm->dispatchEvent(Event::postLoad, new Events\LifecycleEventArgs($document, $this->dm));
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
                throw new \InvalidArgumentException("Detached entity passed to persist().");
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
            if ( ($assoc['cascade'] & ClassMetadata::CASCADE_PERSIST) ) {
                $related = $class->reflFields[$assocName]->getValue($document);
                if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::TO_ONE) {
                    if ($this->getDocumentState($related) == self::STATE_NEW) {
                        $this->doScheduleInsert($related, $visited);
                    }
                } else {
                    // $related can never be a persistent collection in case of a new entity.
                    foreach ($related as $relatedDocument) {
                        if ($this->getDocumentState($relatedDocument) == self::STATE_NEW) {
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
            $this->evm->dispatchEvent(Event::preRemove, new Events\LifecycleEventArgs($document, $this->dm));
        }
    }

    /**
     * recurse over all known child documents to remove them form this unit of work
     * as their parent gets removed from phpcr. If you do not, flush will try to create
     + orphaned nodes if these documents are modified which leads to a PHPCR exception
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
                  $this->scheduledUpdates[$oid]
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
        foreach ($this->identityMap as $id => $document) {
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
        if ($document instanceof Proxy\Proxy && !$document->__isInitialized__) {
            return;
        }

        $oid = spl_object_hash($document);
        $actualData = array();
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            $value = $reflProperty->getValue($document);
            if ($class->isCollectionValuedAssociation($fieldName) && $value !== null
                    && !($value instanceof PersistentCollection)
            ) {
                if (!$value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // TODO coll shold be a new PersistentCollection
                $coll = $value;

                $class->reflFields[$fieldName]->setValue($document, $coll);

                $actualData[$fieldName] = $coll;
            } else {
                $actualData[$fieldName] = $value;
            }
        }
        // unset the revision field if necessary, it is not to be managed by the user in write scenarios.
        if ($class->isVersioned) {
            unset($actualData[$class->versionField]);
        }

        if (!isset($this->originalData[$oid])) {
            // Entity is New and should be inserted
            $this->originalData[$oid] = $actualData;
            $this->documentChangesets[$oid] = $actualData;
            $this->scheduledInserts[$oid] = $document;
        } else {
            // Entity is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data

            $changed = false;
            foreach ($actualData as $fieldName => $fieldValue) {
                if (!isset($class->fieldMappings[$fieldName]) && !isset($class->childMappings[$fieldName])) {
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
                }
            }

            if ($changed) {
                $this->documentChangesets[$oid] = $actualData;
                $this->scheduledUpdates[$oid] = $document;
            }
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

        $this->registerManaged($document, $id, null);

        if (isset($class->lifecycleCallbacks[Event::prePersist])) {
            $class->invokeLifecycleCallbacks(Event::prePersist, $document);
        }
        if ($this->evm->hasListeners(Event::prePersist)) {
            $this->evm->dispatchEvent(Event::prePersist, new Events\LifecycleEventArgs($document, $this->dm));
        }
    }

    /**
     * Flush Operation - Write all dirty entries to the PHPCR.
     *
     * @param boolean $persist_to_backend Wether the phpcr session should be saved to permanent storage
     *
     * @return void
     *
     * @throws PHPCRException if it detects that a existing child should be replaced
     */
    public function flush($persist_to_backend = true)
    {
        $this->detectChangedDocuments();

        if ($this->evm->hasListeners(Event::onFlush)) {
            $this->evm->dispatchEvent(Event::onFlush, new Events\OnFlushEventArgs($this));
        }

        $config = $this->dm->getConfiguration();

        $useDoctrineMetadata = $config->getWriteDoctrineMetadata();

        $session = $this->dm->getPhpcrSession();

        foreach ($this->scheduledRemovals as $oid => $document) {
            $this->nodesMap[$oid]->remove();
            $this->removeFromIdentityMap($document);
            $this->purgeChildren($document);
        }

        foreach ($this->scheduledInserts as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            // FIXME: this leads to prePersist being called twice, because its also invoked in persistNew
            // which is the right place? mongo does this differently.
            if (isset($class->lifecycleCallbacks[Event::prePersist])) {
                $class->invokeLifecycleCallbacks(Event::prePersist, $document);
                $this->computeChangeSet($class, $document); // TODO: prevent association computations in this case?
            }
            if ($this->evm->hasListeners(Event::prePersist)) {
                $this->evm->dispatchEvent(Event::prePersist, new Events\LifecycleEventArgs($document, $this->dm));
                $this->computeChangeSet($class, $document); // TODO: prevent association computations in this case?
            }
            $id = $this->documentIds[$oid];
            $parentNode = $session->getNode(dirname($id) === '\\' ? '/' : dirname($id));
            $node = $parentNode->addNode(basename($id), $class->nodeType);
            try {
              $node->addMixin('phpcr:managed');
            } catch (\PHPCR\NodeType\NoSuchNodeTypeException $e) {
              throw new PHPCRException("You need to register the node type phpcr:managed first. See https://github.com/doctrine/phpcr-odm/wiki/Custom-node-type-phpcr:managed");
            }

            if ($class->isVersioned) {
                $node->addMixin("mix:versionable");
            }

            $this->nodesMap[$oid] = $node;
            if ($class->identifier) {
                $class->reflFields[$class->identifier]->setValue($document, $id);
            }
            if ($class->node) {
                $class->reflFields[$class->node]->setValue($document, $node);
            }
            if ($useDoctrineMetadata) {
                $node->setProperty('phpcr:alias', $class->alias, PropertyType::STRING);
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
                }
            }
        }

        foreach ($this->scheduledUpdates as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->nodesMap[$oid];

            if (isset($class->lifecycleCallbacks[Event::preUpdate])) {
                $class->invokeLifecycleCallbacks(Event::preUpdate, $document);
                $this->computeChangeSet($class, $document); // TODO: prevent association computations in this case?
            }
            if ($this->evm->hasListeners(Event::preUpdate)) {
                $this->evm->dispatchEvent(Event::preUpdate, new Events\LifecycleEventArgs($document, $this->dm));
                $this->computeChangeSet($class, $document); // TODO: prevent association computations in this case?
            }

            if ($useDoctrineMetadata) {
                $node->setProperty('phpcr:alias', $class->alias, PropertyType::STRING);
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
                } elseif (isset($class->associationsMappings[$fieldName]) && $useDoctrineMetadata) {
                    if ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_ONE) {
                        if (\is_object($fieldValue)) {
                            $data['doctrine_metadata']['associations'][$fieldName] = $this->getDocumentId($fieldValue);
                        } else {
                            $data['doctrine_metadata']['associations'][$fieldName] = null;
                        }
                    } elseif ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_MANY) {
                        if ($class->associationsMappings[$fieldName]['isOwning']) {
                            // TODO: Optimize when not initialized yet! In ManyToMany case we can keep track of ALL ids
                            $ids = array();
                            if (is_array($fieldValue) || $fieldValue instanceof \Doctrine\Common\Collections\Collection) {
                                foreach ($fieldValue as $relatedObject) {
                                    $ids[] = $this->getDocumentId($relatedObject);
                                }
                            }

                            $data['doctrine_metadata']['associations'][$fieldName] = $ids;
                        }
                    }
                // child is set to null ... remove the node ...
                } elseif (isset($class->childMappings[$fieldName])) {
                    if ($fieldValue === null) {
                      if ($node->hasNode($class->childMappings[$fieldName]['name'])) {
                        $child = $node->getNode($class->childMappings[$fieldName]['name']);
                        $childDocument = $this->createDocument(null, $child);
                        $this->purgeChildren($childDocument);
                        $child->remove();
                      }
                    } elseif (isset($this->originalData[$oid][$fieldName])) {
                        // this is currently not implemented
                        // the old child needs to be removed and the new child might be moved
                        throw new PHPCRException("You can not move or copy children by assignment as it would be ambigous. Please use the PHPCR\Session::move() resp PHPCR\Session::copy operations for this.");
                    }
                }
            }

            // respect the non mapped data, otherwise they will be deleted.
            if (isset($this->nonMappedData[$oid]) && $this->nonMappedData[$oid]) {
                $data = array_merge($data, $this->nonMappedData[$oid]);
            }

            $rev = $this->getDocumentRevision($document);
            if ($rev) {
                $data['_rev'] = $rev;
            }
        }

        if ($persist_to_backend) {
            $session->save();
        }

        $this->handlePostFlushEvents();

        foreach ($this->visitedCollections as $col) {
            $col->takeSnapshot();
        }

        $this->scheduledUpdates =
        $this->scheduledRemovals =
        $this->scheduledInserts =
        $this->visitedCollections = array();
    }

    /**
     * Invoke / dispatch events as necessary after a flush operation
     */
    protected function handlePostFlushEvents()
    {
        foreach ($this->scheduledRemovals as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if (isset($class->lifecycleCallbacks[Event::postRemove])) {
                $class->invokeLifecycleCallbacks(Event::postRemove, $document);
            }
            if ($this->evm->hasListeners(Event::postRemove)) {
                $this->evm->dispatchEvent(Event::postRemove, new Events\LifecycleEventArgs($document, $this->dm));
            }
        }

        foreach ($this->scheduledInserts as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if (isset($class->lifecycleCallbacks[Event::postPersist])) {
                $class->invokeLifecycleCallbacks(Event::postPersist, $document);
            }
            if ($this->evm->hasListeners(Event::postPersist)) {
                $this->evm->dispatchEvent(Event::postPersist, new Events\LifecycleEventArgs($document, $this->dm));
            }
        }

        foreach ($this->scheduledUpdates as $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if (isset($class->lifecycleCallbacks[Event::postUpdate])) {
                $class->invokeLifecycleCallbacks(Event::postUpdate, $document);
            }
            if ($this->evm->hasListeners(Event::postUpdate)) {
                $this->evm->dispatchEvent(Event::postUpdate, new Events\LifecycleEventArgs($document, $this->dm));
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
        $this->flush();
        $session = $this->dm->getPhpcrSession();
        $node = $session->getNode($path);
        $node->addMixin("mix:versionable");
        $vm = $session->getWorkspace()->getVersionManager();
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
        $this->flush();
        $session = $this->dm->getPhpcrSession();
        $node = $session->getNode($path);
        $node->addMixin("mix:versionable");
        $vm = $session->getWorkspace()->getVersionManager();
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
        $this->flush();
        $session = $this->dm->getPhpcrSession();
        $vm = $session->getWorkspace()->getVersionManager();
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
        $session = $this->dm->getPhpcrSession();
        $vm = $session->getWorkspace()->getVersionManager();
        return $vm->getBaseVersion($path)->getPredecessors();
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
     * @param  object $document
     * @return bool
     */
    public function contains($document)
    {
        return isset($this->documentIds[spl_object_hash($document)]);
    }

    public function registerManaged($document, $id, $revision)
    {
        $oid = spl_object_hash($document);
        $this->documentState[$oid] = self::STATE_MANAGED;
        $this->documentIds[$oid] = $id;
        $this->documentRevisions[$oid] = $revision;
        $this->identityMap[$id] = $document;
    }

    /**
     * Tries to find an entity with the given id in the identity map of
     * this UnitOfWork.
     *
     * @param mixed $id The entity id to look for.
     * @param string $rootClassName The name of the root class of the mapped entity hierarchy.
     * @return mixed Returns the entity with the specified id if it exists in
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

    public function getDocumentId($document)
    {
        $oid = spl_object_hash($document);
        if (empty($this->documentIds[$oid])) {
            throw new PHPCRException("Document is not managed and has no id.");
        }
        return $this->documentIds[$oid];
    }
}
