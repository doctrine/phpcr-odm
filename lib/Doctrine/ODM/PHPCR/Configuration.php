<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Mapping\Driver\BuiltinDocumentsDriver;
use Doctrine\ODM\PHPCR\Repository\DefaultRepositoryFactory;
use Doctrine\ODM\PHPCR\Repository\RepositoryFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectRepository;
use PHPCR\Util\UUIDHelper;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Pascal Helfenstein <nicam@nicam.ch>
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
     */
    public function setValidateDoctrineMetadata(bool $validateDoctrineMetadata): void
    {
        $this->attributes['validateDoctrineMetadata'] = $validateDoctrineMetadata;
    }

    /**
     * Gets if all PHPCR document metadata should be validated on read.
     */
    public function getValidateDoctrineMetadata(): bool
    {
        return $this->attributes['validateDoctrineMetadata'];
    }

    /**
     * Sets if all PHPCR documents should automatically get doctrine metadata added on write.
     */
    public function setWriteDoctrineMetadata(bool $writeDoctrineMetadata): void
    {
        $this->attributes['writeDoctrineMetadata'] = $writeDoctrineMetadata;
    }

    /**
     * Gets if all PHPCR documents should automatically get doctrine metadata added on write.
     */
    public function getWriteDoctrineMetadata(): bool
    {
        return $this->attributes['writeDoctrineMetadata'];
    }

    /**
     * Adds a namespace with the specified alias.
     */
    public function addDocumentNamespace(string $alias, string $namespace): void
    {
        $this->attributes['documentNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @throws PHPCRException
     */
    public function getDocumentNamespace(string $documentNamespaceAlias): string
    {
        if (!isset($this->attributes['documentNamespaces'][$documentNamespaceAlias])) {
            throw PHPCRException::unknownDocumentNamespace($documentNamespaceAlias);
        }

        return trim($this->attributes['documentNamespaces'][$documentNamespaceAlias], '\\');
    }

    /**
     * Set the document alias map.
     *
     * @param array<string, string> $documentNamespaces
     */
    public function setDocumentNamespaces(array $documentNamespaces): void
    {
        $this->attributes['documentNamespaces'] = $documentNamespaces;
    }

    /**
     * Sets the driver implementation that is used to retrieve mapping metadata.
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl, bool $useBuiltInDocumentsDriver = true): void
    {
        if ($useBuiltInDocumentsDriver) {
            $driverImpl = new BuiltinDocumentsDriver($driverImpl);
        }
        $this->attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Gets the driver implementation that is used to retrieve mapping metadata.
     */
    public function getMetadataDriverImpl(): ?MappingDriver
    {
        return $this->attributes['metadataDriverImpl'];
    }

    public function setMetadataCacheImpl(CacheItemPoolInterface $metadataCacheImpl): void
    {
        $this->attributes['metadataCacheImpl'] = $metadataCacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     */
    public function getMetadataCacheImpl(): ?CacheItemPoolInterface
    {
        return $this->attributes['metadataCacheImpl'];
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     */
    public function getDocumentClassMapper(): DocumentClassMapperInterface
    {
        if (empty($this->attributes['documentClassMapper'])) {
            $this->setDocumentClassMapper(new DocumentClassMapper());
        }

        return $this->attributes['documentClassMapper'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     */
    public function setDocumentClassMapper(DocumentClassMapperInterface $documentClassMapper): void
    {
        $this->attributes['documentClassMapper'] = $documentClassMapper;
    }

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     */
    public function setProxyDir(string $dir): void
    {
        $this->attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     */
    public function getProxyDir(): string
    {
        if (!isset($this->attributes['proxyDir'])) {
            $this->attributes['proxyDir'] = sys_get_temp_dir();
        }

        return $this->attributes['proxyDir'];
    }

    /**
     * Sets the namespace for Doctrine proxy class files.
     */
    public function setProxyNamespace(string $namespace): void
    {
        $this->attributes['proxyNamespace'] = $namespace;
    }

    /**
     * Gets the namespace for Doctrine proxy class files.
     */
    public function getProxyNamespace(): string
    {
        return $this->attributes['proxyNamespace'];
    }

    /**
     * Sets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     */
    public function setAutoGenerateProxyClasses(bool $bool): void
    {
        $this->attributes['autoGenerateProxyClasses'] = $bool;
    }

    /**
     * Gets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     */
    public function getAutoGenerateProxyClasses(): bool
    {
        return $this->attributes['autoGenerateProxyClasses'];
    }

    /**
     * Sets a class metadata factory.
     *
     * @since 1.1
     */
    public function setClassMetadataFactoryName(string $cmfName): void
    {
        $this->attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @since 1.1
     */
    public function getClassMetadataFactoryName(): string
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
     * @throws PHPCRException If not is a ObjectRepository
     */
    public function setDefaultRepositoryClassName(string $className): void
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
     */
    public function getDefaultRepositoryClassName(): string
    {
        return $this->attributes['defaultRepositoryClassName']
            ?? DocumentRepository::class;
    }

    /**
     * Set the document repository factory.
     *
     * @since 1.1
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory): void
    {
        $this->attributes['repositoryFactory'] = $repositoryFactory;
    }

    /**
     * Get the document repository factory.
     *
     * @since 1.1
     */
    public function getRepositoryFactory(): RepositoryFactory
    {
        return $this->attributes['repositoryFactory']
            ?? new DefaultRepositoryFactory();
    }

    /**
     * Set the closure for the UUID generation.
     *
     * @since 1.1
     */
    public function setUuidGenerator(\Closure $generator): void
    {
        $this->attributes['uuidGenerator'] = $generator;
    }

    /**
     * Get the closure for the UUID generation.
     *
     * @since 1.1
     */
    public function getUuidGenerator(): \Closure
    {
        return $this->attributes['uuidGenerator']
            ?? function () {
                return UUIDHelper::generateUUID();
            };
    }
}

interface_exists(MappingDriver::class);
