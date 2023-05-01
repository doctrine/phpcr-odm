<?php

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    protected $cacheSalt = '__PHPCRODMCLASSMETADATA';

    private DocumentManagerInterface $dm;
    private ?MappingDriver $driver;
    private EventManager $evm;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;

        $conf = $this->dm->getConfiguration();
        if ($cache = $conf->getMetadataCacheImpl()) {
            $this->setCache($cache);
        }
        $this->driver = $conf->getMetadataDriverImpl();
        $this->evm = $this->dm->getEventManager();
    }

    /**
     * @throws MappingException
     */
    public function getMetadataFor($className): ClassMetadata
    {
        $metadata = parent::getMetadataFor($className);
        if ($metadata instanceof ClassMetadata) {
            return $metadata;
        }

        throw MappingException::classNotMapped($className);
    }

    /**
     * @throws MappingException
     */
    public function loadMetadata($className): array
    {
        if (class_exists($className)) {
            return parent::loadMetadata($className);
        }

        throw MappingException::classNotFound($className);
    }

    protected function newClassMetadataInstance($className): ClassMetadata
    {
        return new ClassMetadata($className);
    }

    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName): string
    {
        return $this->dm->getConfiguration()->getDocumentNamespace($namespaceAlias).'\\'.$simpleClassName;
    }

    /**
     * Actually loads PHPCR-ODM metadata from the underlying metadata.
     *
     * @param ClassMetadata      $class
     * @param ClassMetadata|null $parent
     * @param bool               $rootEntityFound
     * @param array              $nonSuperclassParents all parent class names
     *                                                 that are not marked as mapped superclasses
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents): void
    {
        if ($parent) {
            $this->addInheritedDocumentOptions($class, $parent);
            $this->addInheritedFields($class, $parent);
        }

        if ($this->getDriver()) {
            $this->getDriver()->loadMetadataForClass($class->getName(), $class);
        }

        // once we loaded the metadata of this class, we might have to merge in the mixins of the parent.
        if ($parent && $class->getInheritMixins()) {
            $class->setMixins(array_merge($parent->getMixins(), $class->getMixins()));
        }

        if ($this->evm->hasListeners(Event::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->dm);
            $this->evm->dispatchEvent(Event::loadClassMetadata, $eventArgs);
        }

        $this->validateRuntimeMetadata($class, $parent);
        $class->setParentClasses($this->getParentClasses($class->name));
    }

    /**
     * Set the document level options of the parent class to the subclass.
     *
     * This has to be done before loading the data of the subclass.
     */
    private function addInheritedDocumentOptions(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        $subClass->setCustomRepositoryClassName($parentClass->customRepositoryClassName);
        $subClass->setTranslator($parentClass->translator);
        $subClass->setVersioned($parentClass->versionable);
        $subClass->setReferenceable($parentClass->referenceable);
        $subClass->setNodeType($parentClass->getNodeType());
    }

    /**
     * Adds inherited fields to the subclass mapping.
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->fieldMappings as $fieldName) {
            $subClass->mapField($parentClass->mappings[$fieldName], $parentClass);
        }
        foreach ($parentClass->referenceMappings as $fieldName) {
            $mapping = $parentClass->mappings[$fieldName];
            if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                $subClass->mapManyToOne($mapping, $parentClass);
            } else {
                $subClass->mapManyToMany($mapping, $parentClass);
            }
        }
        foreach ($parentClass->childMappings as $fieldName) {
            $mapping = $parentClass->mappings[$fieldName];
            $subClass->mapChild($mapping, $parentClass);
        }
        foreach ($parentClass->childrenMappings as $fieldName) {
            $subClass->mapChildren($parentClass->mappings[$fieldName], $parentClass);
        }
        foreach ($parentClass->referrersMappings as $fieldName) {
            $subClass->mapReferrers($parentClass->mappings[$fieldName], $parentClass);
        }
        if ($parentClass->identifier) {
            $subClass->mapId($parentClass->mappings[$parentClass->identifier], $parentClass);
        }
        if ($parentClass->node) {
            $subClass->mapNode($parentClass->mappings[$parentClass->node], $parentClass);
        }
        if ($parentClass->nodename) {
            $subClass->mapNodename($parentClass->mappings[$parentClass->nodename], $parentClass);
        }
        if ($parentClass->parentMapping) {
            $subClass->mapParentDocument($parentClass->mappings[$parentClass->parentMapping], $parentClass);
        }
        if ($parentClass->localeMapping) {
            $subClass->mapLocale($parentClass->mappings[$parentClass->localeMapping], $parentClass);
        }
        if ($parentClass->depthMapping) {
            $subClass->mapDepth($parentClass->mappings[$parentClass->depthMapping], $parentClass);
        }
        if ($parentClass->versionNameField) {
            $subClass->mapVersionName($parentClass->mappings[$parentClass->versionNameField], $parentClass);
        }
        if ($parentClass->versionCreatedField) {
            $subClass->mapVersionCreated($parentClass->mappings[$parentClass->versionCreatedField], $parentClass);
        }
        if ($parentClass->lifecycleCallbacks) {
            $subClass->mapLifecycleCallbacks($parentClass->lifecycleCallbacks);
        }

        $subClass->setReferenceable($parentClass->referenceable);

        // Versionable defaults to false - only set on child class if it is non-false
        if ($parentClass->versionable) {
            $subClass->setVersioned($parentClass->versionable);
        }

        $subClass->setTranslator($parentClass->translator);
        $subClass->setNodeType($parentClass->nodeType);
        $subClass->setCustomRepositoryClassName($parentClass->customRepositoryClassName);
    }

    /**
     * Validate runtime metadata is correctly defined.
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata(ClassMetadata $class, $parent): void
    {
        if (!$class->reflClass) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateReferenceable();
        $class->validateReferences();
        $class->validateChildClasses();
        $class->validateLifecycleCallbacks($this->getReflectionService());
        $class->validateTranslatables();

        // TODO: verify inheritance
    }

    protected function getDriver(): MappingDriver
    {
        return $this->driver;
    }

    protected function initialize(): void
    {
        $this->initialized = true;
    }

    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
        \assert($class instanceof ClassMetadata);
        $class->initializeReflection($reflService);
    }

    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
        \assert($class instanceof ClassMetadata);
        $class->wakeupReflection($reflService);
    }

    protected function isEntity(ClassMetadataInterface $class): bool
    {
        \assert($class instanceof ClassMetadata);

        return false === $class->isMappedSuperclass;
    }
}

interface_exists(ClassMetadataInterface::class);
interface_exists(ReflectionService::class);
