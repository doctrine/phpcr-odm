<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata as PhpcrClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\MappingException as DoctrineMappingException;
use Symfony\Component\Yaml\Yaml;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class YamlDriver extends FileDriver
{
    public const DEFAULT_FILE_EXTENSION = '.dcm.yml';

    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    public function loadMetadataForClass($className, ClassMetadata $class): void
    {
        \assert($class instanceof PhpcrClassMetadata);
        try {
            $element = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$element) {
            return;
        }
        $element['type'] = $element['type'] ?? 'document';

        if (isset($element['repositoryClass'])) {
            $class->setCustomRepositoryClassName($element['repositoryClass']);
        }

        if (isset($element['translator'])) {
            $class->setTranslator($element['translator']);
        }

        if (isset($element['versionable']) && $element['versionable']) {
            $class->setVersioned($element['versionable']);
        }

        if (isset($element['referenceable']) && $element['referenceable']) {
            $class->setReferenceable($element['referenceable']);
        }

        if (isset($element['uniqueNodeType']) && $element['uniqueNodeType']) {
            $class->setUniqueNodeType($element['uniqueNodeType']);
        }

        if (isset($element['mixins'])) {
            $mixins = [];
            foreach ($element['mixins'] as $mixin) {
                $mixins[] = $mixin;
            }
            $class->setMixins($mixins);
        }

        if (isset($element['inheritMixins'])) {
            $class->setInheritMixins($element['inheritMixins']);
        }

        if (isset($element['nodeType'])) {
            $class->setNodeType($element['nodeType']);
        }

        if ('mappedSuperclass' === $element['type']) {
            $class->isMappedSuperclass = true;
        }

        if (isset($element['fields'])) {
            foreach ($element['fields'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $type = $mapping;
                    $mapping = [];
                    $mapping['type'] = $type;
                }
                if (!array_key_exists('fieldName', $mapping)) {
                    $mapping['fieldName'] = $fieldName;
                }
                $class->mapField($mapping);
            }
        }

        if (isset($element['uuid'])) {
            $mapping = [
                'fieldName' => $element['uuid'],
                'uuid' => true,
            ];
            $class->mapField($mapping);
        }
        if (isset($element['id'])) {
            if (is_array($element['id'])) {
                if (!isset($element['id']['fieldName'])) {
                    throw new MappingException('Missing fieldName property for id field');
                }
                $fieldName = $element['id']['fieldName'];
            } else {
                $fieldName = $element['id'];
            }
            $mapping = ['fieldName' => $fieldName, 'id' => true];
            if (isset($element['id']['generator']['strategy'])) {
                $mapping['strategy'] = $element['id']['generator']['strategy'];
            }
            $class->mapId($mapping);
        }
        if (isset($element['node'])) {
            $class->mapNode(['fieldName' => $element['node']]);
        }
        if (isset($element['nodename'])) {
            $class->mapNodename(['fieldName' => $element['nodename']]);
        }
        if (isset($element['parentdocument'])) {
            $mapping = [
                'fieldName' => $element['parentdocument'],
                'cascade' => (isset($element['cascade'])) ? $this->getCascadeMode($element['cascade']) : 0,
            ];

            $class->mapParentDocument($mapping);
        }
        if (isset($element['child'])) {
            foreach ($element['child'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $name = $mapping;
                    $mapping = [];
                    $mapping['nodeName'] = $name;
                }
                if (!array_key_exists('fieldName', $mapping)) {
                    $mapping['fieldName'] = $fieldName;
                }
                $mapping['cascade'] = (array_key_exists('cascade', $mapping)) ? $this->getCascadeMode($mapping['cascade']) : 0;
                $class->mapChild($mapping);
            }
        }
        if (isset($element['children'])) {
            foreach ($element['children'] as $fieldName => $mapping) {
                if (null === $mapping) {
                    $mapping = [];
                }
                if (is_string($mapping)) {
                    $filter = $mapping;
                    $mapping = [];
                    $mapping['filter'] = $filter;
                }
                if (!array_key_exists('fieldName', $mapping)) {
                    $mapping['fieldName'] = $fieldName;
                }
                if (!array_key_exists('filter', $mapping)) {
                    $mapping['filter'] = null;
                } elseif (is_string($mapping['filter'])) {
                    $mapping['filter'] = (array) $mapping['filter'];
                }
                if (!array_key_exists('fetchDepth', $mapping)) {
                    $mapping['fetchDepth'] = -1;
                }
                if (!array_key_exists('ignoreUntranslated', $mapping)) {
                    $mapping['ignoreUntranslated'] = false;
                }
                $mapping['cascade'] = (array_key_exists('cascade', $mapping)) ? $this->getCascadeMode($mapping['cascade']) : 0;
                $class->mapChildren($mapping);
            }
        }
        if (isset($element['referenceOne'])) {
            foreach ($element['referenceOne'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'one');
            }
        }
        if (isset($element['referenceMany'])) {
            foreach ($element['referenceMany'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'many');
            }
        }

        if (isset($element['locale'])) {
            $class->mapLocale(['fieldName' => $element['locale']]);
        }

        if (isset($element['depth'])) {
            $class->mapDepth(['fieldName' => $element['depth']]);
        }

        if (isset($element['mixedReferrers'])) {
            foreach ($element['mixedReferrers'] as $name => $attributes) {
                $mapping = [
                    'fieldName' => $name,
                    'referenceType' => $attributes['referenceType'] ?? null,
                ];
                $class->mapMixedReferrers($mapping);
            }
        }
        if (isset($element['referrers'])) {
            foreach ($element['referrers'] as $name => $attributes) {
                if (!isset($attributes['referencedBy'])) {
                    throw new MappingException("$className is missing the referencedBy attribute for the referrer field $name");
                }
                if (!isset($attributes['referringDocument'])) {
                    throw new MappingException("$className is missing the referringDocument attribute for the referrer field $name");
                }
                $mapping = [
                    'fieldName' => $name,
                    'referencedBy' => $attributes['referencedBy'],
                    'referringDocument' => $attributes['referringDocument'],
                    'cascade' => (isset($attributes['cascade'])) ? $this->getCascadeMode($attributes['cascade']) : 0,
                ];
                $class->mapReferrers($mapping);
            }
        }
        if (isset($element['versionName'])) {
            $class->mapVersionName(['fieldName' => $element['versionName']]);
        }
        if (isset($element['versionCreated'])) {
            $class->mapVersionCreated(['fieldName' => $element['versionCreated']]);
        }

        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $type => $methods) {
                foreach ($methods as $method) {
                    $class->addLifecycleCallback($method, constant('Doctrine\ODM\PHPCR\Event::'.$type));
                }
            }
        }

        if (isset($element['child_classes'])) {
            $class->setChildClasses($element['child_classes']);
        }

        if (isset($element['is_leaf'])) {
            $class->setIsLeaf($element['is_leaf']);
        }

        $class->validateClassMapping();
    }

    private function addMappingFromReference(ClassMetadata $class, string $fieldName, array $reference, string $type): void
    {
        /** @var PhpcrClassMetadata $class */
        $mapping = array_merge(['fieldName' => $fieldName], $reference);

        $mapping['cascade'] = (isset($reference['cascade'])) ? $this->getCascadeMode($reference['cascade']) : 0;
        $mapping['name'] = $reference['name'] ?? null;

        if (!array_key_exists('targetDocument', $mapping)) {
            $mapping['targetDocument'] = null;
        }

        if ('many' === $type) {
            $class->mapManyToMany($mapping);
        } elseif ('one' === $type) {
            $class->mapManyToOne($mapping);
        }
    }

    protected function loadMappingFile($file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('File "%s" not found', $file));
        }

        return Yaml::parse(file_get_contents($file));
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param array $cascadeElement the cascade element
     *
     * @return int a bitmask of cascade options
     */
    private function getCascadeMode(array $cascadeElement): int
    {
        $cascade = 0;
        foreach ($cascadeElement as $cascadeMode) {
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
