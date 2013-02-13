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

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.

 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
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
     * @var DocumentManager
     */
    private $dm;

    /**
     *  The used metadata driver.
     *
     * @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    private $driver;

    /**
     * Creates a new factory instance that uses the given DocumentManager instance.
     *
     * @param DocumentManager $dm The DocumentManager instance
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;

        $conf = $this->dm->getConfiguration();
        $this->setCacheDriver($conf->getMetadataCacheImpl());
        $this->driver = $conf->getMetadataDriverImpl();
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
        return $this->dm->getConfiguration()->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        if ($parent) {
            $this->addInheritedFields($class, $parent);
        }

        if ($this->getDriver()) {
            $this->getDriver()->loadMetadataForClass($class->getName(), $class);
        }

        $this->validateRuntimeMetadata($class, $parent);
        $class->setParentClasses($this->getParentClasses($class->name));
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
            if ($mapping['type'] == ClassMetadata::MANY_TO_ONE) {
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
     * @throws MappingException
     */
    protected function validateRuntimeMetadata($class, $parent)
    {
        if (!$class->reflClass) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateReferences();
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
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }
}
