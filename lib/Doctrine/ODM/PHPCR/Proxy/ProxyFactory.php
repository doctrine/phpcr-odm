<?php

namespace Doctrine\ODM\PHPCR\Proxy;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\Common\Proxy\Exception\UnexpectedValueException;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata as PhpcrClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Nils Adermann  <naderman@naderman.de>
 * @author Johannes Stark <starkj@gmx.de>
 * @author David Buchmann <david@liip.ch>
 * @author Marco Pivetta  <ocramius@gmail.com>
 */
class ProxyFactory extends AbstractProxyFactory
{
    private DocumentManagerInterface $documentManager;
    private string $proxyNamespace;

    /**
     * @param string $proxyDir       Path to store the proxy classes. The path must already exist
     * @param string $proxyNamespace The namespace to use for the proxy classes
     * @param bool   $autoGenerate   Whether to automatically generate proxy classes
     */
    public function __construct(DocumentManagerInterface $documentManager, string $proxyDir, string $proxyNamespace, bool $autoGenerate = false)
    {
        parent::__construct(
            new ProxyGenerator($proxyDir, $proxyNamespace),
            $documentManager->getMetadataFactory(),
            $autoGenerate
        );

        $this->documentManager = $documentManager;
        $this->proxyNamespace = $proxyNamespace;
    }

    protected function skipClass(BaseClassMetadata $metadata): bool
    {
        if (!$metadata instanceof PhpcrClassMetadata) {
            throw new InvalidArgumentException('Did not get the expected type of metadata but '.get_class($metadata));
        }

        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    protected function createProxyDefinition($className): ProxyDefinition
    {
        $classMetadata = $this->documentManager->getClassMetadata($className);

        if ($classMetadata->identifier) {
            $identifierFields = [$classMetadata->identifier];
            $reflectionId = $classMetadata->reflFields[$classMetadata->identifier];
        } else {
            $identifierFields = [];
            $reflectionId = null;
        }

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($classMetadata->getName(), $this->proxyNamespace),
            $identifierFields,
            $classMetadata->reflFields,
            $this->createInitializer($classMetadata),
            $this->createCloner($classMetadata, $reflectionId)
        );
    }

    /**
     * Generates a closure capable of initializing a proxy.
     */
    private function createInitializer(PhpcrClassMetadata $classMetadata): \Closure
    {
        $className = $classMetadata->getName();
        $documentManager = $this->documentManager;

        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            return static function (Proxy $proxy) use ($className, $documentManager) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $property;
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup(); /** @phpstan-ignore-line we check for existence with the reflection class */
                $repository = $documentManager->getRepository($className);
                if ($repository instanceof DocumentRepository || method_exists($repository, 'refreshDocumentForProxy')) {
                    $repository->refreshDocumentForProxy($proxy);
                }
            };
        }

        return static function (Proxy $proxy) use ($className, $documentManager) {
            $proxy->__setInitializer(null);
            $proxy->__setCloner(null);

            if ($proxy->__isInitialized()) {
                return;
            }

            $properties = $proxy->__getLazyProperties();

            foreach ($properties as $propertyName => $property) {
                if (!isset($proxy->$propertyName)) {
                    $proxy->$propertyName = $property;
                }
            }

            $proxy->__setInitialized(true);
            $repository = $documentManager->getRepository($className);
            if ($repository instanceof DocumentRepository || method_exists($repository, 'refreshDocumentForProxy')) {
                $repository->refreshDocumentForProxy($proxy);
            }
        };
    }

    /**
     * Generates a closure capable of finalizing a cloned proxy.
     *
     * @throws UnexpectedValueException
     */
    private function createCloner(PhpcrClassMetadata $classMetadata, \ReflectionProperty $reflectionId = null): \Closure
    {
        $className = $classMetadata->getName();
        $documentManager = $this->documentManager;

        return static function (Proxy $cloned) use ($className, $classMetadata, $documentManager, $reflectionId) {
            if ($cloned->__isInitialized()) {
                return;
            }

            $cloned->__setInitialized(true);
            $cloned->__setInitializer(null);

            if (!$reflectionId) {
                return;
            }

            $original = $documentManager->find($className, $reflectionId->getValue($cloned));

            if (null === $original) {
                throw new UnexpectedValueException(sprintf(
                    'Proxy with ID "%s" could not be loaded',
                    $reflectionId->getValue($cloned)
                ));
            }

            foreach ($classMetadata->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                if ($classMetadata->hasField($propertyName) || $classMetadata->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($cloned, $reflectionProperty->getValue($original));
                }
            }
        };
    }
}

interface_exists(BaseClassMetadata::class);
