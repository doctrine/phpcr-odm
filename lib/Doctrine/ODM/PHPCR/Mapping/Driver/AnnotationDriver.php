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

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\Reader,
    Doctrine\ODM\PHPCR\Event,
    Doctrine\ODM\PHPCR\Mapping\Annotations as ODM,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\Mapping\MappingException;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class AnnotationDriver implements Driver
{
    /**
     * The AnnotationReader.
     *
     * @var AnnotationReader
     */
    private $reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    private $paths = array();

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    private $fileExtension = '.php';

    /**
     * @param array
     */
    private $classNames;

    /**
     * Document annotation classes, ordered by precedence.
     */
    static private $documentAnnotationClasses = array(
        'Doctrine\\ODM\\PHPCR\\Mapping\\Annotations\\Document',
        'Doctrine\\ODM\\PHPCR\\Mapping\\Annotations\\MappedSuperclass',
        'Doctrine\\ODM\\PHPCR\\Mapping\\Annotations\\EmbeddedDocument',
    );

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param $reader The AnnotationReader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found.
     */
    public function __construct(Reader $reader, $paths = null)
    {
        $this->reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    /**
     * Append lookup paths to metadata driver.
     *
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $reflClass = $class->getReflectionClass();

        $documentAnnots = array();
        foreach ($this->reader->getClassAnnotations($reflClass) as $annot) {
          foreach (self::$documentAnnotationClasses as $i => $annotClass) {
            if ($annot instanceof $annotClass) {
              $documentAnnots[$i] = $annot;
            }
          }
        }
        if (!$documentAnnots) {
          throw MappingException::classIsNotAValidDocument($className);
        }

        // find the winning document annotation
        ksort($documentAnnots);
        $documentAnnot = reset($documentAnnots);

        if (!$documentAnnot->alias) {
            throw new MappingException('Alias must be specified in the Document() annotation mapping');
        }

        $class->setAlias($documentAnnot->alias);
        if (isset($documentAnnot->isVersioned) && $documentAnnot->isVersioned) {
            $class->setVersioned(true);
        }
        $class->setNodeType($documentAnnot->nodeType);

        if (isset($documentAnnot->referenceable) && $documentAnnot->referenceable) {
            $class->setReferenceable(true);
        }

        if ($documentAnnot->repositoryClass) {
            $class->setCustomRepositoryClassName($documentAnnot->repositoryClass);
        }

        foreach ($reflClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            foreach ($this->reader->getPropertyAnnotations($property) as $fieldAnnot) {
                if ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\Property) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapField($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\Id) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapId($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\Node) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapNode($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\Child) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapChild($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\Children) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapChildren($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\ReferenceOne) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapManyToOne($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Annotations\ReferenceMany) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapManyToMany($mapping);
                }
            }
        }

        foreach ($reflClass->getMethods() as $method) {
            if ($method->isPublic()) {
                foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                    if ($annot instanceof ODM\PrePersist) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::prePersist);
                    } elseif ($annot instanceof  ODM\PostPersist) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::postPersist);
                    } elseif ($annot instanceof ODM\PreUpdate) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::preUpdate);
                    } elseif ($annot instanceof ODM\PostUpdate) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::postUpdate);
                    } elseif ($annot instanceof ODM\PreRemove) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::preRemove);
                    } elseif ($annot instanceof ODM\PostRemove) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::postRemove);
                    } elseif ($annot instanceof ODM\PreLoad) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::preLoad);
                    } elseif ($annot instanceof  ODM\PostLoad) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\PHPCR\Event::postLoad);
                    }
                }
            }
        }

    }

    /**
     * Whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     * A class is non-transient if it is annotated with either @Entity or
     * @MappedSuperclass in the class doc block.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $rc = new \ReflectionClass($className);

        if ($this->reader->getClassAnnotation($rc, 'Doctrine\ODM\PHPCR\Mapping\Annotations\Document')) {
            return false;
        }

        if ($this->reader->getClassAnnotation($rc, 'Doctrine\ODM\PHPCR\Mapping\Annotations\MappedSuperclass')) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if (!$this->paths) {
            throw MappingException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath();
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename($this->fileExtension)) == $file->getBasename()) {
                    continue;
                }

                $sourceFile = realpath($file->getPathName());
                require_once $sourceFile;
                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && !$this->isTransient($className)) {
                $classes[] = $className;
            }
        }
        $this->classNames = $classes;

        return $classes;
    }
}
