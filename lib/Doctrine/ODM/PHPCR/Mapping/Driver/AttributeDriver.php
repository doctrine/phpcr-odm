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

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\Attributes as ODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes\Document;
use Doctrine\ODM\PHPCR\Mapping\Attributes\MappedSuperclass;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * Read mapping metadata from PHP attributes.
 *
 * @license http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @see     www.doctrine-project.org
 * @since   1.8
 *
 * @author  David Buchmann <david@liip.ch>
 */
class AttributeDriver implements MappingDriver
{
    use ColocatedMappingDriver;

    /**
     * Document attribute classes, ordered by precedence.
     */
    private const DOCUMENT_ATTRIBUTE_CLASSES = [
        Document::class => 0,
        MappedSuperclass::class => 1,
    ];

    private AttributeReader $reader;

    /**
     * @param array<string> $paths
     */
    public function __construct(array $paths)
    {
        $this->reader = new AttributeReader();
        $this->addPaths($paths);
    }

    public function isTransient($className)
    {
        $classAttributes = $this->reader->getClassAttributes(new \ReflectionClass($className));

        foreach ($classAttributes as $a) {
            if (array_key_exists($a::class, self::DOCUMENT_ATTRIBUTE_CLASSES)) {
                return false;
            }
        }

        return true;
    }

    public function loadMetadataForClass($className, PersistenceClassMetadata $metadata): void
    {
        \assert($metadata instanceof ClassMetadata);
        $reflectionClass = $metadata->getReflectionClass();
        $classAttributes = $this->reader->getClassAttributes($reflectionClass);

        // Evaluate document attribute
        if (array_key_exists(ODM\Document::class, $classAttributes)) {
            $documentAttribute = $classAttributes[ODM\Document::class];
            \assert($documentAttribute instanceof ODM\Document);
        } elseif (isset($classAttributes[ODM\MappedSuperclass::class])) {
            $documentAttribute = $classAttributes[ODM\MappedSuperclass::class];
            \assert($documentAttribute instanceof ODM\MappedSuperclass);
            $metadata->isMappedSuperclass = true;
        } else {
            throw MappingException::classIsNotAValidDocument($className);
        }

        if (null !== $documentAttribute->referenceable) {
            $metadata->setReferenceable($documentAttribute->referenceable);
        }

        if (null !== $documentAttribute->versionable) {
            $metadata->setVersioned($documentAttribute->versionable);
        }

        if (null !== $documentAttribute->uniqueNodeType) {
            $metadata->setUniqueNodeType($documentAttribute->uniqueNodeType);
        }

        if (null !== $documentAttribute->mixins) {
            $metadata->setMixins($documentAttribute->mixins);
        }

        if (null !== $documentAttribute->inheritMixins) {
            $metadata->setInheritMixins($documentAttribute->inheritMixins);
        }

        if (null !== $documentAttribute->nodeType) {
            $metadata->setNodeType($documentAttribute->nodeType);
        }

        if (null !== $documentAttribute->repositoryClass) {
            $metadata->setCustomRepositoryClassName($documentAttribute->repositoryClass);
        }

        if (null !== $documentAttribute->translator) {
            $metadata->setTranslator($documentAttribute->translator);
        }

        if (null !== $documentAttribute->childClasses) {
            $metadata->setChildClasses($documentAttribute->childClasses);
        }

        if (null !== $documentAttribute->isLeaf) {
            $metadata->setIsLeaf($documentAttribute->isLeaf);
        }

        foreach ($reflectionClass->getProperties() as $property) {
            if ($metadata->isInheritedField($property->name)
                && $metadata->name !== $property->getDeclaringClass()->getName()
            ) {
                continue;
            }

            $mapping = [];
            $mapping['fieldName'] = $property->getName();

            foreach ($this->reader->getPropertyAttributes($property) as $fieldAttribute) {
                if ($fieldAttribute instanceof ODM\Field) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapField($mapping);
                } elseif ($fieldAttribute instanceof ODM\Id) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapId($mapping);
                } elseif ($fieldAttribute instanceof ODM\Node) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapNode($mapping);
                } elseif ($fieldAttribute instanceof ODM\Nodename) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapNodename($mapping);
                } elseif ($fieldAttribute instanceof ODM\ParentDocument) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapParentDocument($mapping);
                } elseif ($fieldAttribute instanceof ODM\Child) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapChild($mapping);
                } elseif ($fieldAttribute instanceof ODM\Children) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapChildren($mapping);
                } elseif ($fieldAttribute instanceof ODM\ReferenceOne) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapManyToOne($mapping);
                } elseif ($fieldAttribute instanceof ODM\ReferenceMany) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapManyToMany($mapping);
                } elseif ($fieldAttribute instanceof ODM\Referrers) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAttribute->cascade);
                    $metadata->mapReferrers($mapping);
                } elseif ($fieldAttribute instanceof ODM\MixedReferrers) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapMixedReferrers($mapping);
                } elseif ($fieldAttribute instanceof ODM\Locale) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapLocale($mapping);
                } elseif ($fieldAttribute instanceof ODM\Depth) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapDepth($mapping);
                } elseif ($fieldAttribute instanceof ODM\VersionName) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapVersionName($mapping);
                } elseif ($fieldAttribute instanceof ODM\VersionCreated) {
                    $mapping = array_merge($mapping, (array) $fieldAttribute);
                    $metadata->mapVersionCreated($mapping);
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() == $metadata->name) {
                foreach ($this->reader->getMethodAttributes($method) as $methodAttribute) {
                    if ($methodAttribute instanceof ODM\PrePersist) {
                        $metadata->addLifecycleCallback($method->getName(), Event::prePersist);
                    } elseif ($methodAttribute instanceof ODM\PostPersist) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postPersist);
                    } elseif ($methodAttribute instanceof ODM\PreUpdate) {
                        $metadata->addLifecycleCallback($method->getName(), Event::preUpdate);
                    } elseif ($methodAttribute instanceof ODM\PostUpdate) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postUpdate);
                    } elseif ($methodAttribute instanceof ODM\PreRemove) {
                        $metadata->addLifecycleCallback($method->getName(), Event::preRemove);
                    } elseif ($methodAttribute instanceof ODM\PostRemove) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postRemove);
                    } elseif ($methodAttribute instanceof ODM\PostLoad) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postLoad);
                    }
                }
            }
        }

        $metadata->validateClassMapping();
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @return int a bitmask of cascade options
     */
    private function getCascadeMode(?array $cascadeList): int
    {
        if (!$cascadeList) {
            return 0;
        }

        $cascade = 0;
        foreach ($cascadeList as $cascadeMode) {
            $constantName = 'Doctrine\ODM\PHPCR\Mapping\ClassMetadata::CASCADE_'.strtoupper($cascadeMode);
            if (!defined($constantName)) {
                throw new MappingException("Cascade mode '$cascadeMode' not supported.");
            }
            $cascade |= constant($constantName);
        }

        return $cascade;
    }
}

interface_exists(PersistenceClassMetadata::class);
