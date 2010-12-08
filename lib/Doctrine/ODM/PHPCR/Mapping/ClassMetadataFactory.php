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

use Doctrine\ODM\PHPCR\DocumentManager,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\PHPCRException,
    Doctrine\Common\Cache\Cache;

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
class ClassMetadataFactory
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var array
     */
    private $loadedMetadata = array();

    /**
     * @var array
     */
    private $loadedAliases = array();

    /**
     *  The used metadata driver.
     *
     * @var Doctrine\ODM\PHPCR\Mapping\Driver\Driver
     */
    private $driver;

    /**
     * The used cache driver.
     *
     * @var Cache
     */
    private $cacheDriver;

    /**
     * Creates a new factory instance that uses the given DocumentManager instance.
     *
     * @param $dm  The DocumentManager instance
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->driver = $this->dm->getConfiguration()->getMetadataDriverImpl();
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @param Doctrine\Common\Cache\Cache $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return Doctrine\Common\Cache\Cache
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * Gets the array of loaded ClassMetadata instances.
     *
     * @return array $loadedMetadata The loaded metadata.
     */
    public function getLoadedMetadata()
    {
        return $this->loadedMetadata;
    }

    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     *
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata()
    {
        $metadata = array();
        foreach ($this->driver->getAllClassNames() as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
    }

    public function getMetadataForAlias($alias)
    {
        if (isset($this->loadedAliases[$alias])) {
            return $this->loadedAliases[$alias];
        }

        if ($this->cacheDriver && ($cached = $this->cacheDriver->fetch("$alias\$PHPCRODMALIAS")) !== false) {
            return $this->loadedAliases[$alias] = $cached;
        }

        foreach ($this->loadedMetadata as $className => $metadata) {
            if ($metadata->alias === $alias) {
                $this->loadedAliases[$alias] = $metadata;
                if ($this->cacheDriver) {
                    $this->cacheDriver->save(
                        "$alias\$PHPCRODMALIAS", $this->loadedAliases[$alias], null
                    );
                }
                return $metadata;
            }
        }

        foreach ($this->driver->getAllClassNames() as $className) {
            $metadata = $this->getMetadataFor($className);
            if ($metadata->alias === $alias) {
                $this->loadedAliases[$alias] = $metadata;
                if ($this->cacheDriver) {
                    $this->cacheDriver->save(
                        "$alias\$PHPCRODMALIAS", $this->loadedAliases[$alias], null
                    );
                }
                return $metadata;
            }
        }

        throw new MappingException('Alias '.$alias.' could not be resolved to a document class name');
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     * @return Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if (!isset($this->loadedMetadata[$className])) {
            $realClassName = $className;

            // Check for namespace alias
            if (strpos($className, ':') !== false) {
                list($namespaceAlias, $simpleClassName) = explode(':', $className);
                $realClassName = $this->dm->getConfiguration()->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;

                if (isset($this->loadedMetadata[$realClassName])) {
                    // We do not have the alias name in the map, include it
                    $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];

                    return $this->loadedMetadata[$realClassName];
                }
            }

            if ($this->cacheDriver) {
                if (($cached = $this->cacheDriver->fetch("$realClassName\$PHPCRODMCLASSMETADATA")) !== false) {
                    $this->loadedMetadata[$realClassName] = $cached;
                } else {
                    foreach ($this->loadMetadata($realClassName) as $loadedClassName) {
                        $this->cacheDriver->save(
                            "$loadedClassName\$PHPCRODMCLASSMETADATA", $this->loadedMetadata[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->loadMetadata($realClassName);
            }

            if ($className != $realClassName) {
                // We do not have the alias name in the map, include it
                $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];
            }
        }

        if (!isset($this->loadedMetadata[$className])) {
            throw MappingException::classNotMapped();
        }

        return $this->loadedMetadata[$className];
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $className The name of the class for which the metadata should get loaded.
     */
    private function loadMetadata($className)
    {
        if (!class_exists($className)) {
            throw MappingException::classNotFound($className);
        }

        $this->loadedMetadata[$className] = new ClassMetadata($className);
        $this->driver->loadMetadataForClass($className, $this->loadedMetadata[$className]);
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     *
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        return isset($this->loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     *
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->loadedMetadata[$className] = $class;
    }

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
}
