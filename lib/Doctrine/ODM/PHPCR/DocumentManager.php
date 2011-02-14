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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\HTTP\Client;
use Doctrine\Common\EventManager;

/**
 * Document Manager
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class DocumentManager
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork = null;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory = null;

    /**
     * @var array
     */
    private $repositories = array();

    /**
     * @var EventManager
     */
    private $evm;

    public function __construct(Configuration $config = null, EventManager $evm = null)
    {
        $this->config = $config ?: new Configuration();
        $this->evm = $evm ?: new EventManager();
        $this->metadataFactory = new ClassMetadataFactory($this);
        $this->unitOfWork = new UnitOfWork($this);
        $this->proxyFactory = new Proxy\ProxyFactory($this, $this->config->getProxyDir(), $this->config->getProxyNamespace(), true);
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->evm;
    }

    /**
     * @return \PHPCR\SessionInterface
     */
    public function getPhpcrSession()
    {
        return $this->config->getPhpcrSession();
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param Configuration $config
     * @param EventManager $evm
     * @return DocumentManager
     */
    public static function create(Configuration $config = null, EventManager $evm = null)
    {
        return new DocumentManager($config, $evm);
    }

    /**
     * @return ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * @param  string $class
     * @return ClassMetadata
     */
    public function getClassMetadata($class)
    {
        return $this->metadataFactory->getMetadataFor($class);
    }

    /**
     * Find the Document with the given id.
     *
     * Will return null if the document wasn't found.
     *
     * @param string $documentName
     * @param string $path
     * @return object
     */
    public function find($documentName, $path)
    {
        return $this->getRepository($documentName)->find($path);
    }

    /**
     * Finds many documents by path.
     *
     * @param string $documentName
     * @param array $paths
     * @return object
     */
    public function findMany($documentName, array $paths)
    {
        return $this->getRepository($documentName)->findMany($paths);
    }

    /**
     * @param  string $documentName
     * @return Doctrine\ODM\PHPCR\DocumentRepository
     */
    public function getRepository($documentName)
    {
        $documentName  = ltrim($documentName, '\\');
        if (!isset($this->repositories[$documentName])) {
            $class = $this->getClassMetadata($documentName);
            if ($class->customRepositoryClassName) {
                $repositoryClass = $class->customRepositoryClassName;
            } else {
                $repositoryClass = 'Doctrine\ODM\PHPCR\DocumentRepository';
            }
            $this->repositories[$documentName] = new $repositoryClass($this, $class);
        }
        return $this->repositories[$documentName];
    }

    public function persist($object, $path)
    {
        $this->unitOfWork->scheduleInsert($object, $path);
    }

    public function remove($object)
    {
        $this->unitOfWork->scheduleRemove($object);
    }

    /**
     * Refresh the given document by querying the PHPCR to get the current state.
     *
     * @param object $document
     */
    public function refresh($document)
    {
        $this->getRepository(get_class($document))->refresh($document);
    }

    /**
     * Gets a reference to the entity identified by the given type and path
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $documentName The name of the entity type.
     * @param mixed $path The entity path.
     * @return object The entity reference.
     */
    public function getReference($documentName, $path)
    {
        // Check identity map first, if its already in there just return it.
        if ($document = $this->unitOfWork->tryGetByPath($path)) {
            return $document;
        }
        $class = $this->metadataFactory->getMetadataFor(ltrim($documentName, '\\'));
        $document = $this->proxyFactory->getProxy($class->name, $path);
        $this->unitOfWork->registerManaged($document, $path, null);

        return $document;
    }

    public function flush()
    {
        $this->unitOfWork->flush();
    }

    /**
     * Check in the Object, this makes the node read only and creates a new version.
     *
     * @param object $document
     */
    public function checkIn($object)
    {
        $this->unitOfWork->checkIn($object);
    }

    /**
     * Check Out in the Object, this makes the node writable again.
     *
     * @param object $document
     */
    public function checkOut($object)
    {
        $this->unitOfWork->checkOut($object);
    }

    /**
     * Restores an Object to a certain version.
     *
     * @param string $version The version to be restored e.g. 1.0 or 1.1
     * @param object $document.
     * @param bool $removeExisting Should the existing version be removed.
     */
    public function restore($version, $object, $removeExisting = true)
    {
        $this->unitOfWork->restore($version, $object, $removeExisting);
        $this->refresh($object);
    }

    /**
     * @param  object $document
     * @return bool
     */
    public function contains($document)
    {
        return $this->unitOfWork->contains($document);
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    public function clear()
    {
        // Todo: Do a real delegated clear?
        $this->unitOfWork = new UnitOfWork($this);
        return $this->config->getPhpcrSession()->clear();
    }
}
