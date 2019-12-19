<?php

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Event;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    protected $cacheSalt = '\$PHPCRODMCLASSMETADATA';

    /**
     * @var DocumentManagerInterface
     */
    private $dm;

    /**
     *  The used metadata driver.
     *
     * @var MappingDriver
     */
    private $driver;

    /**
     * @var EventManager
     */
    private $evm;

    /**
     * Creates a new factory instance that uses the given DocumentManager instance.
     *
     * @param DocumentManagerInterface $dm The DocumentManager instance
     */
    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;

        $conf = $this->dm->getConfiguration();
        $this->setCacheDriver($conf->getMetadataCacheImpl());
        $this->driver = $conf->getMetadataDriverImpl();
        $this->evm = $this->dm->getEventManager();
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function getMetadataFor($className)
    {
        $metadata = parent::getMetadataFor($className);
        if ($metadata) {
            return $metadata;
        }

        throw MappingException::classNotMapped($className);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function loadMetadata($className)
    {
        if (class_exists($className)) {
            return parent::loadMetadata($className);
        }

        throw MappingException::classNotFound($className);
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
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
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
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
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function addInheritedDocumentOptions(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        $subClass->setCustomRepositoryClassName($parentClass->customRepositoryClassName);
        $subClass->setTranslator($parentClass->translator);
        $subClass->setVersioned($parentClass->versionable);
        $subClass->setReferenceable($parentClass->referenceable);
        $subClass->setNodeType($parentClass->getNodeType());
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName) {
            $subClass->mapField($parentClass->mappings[$fieldName], $parentClass);
        }
        foreach ($parentClass->referenceMappings as $fieldName) {
            $mapping = $parentClass->mappings[$fieldName];
            if (ClassMetadata::MANY_TO_ONE == $mapping['type']) {
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
     * @param ClassMetadata $class
     * @param $parent
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata($class, $parent)
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

        // verify inheritance
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->initializeReflection($reflService);
    }

    /**
     * {@inheritdoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->wakeupReflection($reflService);
    }

    /**
     * {@inheritdoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return isset($class->isMappedSuperclass) && false === $class->isMappedSuperclass;
    }
}
