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

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.

 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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
     * @var \Doctrine\ODM\PHPCR\Mapping\Driver\Driver
     */
    private $driver;

    /**
     * Creates a new factory instance that uses the given DocumentManager instance.
     *
     * @param $dm The DocumentManager instance
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
    function loadMetadata($className)
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
    protected function doLoadMetadata($class, $parent, $rootEntityFound)
    {
        if ($parent) {
            $this->addInheritedFields($class, $parent);
        }

        if ($this->getDriver()) {
            $this->getDriver()->loadMetadataForClass($class->getName(), $class);
        }
    }

    /**
    * Adds inherited fields to the subclass mapping.
    *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $subClass
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $parentClass
     * @return void
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $fieldName, $mapping);
            $subClass->mapField($mapping);
        }
        foreach ($parentClass->associationsMappings as $fieldName => $mapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $fieldName, $mapping);
            $subClass->storeAssociationMapping($mapping);
        }
        foreach ($parentClass->childMappings as $fieldName => $mapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $fieldName, $mapping);
            $subClass->mapChild($mapping);
        }
        foreach ($parentClass->childrenMappings as $fieldName => $mapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $fieldName, $mapping);
            $subClass->mapChildren($mapping);
        }
        foreach ($parentClass->referrersMappings as $fieldName => $mapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $fieldName, $mapping);
            $subClass->mapReferrers($mapping);
        }
        if ($parentClass->node) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->node);
            $subClass->mapNode($mapping);
        }
        if ($parentClass->nodename) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->nodename);
            $subClass->mapNodename($mapping);
        }
        if ($parentClass->parentMapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->parentMapping);
            $subClass->mapParentDocument($mapping);
        }
        if ($parentClass->localeMapping) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->localeMapping);
            $subClass->mapLocale($mapping);
        }
        if ($parentClass->versionNameField) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->versionNameField);
            $subClass->mapVersionName($mapping);
        }
        if ($parentClass->versionCreatedField) {
            $mapping = $this->addInheritanceKeys($parentClass, $parentClass->versionCreatedField);
            $subClass->mapVersionCreated($mapping);
        }
    }

    private function addInheritanceKeys(ClassMetadata $parentClass, $fieldName, array $mapping = array())
    {
        $mapping['fieldName'] = $fieldName;

        if (!isset($mapping['inherited']) && !$parentClass->isMappedSuperclass) {
            $mapping['inherited'] = $parentClass->name;
        }
        if (!isset($mapping['declared'])) {
            $mapping['declared'] = $parentClass->name;
        }

        return $mapping;
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

}
