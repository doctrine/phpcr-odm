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
use Doctrine\ODM\PHPCR\Types\Type;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

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
    private $documentPaths = array();

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

        $type = $documentName;
//        if ($node->hasProperty('_doctrine_name')) {
//             $type = $node->getPropertyValue('_doctrine_name');
//             if (isset($documentName) && $this->dm->getConfiguration()->getValidateDoctrineMetadata()) {
//                $validate = true;
//             }
//        } else if (isset($documentName)) {
//             $type = $documentName;
//             if ($this->dm->getConfiguration()->getWriteDoctrineMetadata()) {
//                $data['doctrine_metadata'] = array('type' => $documentName);
//             }
//        } else {
//             throw new \InvalidArgumentException("Missing Doctrine metadata in the Document, cannot hydrate (yet)!");
//        }

        $class = $this->dm->getClassMetadata($type);

        $documentState = array();
        $nonMappedData = array();
        $id = $node->getPath();

        foreach ($node->getProperties() as $name => $property) {
            if (isset($class->fieldMappings[$name])) {
                $documentState[$class->fieldMappings[$name]['fieldName']] = $property->getNativeValue();
            }
//            } else if ($jsonName == 'doctrine_metadata') {
//                if (!isset($jsonValue['associations'])) {
//                    continue;
//                }
//
//                foreach ($jsonValue['associations'] AS $assocName => $assocValue) {
//                    if (isset($class->associationsMappings[$assocName])) {
//                        if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::TO_ONE) {
//                            if ($assocValue) {
//                                $assocValue = $this->dm->getReference($class->associationsMappings[$assocName]['targetDocument'], $assocValue);
//                            }
//                            $documentState[$class->associationsMappings[$assocName]['fieldName']] = $assocValue;
//                        } else if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::MANY_TO_MANY) {
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

        if ($class->path) {
            $documentState[$class->path] = $node->getPath();
        }
        if ($class->identifier) {
            $documentState[$class->identifier] = $node->getIdentifier();
        }

        // initialize inverse side collections
        foreach ($class->associationsMappings AS $assocName => $assocOptions) {
            if (!$assocOptions['isOwning'] && $assocOptions['type'] & ClassMetadata::TO_MANY) {
                $documentState[$class->associationsMappings[$assocName]['fieldName']] = new PersistentViewCollection(
                    new \Doctrine\Common\Collections\ArrayCollection(),
                    $this->dm,
                    $id,
                    $class->associationsMappings[$assocName]['mappedBy']
                );
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
            $this->documentPaths[$oid] = $id;
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
        return $this->originalData[\spl_object_hash($document)];
    }

    /**
     * Schedule insertion of this document and cascade if neccessary.
     *
     * @param object $document
     * @param string $path
     */
    public function scheduleInsert($document, $path)
    {
        $visited = array();
        $this->doScheduleInsert($document, $path, $visited);
    }

    private function doScheduleInsert($document, $path, &$visited)
    {
        $oid = \spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }
        $visited[$oid] = true;

        $class = $this->dm->getClassMetadata(get_class($document));
        $state = $this->getDocumentState($document);

        switch ($state) {
            case self::STATE_NEW:
                $this->registerManaged($document, $path, null);

                if ($this->evm->hasListeners(Event::prePersist)) {
                    $this->evm->dispatchEvent(Event::prePersist, new Events\LifecycleEventArgs($document, $this->dm));
                }
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
        foreach ($class->associationsMappings AS $assocName => $assoc) {
            if ( ($assoc['cascade'] & ClassMetadata::CASCADE_PERSIST) ) {
                $related = $class->reflFields[$assocName]->getValue($document);
                if ($class->associationsMappings[$assocName]['type'] & ClassMetadata::TO_ONE) {
                    if ($this->getDocumentState($related) == self::STATE_NEW) {
                        $this->doScheduleInsert($related, $visited);
                    }
                } else {
                    // $related can never be a persistent collection in case of a new entity.
                    foreach ($related AS $relatedDocument) {
                        if ($this->getDocumentState($relatedDocument) == self::STATE_NEW) {
                            $this->doScheduleInsert($relatedDocument, $visited);
                        }
                    }
                }
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
        $oid = \spl_object_hash($document);
        $this->scheduledRemovals[$oid] = $document;
        $this->documentState[$oid] = self::STATE_REMOVED;

        if ($this->evm->hasListeners(Event::preRemove)) {
            $this->evm->dispatchEvent(Event::preRemove, new Events\LifecycleEventArgs($document, $this->dm));
        }
    }

    public function getDocumentState($document)
    {
        $oid = \spl_object_hash($document);
        if (isset($this->documentState[$oid])) {
            return $this->documentState[$oid];
        }
        return self::STATE_NEW;
    }

    private function detectChangedDocuments()
    {
        foreach ($this->identityMap AS $id => $document) {
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

        $oid = \spl_object_hash($document);
        $actualData = array();
        // TODO: Do we need two loops?
        foreach ($class->reflFields AS $fieldName => $reflProperty) {
            $value = $reflProperty->getValue($document);
            if ($class->isCollectionValuedAssociation($fieldName) && $value !== null
                    && !($value instanceof PersistentCollection)) {

                if (!$value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                if ($class->associationsMappings[$fieldName]['isOwning']) {
                    $coll = new PersistentIdsCollection(
                        $value,
                        $class->associationsMappings[$fieldName]['targetDocument'],
                        $this->dm,
                        array()
                    );
                } else {
                    $coll = new PersistentViewCollection(
                        $value,
                        $this->dm,
                        $this->documentPaths[$oid],
                        $class->associationsMappings[$fieldName]['mappedBy']
                    );
                }

                $class->reflFields[$fieldName]->setValue($document, $coll);

                $actualData[$fieldName] = $coll;
            } else {
                $actualData[$fieldName] = $value;
            }
            // TODO: ORM transforms arrays and collections into persistent collections
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
            foreach ($actualData AS $fieldName => $fieldValue) {
                if (isset($class->fieldMappings[$fieldName]) && $this->originalData[$oid][$fieldName] !== $fieldValue) {
                    $changed = true;
                    break;
                } else if(isset($class->associationsMappings[$fieldName])) {
                    if (!$class->associationsMappings[$fieldName]['isOwning']) {
                        continue;
                    }

                    if ( ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_ONE) && $this->originalData[$oid][$fieldName] !== $fieldValue) {
                        $changed = true;
                        break;
                    } else if ( ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_MANY)) {
                        if ( !($fieldValue instanceof PersistentCollection)) {
                            // if its not a persistent collection and the original value changed. otherwise it could just be null
                            $changed = true;
                            break;
                        } else if ($fieldValue->changed()) {
                            $this->visitedCollections[] = $fieldValue;
                            $changed = true;
                            break;
                        }
                    }
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
     * Flush Operation - Write all dirty entries to the PHPCR.
     *
     * @return void
     */
    public function flush()
    {
        $this->detectChangedDocuments();

        if ($this->evm->hasListeners(Event::onFlush)) {
            $this->evm->dispatchEvent(Event::onFlush, new Events\OnFlushEventArgs($this));
        }

        $config = $this->dm->getConfiguration();

        $useDoctrineMetadata = $config->getWriteDoctrineMetadata();

        $session = $this->dm->getPhpcrSession();

        foreach ($this->scheduledRemovals AS $oid => $document) {
            $session->deleteDocument($this->documentPaths[$oid], $this->documentRevisions[$oid]);
            $this->removeFromIdentityMap($document);

            if ($this->evm->hasListeners(Event::postRemove)) {
                $this->evm->dispatchEvent(Event::postRemove, new Events\LifecycleEventArgs($document, $this->dm));
            }
        }

        foreach ($this->scheduledInserts as $oid => $document) {
            $document = $document;
            $path = $this->documentPaths[$oid];
            $class = $this->dm->getClassMetadata(get_class($document));
            $parentNode = $session->getNode(dirname($path) === '\\' ? '/' : dirname($path));
            $node = $parentNode->addNode(basename($path), $class->nodeType);
            $this->nodesMap[$oid] = $node;

            if ($useDoctrineMetadata) {
                $node->setProperty('_doctrine_alias', $class->alias, 'string');
            }

            foreach ($this->documentChangesets[$oid] as $fieldName => $fieldValue) {
                if (isset($class->fieldMappings[$fieldName])) {
                    $type = \PHPCR\PropertyType::valueFromName($class->fieldMappings[$fieldName]['type']);
                    $node->setProperty($class->fieldMappings[$fieldName]['name'], $fieldValue, $type);
                }
            }
        }

        foreach ($this->scheduledUpdates AS $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));
            $node = $this->nodesMap[$oid];

            if ($this->evm->hasListeners(Event::preUpdate)) {
                $this->evm->dispatchEvent(Event::preUpdate, new Events\LifecycleEventArgs($document, $this->dm));
                $this->computeChangeSet($class, $document); // TODO: prevent association computations in this case?
            }

            if ($useDoctrineMetadata) {
                $node->setProperty('_doctrine_alias', $class->alias);
            }

            // Convert field values to json values.
            foreach ($this->documentChangesets[$oid] AS $fieldName => $fieldValue) {
                if (isset($class->fieldMappings[$fieldName])) {
                    $node->setProperty($class->fieldMappings[$fieldName]['name'], $fieldValue);
                } else if (isset($class->associationsMappings[$fieldName]) && $useDoctrineMetadata) {
                    if ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_ONE) {
                        if (\is_object($fieldValue)) {
                            $data['doctrine_metadata']['associations'][$fieldName] = $this->getDocumentPath($fieldValue);
                        } else {
                            $data['doctrine_metadata']['associations'][$fieldName] = null;
                        }
                    } else if ($class->associationsMappings[$fieldName]['type'] & ClassMetadata::TO_MANY) {
                        if ($class->associationsMappings[$fieldName]['isOwning']) {
                            // TODO: Optimize when not initialized yet! In ManyToMany case we can keep track of ALL ids
                            $ids = array();
                            if (is_array($fieldValue) || $fieldValue instanceof \Doctrine\Common\Collections\Collection) {
                                foreach ($fieldValue AS $relatedObject) {
                                    $ids[] = $this->getDocumentPath($relatedObject);
                                }
                            }

                            $data['doctrine_metadata']['associations'][$fieldName] = $ids;
                        }
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

        $session->save();

        foreach ($this->scheduledUpdates AS $oid => $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if ($this->evm->hasListeners(Event::postUpdate)) {
                $this->evm->dispatchEvent(Event::postUpdate, new Events\LifecycleEventArgs($document, $this->dm));
            }
        }

        foreach ($this->visitedCollections AS $col) {
            $col->takeSnapshot();
        }

        $this->scheduledUpdates =
        $this->scheduledRemovals =
        $this->visitedCollections = array();
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

        if (isset($this->identityMap[$this->documentPaths[$oid]])) {
            unset($this->identityMap[$this->documentPaths[$oid]],
                  $this->documentPaths[$oid],
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
        return isset($this->documentPaths[\spl_object_hash($document)]);
    }

    public function registerManaged($document, $path, $revision)
    {
        $oid = spl_object_hash($document);
        $this->documentState[$oid] = self::STATE_MANAGED;
        $this->documentPaths[$oid] = $path;
        $this->documentRevisions[$oid] = $revision;
        $this->identityMap[$path] = $document;
    }

    /**
     * Tries to find an entity with the given path in the identity map of
     * this UnitOfWork.
     *
     * @param mixed $path The entity path to look for.
     * @param string $rootClassName The name of the root class of the mapped entity hierarchy.
     * @return mixed Returns the entity with the specified path if it exists in
     *               this UnitOfWork, FALSE otherwise.
     */
    public function tryGetByPath($path)
    {
        if (isset($this->identityMap[$path])) {
            return $this->identityMap[$path];
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
        $oid = \spl_object_hash($document);
        if (isset($this->documentRevisions[$oid])) {
            return $this->documentRevisions[$oid];
        }
        return null;
    }

    public function getDocumentPath($document)
    {
        $oid = \spl_object_hash($document);
        if (isset($this->documentPaths[$oid])) {
            return $this->documentPaths[$oid];
        } else {
            throw new PHPCRException("Document is not managed and has no path.");
        }
    }
}
