<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\PHPCR\Mapping\Driver\BuiltinDocumentsDriver;
use Doctrine\ODM\PHPCR\Repository\DefaultRepositoryFactory;
use Doctrine\ODM\PHPCR\Repository\RepositoryFactory;
use PHPCR\Util\UUIDHelper;

/**
 * Configuration class.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class Configuration
{
    /**
     * Array of attributes for this configuration instance.
     *
     * @var array
     */
    private $attributes = [
        'writeDoctrineMetadata' => true,
        'validateDoctrineMetadata' => true,
        'metadataDriverImpl' => null,
        'metadataCacheImpl' => null,
        'documentClassMapper' => null,
        'proxyNamespace' => 'MyPHPCRProxyNS',
        'autoGenerateProxyClasses' => true,
    ];

    /**
     * Sets if all PHPCR document metadata should be validated on read.
     *
     * @param bool $validateDoctrineMetadata
     */
    public function setValidateDoctrineMetadata($validateDoctrineMetadata)
    {
        $this->attributes['validateDoctrineMetadata'] = $validateDoctrineMetadata;
    }

    /**
     * Gets if all PHPCR document metadata should be validated on read.
     *
     * @return bool
     */
    public function getValidateDoctrineMetadata()
    {
        return $this->attributes['validateDoctrineMetadata'];
    }

    /**
     * Sets if all PHPCR documents should automatically get doctrine metadata added on write.
     *
     * @param bool $writeDoctrineMetadata
     */
    public function setWriteDoctrineMetadata($writeDoctrineMetadata)
    {
        $this->attributes['writeDoctrineMetadata'] = $writeDoctrineMetadata;
    }

    /**
     * Gets if all PHPCR documents should automatically get doctrine metadata added on write.
     *
     * @return bool
     */
    public function getWriteDoctrineMetadata()
    {
        return $this->attributes['writeDoctrineMetadata'];
    }

    /**
     * Adds a namespace under a certain alias.
     *
     * @param string $alias
     * @param string $namespace
     */
    public function addDocumentNamespace($alias, $namespace)
    {
        $this->attributes['documentNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $documentNamespaceAlias
     *
     * @throws PHPCRException
     *
     * @return string the namespace URI
     */
    public function getDocumentNamespace($documentNamespaceAlias)
    {
        if (!isset($this->attributes['documentNamespaces'][$documentNamespaceAlias])) {
            throw PHPCRException::unknownDocumentNamespace($documentNamespaceAlias);
        }

        return trim($this->attributes['documentNamespaces'][$documentNamespaceAlias], '\\');
    }

    /**
     * Set the document alias map.
     *
     * @param array $documentNamespaces
     */
    public function setDocumentNamespaces(array $documentNamespaces)
    {
        $this->attributes['documentNamespaces'] = $documentNamespaces;
    }

    /**
     * Sets the driver implementation that is used to retrieve mapping metadata.
     *
     * @param MappingDriver $driverImpl
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl, $useBuildInDocumentsDriver = true)
    {
        if ($useBuildInDocumentsDriver) {
            $driverImpl = new BuiltinDocumentsDriver($driverImpl);
        }
        $this->attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Gets the driver implementation that is used to retrieve mapping metadata.
     *
     * @return MappingDriver
     */
    public function getMetadataDriverImpl()
    {
        return $this->attributes['metadataDriverImpl'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param Cache $metadataCacheImpl
     */
    public function setMetadataCacheImpl(Cache $metadataCacheImpl)
    {
        $this->attributes['metadataCacheImpl'] = $metadataCacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return Cache|null
     */
    public function getMetadataCacheImpl()
    {
        return $this->attributes['metadataCacheImpl'];
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     *
     * @return DocumentClassMapperInterface
     */
    public function getDocumentClassMapper()
    {
        if (empty($this->attributes['documentClassMapper'])) {
            $this->setDocumentClassMapper(new DocumentClassMapper());
        }

        return $this->attributes['documentClassMapper'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param DocumentClassMapperInterface $documentClassMapper
     */
    public function setDocumentClassMapper(DocumentClassMapperInterface $documentClassMapper)
    {
        $this->attributes['documentClassMapper'] = $documentClassMapper;
    }

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     */
    public function setProxyDir($dir)
    {
        $this->attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string
     */
    public function getProxyDir()
    {
        if (!isset($this->attributes['proxyDir'])) {
            $this->attributes['proxyDir'] = sys_get_temp_dir();
        }

        return $this->attributes['proxyDir'];
    }

    /**
     * Sets the namespace for Doctrine proxy class files.
     *
     * @param string $namespace
     */
    public function setProxyNamespace($namespace)
    {
        $this->attributes['proxyNamespace'] = $namespace;
    }

    /**
     * Gets the namespace for Doctrine proxy class files.
     *
     * @return string
     */
    public function getProxyNamespace()
    {
        return $this->attributes['proxyNamespace'];
    }

    /**
     * Sets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @param bool $bool
     */
    public function setAutoGenerateProxyClasses($bool)
    {
        $this->attributes['autoGenerateProxyClasses'] = $bool;
    }

    /**
     * Gets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @return bool
     */
    public function getAutoGenerateProxyClasses()
    {
        return $this->attributes['autoGenerateProxyClasses'];
    }

    /**
     * Sets a class metadata factory.
     *
     * @since 1.1
     *
     * @param string $cmfName
     */
    public function setClassMetadataFactoryName($cmfName)
    {
        $this->attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @since 1.1
     *
     * @return string
     */
    public function getClassMetadataFactoryName()
    {
        if (!isset($this->attributes['classMetadataFactoryName'])) {
            $this->attributes['classMetadataFactoryName'] = Mapping\ClassMetadataFactory::class;
        }

        return $this->attributes['classMetadataFactoryName'];
    }

    /**
     * Sets default repository class.
     *
     * @since 1.1
     *
     * @param string $className
     *
     * @throws PHPCRException If not is a ObjectRepository
     *
     * @return void
     */
    public function setDefaultRepositoryClassName($className)
    {
        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->implementsInterface(ObjectRepository::class)) {
            throw PHPCRException::invalidDocumentRepository($className);
        }

        $this->attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @since 1.1
     *
     * @return string
     */
    public function getDefaultRepositoryClassName()
    {
        return $this->attributes['defaultRepositoryClassName']
            ?? DocumentRepository::class;
    }

    /**
     * Set the document repository factory.
     *
     * @since 1.1
     *
     * @param RepositoryFactory $repositoryFactory
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory)
    {
        $this->attributes['repositoryFactory'] = $repositoryFactory;
    }

    /**
     * Get the document repository factory.
     *
     * @since 1.1
     *
     * @return RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->attributes['repositoryFactory']
            ?? new DefaultRepositoryFactory();
    }

    /**
     * Set the closure for the UUID generation.
     *
     * @since 1.1
     *
     * @param callable $generator
     */
    public function setUuidGenerator(\Closure $generator)
    {
        $this->attributes['uuidGenerator'] = $generator;
    }

    /**
     * Get the closure for the UUID generation.
     *
     * @since 1.1
     *
     * @return callable a UUID generator
     */
    public function getUuidGenerator()
    {
        return $this->attributes['uuidGenerator']
            ?? function () {
                return UUIDHelper::generateUUID();
            };
    }
}
