<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Document;
use Doctrine\ODM\PHPCR\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata as PhpcrClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Pascal Helfenstein <nicam@nicam.ch>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author David Buchmann <david@liip.ch>
 */
class AnnotationDriver extends AbstractAnnotationDriver implements MappingDriver
{
    /**
     * {@inheritdoc}
     *
     * Document annotation classes, ordered by precedence.
     */
    protected $entityAnnotationClasses = [
        Document::class => 0,
        MappedSuperclass::class => 1,
    ];

    /**
     * {@inheritdoc}
     *
     * @param PhpcrClassMetadata $metadata
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $reflClass = $metadata->getReflectionClass();

        $documentAnnots = [];
        foreach ($this->reader->getClassAnnotations($reflClass) as $annot) {
            foreach ($this->entityAnnotationClasses as $annotClass => $i) {
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

        if ($documentAnnot instanceof ODM\MappedSuperclass) {
            $metadata->isMappedSuperclass = true;
        }
        if (null !== $documentAnnot->referenceable) {
            $metadata->setReferenceable($documentAnnot->referenceable);
        }

        if (null !== $documentAnnot->versionable) {
            $metadata->setVersioned($documentAnnot->versionable);
        }

        if (null !== $documentAnnot->uniqueNodeType) {
            $metadata->setUniqueNodeType($documentAnnot->uniqueNodeType);
        }

        if (null !== $documentAnnot->mixins) {
            $metadata->setMixins($documentAnnot->mixins);
        }

        if (null !== $documentAnnot->inheritMixins) {
            $metadata->setInheritMixins($documentAnnot->inheritMixins);
        }

        if (null !== $documentAnnot->nodeType) {
            $metadata->setNodeType($documentAnnot->nodeType);
        }

        if (null !== $documentAnnot->repositoryClass) {
            $metadata->setCustomRepositoryClassName($documentAnnot->repositoryClass);
        }

        if (null !== $documentAnnot->translator) {
            $metadata->setTranslator($documentAnnot->translator);
        }

        if ([] !== $documentAnnot->childClasses) {
            $metadata->setChildClasses($documentAnnot->childClasses);
        }

        $metadata->setIsLeaf($documentAnnot->isLeaf);

        foreach ($reflClass->getProperties() as $property) {
            if ($metadata->isInheritedField($property->name)
                && $metadata->name !== $property->getDeclaringClass()->getName()
            ) {
                continue;
            }

            $mapping = [];
            $mapping['fieldName'] = $property->getName();

            foreach ($this->reader->getPropertyAnnotations($property) as $fieldAnnot) {
                if ($fieldAnnot instanceof ODM\Property) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapField($mapping);
                } elseif ($fieldAnnot instanceof ODM\Id) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapId($mapping);
                } elseif ($fieldAnnot instanceof ODM\Node) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapNode($mapping);
                } elseif ($fieldAnnot instanceof ODM\Nodename) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapNodename($mapping);
                } elseif ($fieldAnnot instanceof ODM\ParentDocument) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapParentDocument($mapping);
                } elseif ($fieldAnnot instanceof ODM\Child) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapChild($mapping);
                } elseif ($fieldAnnot instanceof ODM\Children) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapChildren($mapping);
                } elseif ($fieldAnnot instanceof ODM\ReferenceOne) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapManyToOne($mapping);
                } elseif ($fieldAnnot instanceof ODM\ReferenceMany) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapManyToMany($mapping);
                } elseif ($fieldAnnot instanceof ODM\Referrers) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $mapping['cascade'] = $this->getCascadeMode($fieldAnnot->cascade);
                    $metadata->mapReferrers($mapping);
                } elseif ($fieldAnnot instanceof ODM\MixedReferrers) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapMixedReferrers($mapping);
                } elseif ($fieldAnnot instanceof ODM\Locale) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapLocale($mapping);
                } elseif ($fieldAnnot instanceof ODM\Depth) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapDepth($mapping);
                } elseif ($fieldAnnot instanceof ODM\VersionName) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapVersionName($mapping);
                } elseif ($fieldAnnot instanceof ODM\VersionCreated) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapVersionCreated($mapping);
                }
            }
        }

        foreach ($reflClass->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() == $metadata->name) {
                foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                    if ($annot instanceof ODM\PrePersist) {
                        $metadata->addLifecycleCallback($method->getName(), Event::prePersist);
                    } elseif ($annot instanceof ODM\PostPersist) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postPersist);
                    } elseif ($annot instanceof ODM\PreUpdate) {
                        $metadata->addLifecycleCallback($method->getName(), Event::preUpdate);
                    } elseif ($annot instanceof ODM\PostUpdate) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postUpdate);
                    } elseif ($annot instanceof ODM\PreRemove) {
                        $metadata->addLifecycleCallback($method->getName(), Event::preRemove);
                    } elseif ($annot instanceof ODM\PostRemove) {
                        $metadata->addLifecycleCallback($method->getName(), Event::postRemove);
                    } elseif ($annot instanceof ODM\PostLoad) {
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
    private function getCascadeMode(array $cascadeList)
    {
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

interface_exists(ClassMetadata::class);
