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

namespace Doctrine\ODM\PHPCR\Proxy;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Proxy\Exception\UnexpectedValueException;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use ReflectionProperty;

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
     * @var DocumentManager The DocumentManager this factory is bound to.
     */
    private $documentManager;

    /**
     * @var string The namespace that contains all proxy classes.
     */
    private $proxyNamespace;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param DocumentManager $documentManager The DocumentManager the new factory works for.
     * @param string          $proxyDir        The directory to use for the proxy classes. It must exist.
     * @param string          $proxyNamespace  The namespace to use for the proxy classes.
     * @param boolean         $autoGenerate    Whether to automatically generate proxy classes.
     */
    public function __construct(DocumentManager $documentManager, $proxyDir, $proxyNamespace, $autoGenerate = false)
    {
        parent::__construct(
            new ProxyGenerator($proxyDir, $proxyNamespace),
            $documentManager->getMetadataFactory(),
            $autoGenerate
        );

        $this->documentManager = $documentManager;
        $this->proxyNamespace  = $proxyNamespace;
    }

    /**
     * {@inheritDoc}
     */
    protected function skipClass(BaseClassMetadata $metadata)
    {
        if ( ! $metadata instanceof ClassMetadata) {
            throw new InvalidArgumentException('Did not get the expected type of metadata but ' . get_class($metadata));
        }

        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    protected function createProxyDefinition($className)
    {
        $classMetadata = $this->documentManager->getClassMetadata($className);

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($classMetadata->getName(), $this->proxyNamespace),
            array($classMetadata->identifier),
            $classMetadata->reflFields,
            $this->createInitializer($classMetadata),
            $this->createCloner($classMetadata, $classMetadata->reflFields[$classMetadata->identifier])
        );
    }

    /**
     * Generates a closure capable of initializing a proxy
     *
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $classMetadata
     *
     * @return \Closure
     */
    private function createInitializer(ClassMetadata $classMetadata)
    {
        $className       = $classMetadata->getName();
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
     * Generates a closure capable of finalizing a cloned proxy
     *
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $classMetadata
     * @param \ReflectionProperty                       $reflectionId
     *
     * @return \Closure
     *
     * @throws \Doctrine\Common\Proxy\Exception\UnexpectedValueException
     */
    private function createCloner(ClassMetadata $classMetadata, ReflectionProperty $reflectionId)
    {
        $className       = $classMetadata->getName();
        $documentManager = $this->documentManager;

        return function (Proxy $cloned) use ($className, $classMetadata, $documentManager, $reflectionId) {
            if ($cloned->__isInitialized()) {
                return;
            }

            $cloned->__setInitialized(true);
            $cloned->__setInitializer(null);

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
