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
    /**
     * @var DocumentManagerInterface the DocumentManager this factory is bound to
     */
    private $documentManager;

    /**
     * @var string the namespace that contains all proxy classes
     */
    private $proxyNamespace;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param DocumentManagerInterface $documentManager the DocumentManager the new factory works for
     * @param string                   $proxyDir        The directory to use for the proxy classes. It must exist.
     * @param string                   $proxyNamespace  the namespace to use for the proxy classes
     * @param bool                     $autoGenerate    whether to automatically generate proxy classes
     */
    public function __construct(DocumentManagerInterface $documentManager, $proxyDir, $proxyNamespace, $autoGenerate = false)
    {
        parent::__construct(
            new ProxyGenerator($proxyDir, $proxyNamespace),
            $documentManager->getMetadataFactory(),
            $autoGenerate
        );

        $this->documentManager = $documentManager;
        $this->proxyNamespace = $proxyNamespace;
    }

    /**
     * {@inheritdoc}
     */
    protected function skipClass(BaseClassMetadata $metadata)
    {
        if (!$metadata instanceof PhpcrClassMetadata) {
            throw new InvalidArgumentException('Did not get the expected type of metadata but '.get_class($metadata));
        }

        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    /**
     * {@inheritdoc}
     */
    protected function createProxyDefinition($className)
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
     *
     * @return \Closure
     */
    private function createInitializer(PhpcrClassMetadata $classMetadata)
    {
        $className = $classMetadata->getName();
        $documentManager = $this->documentManager;

        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            return function (Proxy $proxy) use ($className, $documentManager) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup();
                $documentManager->getRepository($className)->refreshDocumentForProxy($proxy);
            };
        }

        return function (Proxy $proxy) use ($className, $documentManager) {
            $proxy->__setInitializer(null);
            $proxy->__setCloner(null);

            if ($proxy->__isInitialized()) {
                return;
            }

            $properties = $proxy->__getLazyProperties();

            foreach ($properties as $propertyName => $property) {
                if (!isset($proxy->$propertyName)) {
                    $proxy->$propertyName = $properties[$propertyName];
                }
            }

            $proxy->__setInitialized(true);
            $documentManager->getRepository($className)->refreshDocumentForProxy($proxy);
        };
    }

    /**
     * Generates a closure capable of finalizing a cloned proxy.
     *
     * @return \Closure
     *
     * @throws UnexpectedValueException
     */
    private function createCloner(PhpcrClassMetadata $classMetadata, \ReflectionProperty $reflectionId = null)
    {
        $className = $classMetadata->getName();
        $documentManager = $this->documentManager;

        return function (Proxy $cloned) use ($className, $classMetadata, $documentManager, $reflectionId) {
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
