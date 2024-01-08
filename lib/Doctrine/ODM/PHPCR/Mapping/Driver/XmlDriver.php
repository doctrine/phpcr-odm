<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata as PhpcrClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\MappingException as DoctrineMappingException;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class XmlDriver extends FileDriver
{
    public const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * @param PhpcrClassMetadata $class
     */
    public function loadMetadataForClass($className, ClassMetadata $class): void
    {
        try {
            $xmlRoot = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$xmlRoot) {
            return;
        }

        if (isset($xmlRoot['repository-class'])) {
            $class->setCustomRepositoryClassName((string) $xmlRoot['repository-class']);
        }

        if (isset($xmlRoot['translator'])) {
            $class->setTranslator((string) $xmlRoot['translator']);
        }

        if (isset($xmlRoot['versionable']) && 'false' !== $xmlRoot['versionable']) {
            $class->setVersioned(strtolower($xmlRoot['versionable']));
        }

        if (isset($xmlRoot['referenceable']) && 'false' !== $xmlRoot['referenceable']) {
            $class->setReferenceable((bool) $xmlRoot['referenceable']);
        }

        if (isset($xmlRoot['uniqueNodeType']) && 'false' !== $xmlRoot['uniqueNodeType']) {
            $class->setUniqueNodeType((bool) $xmlRoot['uniqueNodeType']);
        }

        if (isset($xmlRoot['is-leaf'])) {
            if (!in_array($value = (string) $xmlRoot['is-leaf'], ['true', 'false'])) { // must not do strict comparison here
                throw new MappingException(sprintf(
                    'Value of is-leaf must be "true" or "false", got "%s" for class "%s"',
                    $value,
                    $className
                ));
            }

            $class->setIsLeaf('true' === $value);
        }

        if (isset($xmlRoot->mixins)) {
            $mixins = [];
            foreach ($xmlRoot->mixins->mixin as $mixin) {
                $attributes = $mixin->attributes();
                if (!isset($attributes['type'])) {
                    throw new MappingException('<mixin> missing mandatory type attribute');
                }
                $mixins[] = (string) $attributes['type'];
            }
            $class->setMixins($mixins);
            $attributes = $xmlRoot->mixins->attributes();
            if (isset($attributes['inherit'])) {
                $class->setInheritMixins((bool) $attributes['inherit']);
            }
        }

        if (isset($xmlRoot['node-type'])) {
            $class->setNodeType((string) $xmlRoot['node-type']);
        }

        if ('mapped-superclass' === $xmlRoot->getName()) {
            $class->isMappedSuperclass = true;
        }

        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping = [];
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string) $value;
                    // convert bool fields
                    if (in_array($key, ['id', 'multivalue', 'assoc', 'translated', 'nullable'], true)) {
                        $mapping[$key] = 'true' === $mapping[$key];
                    }
                }
                if (!isset($mapping['name'])) {
                    throw new MappingException(sprintf('Missing name attribute for field of %s', $className));
                }
                $mapping['fieldName'] = $mapping['name'];
                unset($mapping['name']);
                $class->mapField($mapping);
            }
        }
        if (isset($xmlRoot->id)) {
            $mapping = [
                'fieldName' => (string) $xmlRoot->id->attributes()->name,
                'id' => true,
            ];
            if (isset($xmlRoot->id->generator, $xmlRoot->id->generator->attributes()->strategy)) {
                $mapping['strategy'] = (string) $xmlRoot->id->generator->attributes()->strategy;
            }
            $class->mapId($mapping);
        }
        if (isset($xmlRoot->node)) {
            $class->mapNode(['fieldName' => (string) $xmlRoot->node->attributes()->name]);
        }
        if (isset($xmlRoot->nodename)) {
            $class->mapNodename(['fieldName' => (string) $xmlRoot->nodename->attributes()->name]);
        }
        if (isset($xmlRoot->{'parent-document'})) {
            $mapping = [
                'fieldName' => (string) $xmlRoot->{'parent-document'}->attributes()->name,
                'cascade' => (isset($xmlRoot->{'parent-document'}->cascade)) ? $this->getCascadeMode($xmlRoot->{'parent-document'}->cascade) : 0,
            ];
            $class->mapParentDocument($mapping);
        }
        if (isset($xmlRoot->child)) {
            foreach ($xmlRoot->child as $child) {
                $attributes = $child->attributes();
                $mapping = [
                    'fieldName' => (string) $attributes->name,
                    'cascade' => (isset($child->cascade)) ? $this->getCascadeMode($child->cascade) : 0,
                ];
                if (isset($attributes['node-name'])) {
                    $mapping['nodeName'] = (string) $attributes->{'node-name'};
                }
                $class->mapChild($mapping);
            }
        }
        if (isset($xmlRoot->children)) {
            foreach ($xmlRoot->children as $children) {
                $attributes = $children->attributes();
                $mapping = [
                    'fieldName' => (string) $attributes->name,
                    'cascade' => (isset($children->cascade)) ? $this->getCascadeMode($children->cascade) : 0,
                    'filter' => isset($attributes['filter']) ? (array) $attributes->filter : null,
                    'fetchDepth' => isset($attributes['fetch-depth']) ? (int) $attributes->{'fetch-depth'} : -1,
                    'ignoreUntranslated' => !empty($attributes['ignore-untranslated']),
                ];
                $class->mapChildren($mapping);
            }
        }
        if (isset($xmlRoot->{'reference-many'})) {
            foreach ($xmlRoot->{'reference-many'} as $reference) {
                $attributes = $reference->attributes();
                $reference['cascade'] = (isset($reference->cascade)) ? $this->getCascadeMode($reference->cascade) : 0;
                $reference['fieldName'] = (string) $attributes->name ?: null;
                $this->addReferenceMapping($class, $reference, 'many');
            }
        }
        if (isset($xmlRoot->{'reference-one'})) {
            foreach ($xmlRoot->{'reference-one'} as $reference) {
                $attributes = $reference->attributes();
                $reference['cascade'] = (isset($reference->cascade)) ? $this->getCascadeMode($reference->cascade) : 0;
                $reference['fieldName'] = (string) $attributes->name ?: null;
                $this->addReferenceMapping($class, $reference, 'one');
            }
        }

        if (isset($xmlRoot->locale)) {
            $class->mapLocale(['fieldName' => (string) $xmlRoot->locale->attributes()->name]);
        }

        if (isset($xmlRoot->depth)) {
            $class->mapDepth(['fieldName' => (string) $xmlRoot->depth->attributes()->name]);
        }

        if (isset($xmlRoot->{'mixed-referrers'})) {
            foreach ($xmlRoot->{'mixed-referrers'} as $mixedReferrers) {
                $attributes = $mixedReferrers->attributes();
                $mapping = [
                    'fieldName' => (string) $attributes->name,
                    'referenceType' => isset($attributes['reference-type']) ? strtolower((string) $attributes->{'reference-type'}) : null,
                ];
                $class->mapMixedReferrers($mapping);
            }
        }
        if (isset($xmlRoot->referrers)) {
            foreach ($xmlRoot->referrers as $referrers) {
                $attributes = $referrers->attributes();
                if (!isset($attributes['referenced-by'])) {
                    throw new MappingException("$className is missing the referenced-by attribute for the referrer field ".$attributes->name);
                }
                if (!isset($attributes['referring-document'])) {
                    throw new MappingException("$className is missing the referring-document attribute for the referrer field ".$attributes->name);
                }
                // referenceType is determined from the referencedBy field of referringDocument
                $mapping = [
                    'fieldName' => (string) $attributes->name,
                    'cascade' => (isset($referrers->cascade)) ? $this->getCascadeMode($referrers->cascade) : 0,
                    'referencedBy' => (string) $attributes->{'referenced-by'},
                    'referringDocument' => (string) $attributes->{'referring-document'},
                ];
                $class->mapReferrers($mapping);
            }
        }
        if (isset($xmlRoot->{'version-name'})) {
            $class->mapVersionName(['fieldName' => (string) $xmlRoot->{'version-name'}->attributes()->name]);
        }
        if (isset($xmlRoot->{'version-created'})) {
            $class->mapVersionCreated(['fieldName' => (string) $xmlRoot->{'version-created'}->attributes()->name]);
        }

        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $class->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ODM\PHPCR\Event::'.(string) $lifecycleCallback['type']));
            }
        }

        if (isset($xmlRoot->uuid)) {
            $mapping = [];
            $attributes = $xmlRoot->uuid->attributes();

            foreach ($attributes as $key => $value) {
                $mapping[$key] = (string) $value;
            }

            if (!array_key_exists('name', $mapping)) {
                throw new MappingException(sprintf('Missing name attribute for field of %s', $className));
            }

            $mapping['uuid'] = true;
            $mapping['fieldName'] = $mapping['name'];
            $class->mapField($mapping);
        }

        if (isset($xmlRoot->{'child-class'})) {
            $childClasses = [];
            foreach ($xmlRoot->{'child-class'} as $requiredClass) {
                $childClasses[] = (string) $requiredClass['name'];
            }
            $class->setChildClasses($childClasses);
        }

        $class->validateClassMapping();
    }

    private function addReferenceMapping(PhpcrClassMetadata $class, \SimpleXMLElement $reference, string $type): void
    {
        $attributes = (array) $reference->attributes();
        $mapping = $attributes['@attributes'];
        $mapping['strategy'] = isset($mapping['strategy']) ? strtolower($mapping['strategy']) : null;
        $mapping['targetDocument'] = $mapping['target-document'] ?? null;
        unset($mapping['target-document']);

        if ('many' === $type) {
            $class->mapManyToMany($mapping);
        } elseif ('one' === $type) {
            $class->mapManyToOne($mapping);
        }
    }

    protected function loadMappingFile($file): array
    {
        $result = [];
        if (\PHP_VERSION_ID < 80000) {
            $entity = libxml_disable_entity_loader(true);
        }
        $xmlElement = simplexml_load_string(file_get_contents($file));
        if (\PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($entity);
        }

        foreach (['document', 'mapped-superclass'] as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $className = (string) $documentElement['name'];
                    $result[$className] = $documentElement;
                }
            }
        }

        return $result;
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @return int a bitmask of cascade options
     */
    private function getCascadeMode(\SimpleXMLElement $cascadeElement): int
    {
        $cascade = 0;
        foreach ($cascadeElement->children() as $action) {
            // According to the JPA specifications, XML uses "cascade-persist"
            // instead of "persist". Here, both variations
            // are supported because both YAML and Attributes use "persist"
            // and we want to make sure that this driver doesn't need to know
            // anything about the supported cascading actions
            $cascadeMode = str_replace('cascade-', '', $action->getName());
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
