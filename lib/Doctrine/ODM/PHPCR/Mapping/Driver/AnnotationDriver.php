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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\PHPCR\Mapping\MappingException;

// TODO: this is kinda ugly
require __DIR__ . '/DoctrineAnnotations.php';

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
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param $reader The AnnotationReader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found.
     */
    public function __construct(AnnotationReader $reader, $paths = null)
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

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);
        if (isset($classAnnotations['Doctrine\ODM\PHPCR\Mapping\Document'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\PHPCR\Mapping\Document'];
        } else {
            throw MappingException::classIsNotAValidDocument($className);
        }

        if (!$documentAnnot->alias) {
            throw new MappingException('Alias must be specified in the jcr:Document() mapping');
        }

        $class->setAlias($documentAnnot->alias);
        $class->setNodeType($documentAnnot->nodeType);

        if ($documentAnnot->repositoryClass) {
            $class->setCustomRepositoryClassName($documentAnnot->repositoryClass);
        }

        foreach ($reflClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            foreach ($this->reader->getPropertyAnnotations($property) as $fieldAnnot) {
                if ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Property) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapProperty($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Path) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapPath($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\Node) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapNode($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\ReferenceOne) {
                    $cascade = 0;
                    foreach ($fieldAnnot->cascade AS $cascadeMode) {
                        $cascade += constant('Doctrine\ODM\PHPCR\Mapping\ClassMetadata::CASCADE_' . strtoupper($cascadeMode));
                    }
                    $fieldAnnot->cascade = $cascade;

                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapManyToOne($mapping);
                } elseif ($fieldAnnot instanceof \Doctrine\ODM\PHPCR\Mapping\ReferenceMany) {
                    $cascade = 0;
                    foreach ($fieldAnnot->cascade AS $cascadeMode) {
                        $cascade += constant('Doctrine\ODM\PHPCR\Mapping\ClassMetadata::CASCADE_' . strtoupper($cascadeMode));
                    }
                    $fieldAnnot->cascade = $cascade;

                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapManyToMany($mapping);
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
        $classAnnotations = $this->reader->getClassAnnotations(new \ReflectionClass($className));

        return !isset($classAnnotations['Doctrine\ODM\PHPCR\Mapping\Document']) &&
               !isset($classAnnotations['Doctrine\ODM\PHPCR\Mapping\MappedSuperclass']);
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