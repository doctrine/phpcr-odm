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
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;

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
     * @param string $id
     * @return object
     */
    public function find($documentName, $id)
    {
        if (is_null($documentName)) {
            //TODO: we should figure out the document automatically from the phpcr:alias
            throw new \InvalidArgumentException('documentName for find may not be null');
        }
        return $this->getRepository($documentName)->find($id);
    }

    /**
     * Finds many documents by id.
     *
     * @param string $documentName
     * @param array $ids
     * @return object
     */
    public function findMany($documentName, array $ids)
    {
        return $this->getRepository($documentName)->findMany($ids);
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

    public function persist($object)
    {
        $this->unitOfWork->scheduleInsert($object);
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
     * Gets a reference to the entity identified by the given type and id
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $documentName The name of the entity type.
     * @param string $id The entity id.
     * @return object The entity reference.
     */
    public function getReference($documentName, $id)
    {
        // Check identity map first, if its already in there just return it.
        if ($document = $this->unitOfWork->tryGetById($id)) {
            return $document;
        }
        $class = $this->metadataFactory->getMetadataFor(ltrim($documentName, '\\'));
        $document = $this->proxyFactory->getProxy($class->name, $id);
        $this->unitOfWork->registerManaged($document, $id, null);

        return $document;
    }

    /**
     * Flush all current changes, that is save them within the phpcr session
     * and commit that session to permanent storage.
     */
    public function flush()
    {
        $this->unitOfWork->flush();
    }

    /**
     * Temporary workaround: Flush all current changes to the phpcr session,
     * but do not commit the session yet.
     *
     * Until everything is supported in doctrine, you will need to access the
     * phpcr session directly for some operations. With the non-persisting
     * flush, you can make phpcr reflect the current state without comitting
     * the transaction.
     * Do not forget to call session->save() or dm->flush() to persist the
     * changes when you are done.
     *
     * @param boolean $persist_to_backend Wether the phpcr session should be saved to permanent storage or not yet. defaults to persist
     *
     * @depricated: will go away as soon as phpcr-odm maps all necessary
     * concepts of phpcr. if you use this now, be prepared to refactor your
     * code when this method goes away.
     */
    public function flushNoSave()
    {
        $this->unitOfWork->flush(false);
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
     * Gets the DocumentRepository and gets the Predeccors of the Object.
     *
     * @param  string $documentName
     * @param  object $document
     * @return array of \PHPCR\Version\Version objects
     */

    public function getPredecessors($documentName, $object)
    {
         return $this->getRepository($documentName)->getPredecessors($object);
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
