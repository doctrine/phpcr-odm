<?php

declare(strict_types=1);

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Exception\OutOfBoundsException;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\ReflectionService;
use PHPCR\RepositoryException;
use PHPCR\Util\PathHelper;

/**
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author  Jonathan H. Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  David Buchmann <david@liip.ch>
 * @author  Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class ClassMetadata implements ClassMetadataInterface
{
    public const MANY_TO_ONE = 4;

    public const MANY_TO_MANY = 8;

    public const CASCADE_PERSIST = 1;

    public const CASCADE_REMOVE = 2;

    public const CASCADE_MERGE = 4;

    public const CASCADE_DETACH = 8;

    public const CASCADE_REFRESH = 16;

    public const CASCADE_TRANSLATION = 32;

    public const CASCADE_ALL = 255;

    /**
     * No strategy has been set so far.
     */
    public const GENERATOR_TYPE_NONE = 0;

    /**
     * The repository will be asked to generate the id.
     */
    public const GENERATOR_TYPE_REPOSITORY = 1;

    /**
     * Doctrine will not generate any id for us and you are responsible for
     * manually assigning a valid id string in the document.
     *
     * Be aware that in PHPCR, the parent of a node must exist.
     */
    public const GENERATOR_TYPE_ASSIGNED = 2;

    /**
     * The document uses the parent and name mapping to find its location in
     * the tree.
     */
    public const GENERATOR_TYPE_PARENT = 3;

    /**
     * The document uses the parent mapping to find its location in the tree
     * and will use the PHPCR addNodeAutoNamed feature for the node name.
     */
    public const GENERATOR_TYPE_AUTO = 4;

    protected static $validVersionableAnnotations = ['simple', 'full'];

    /**
     * READ-ONLY: The ReflectionProperty instances of the mapped class.
     *
     * @var \ReflectionProperty[]
     */
    public array $reflFields = [];

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var int constant for the id generator to use for this class
     */
    public int $idGenerator = self::GENERATOR_TYPE_NONE;

    /**
     * READ-ONLY: The field name of the document identifier.
     */
    public ?string $identifier = null;

    /**
     * READ-ONLY: The field name of the UUID field.
     */
    public ?string $uuidFieldName = null;

    /**
     * READ-ONLY: The name of the document class that is stored in the phpcr:class property.
     */
    public ?string $name;

    /**
     * READ-ONLY: The namespace the document class is contained in.
     */
    private string $namespace;

    /**
     * READ-ONLY: The JCR Nodetype to be used for this node.
     */
    public string $nodeType = 'nt:unstructured';

    /**
     * READ-ONLY: The JCR Mixins to be used for this node (including inherited mixins).
     *
     * @var string[]
     */
    public array $mixins = [];

    /**
     * READ-ONLY: Inherit parent class' mixins (default) or not.
     */
    public bool $inheritMixins = true;

    /**
     * READ-ONLY: The field name of the node.
     */
    public ?string $node = null;

    /**
     * READ-ONLY except on document creation: The field name for the name of the node.
     */
    public ?string $nodename = null;

    /**
     * READ-ONLY except on document creation: The field name for the parent document.
     */
    public ?string $parentMapping = null;

    /**
     * READ-ONLY: The name of the custom repository class used for the document class.
     * (Optional).
     */
    public ?string $customRepositoryClassName = null;

    /**
     * READ-ONLY: The field mappings of the class.
     */
    public array $fieldMappings = [];

    /**
     * READ-ONLY: The all mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the Document.
     *
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the document.
     */
    public array $mappings = [];

    /**
     * READ-ONLY: The registered lifecycle callbacks for documents of this class.
     */
    public array $lifecycleCallbacks = [];

    /**
     * The ReflectionClass instance of the mapped class.
     */
    public \ReflectionClass $reflClass;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     */
    public bool $isMappedSuperclass = false;

    /**
     * READ-ONLY: The reference mappings of the class.
     */
    public array $referenceMappings = [];

    /**
     * READ-ONLY: The child mappings of the class.
     */
    public array $childMappings = [];

    /**
     * READ-ONLY: The children mappings of the class.
     */
    public array $childrenMappings = [];

    /**
     * READ-ONLY: The referrers mappings of the class.
     */
    public array $referrersMappings = [];

    /**
     * READ-ONLY: The mixed referrers (read only) mappings of the class.
     */
    public array $mixedReferrersMappings = [];

    /**
     * READ-ONLY: Name of the locale property.
     */
    public ?string $localeMapping = null;

    /**
     * READ-ONLY: Name of the depth property.
     */
    public ?string $depthMapping = null;

    /**
     * READ-ONLY: Name of the version name property of this document.
     */
    public ?string $versionNameField = null;

    /**
     * READ-ONLY: Name of the version created property of this document.
     */
    public ?string $versionCreatedField = null;

    /**
     * READ-ONLY: List of translatable fields.
     */
    public array $translatableFields = [];

    /**
     * READ-ONLY: Whether this document should be versioned. If this is not false, it will
     * be one of the values from self::validVersionableAnnotations.
     *
     * @var bool|string
     */
    public $versionable = false;

    /**
     * READ-ONLY: determines if the document is referenceable or not.
     */
    public bool $referenceable = false;

    /**
     * READ-ONLY: If true, consider this document's node type to be unique among all mappings.
     */
    public bool $uniqueNodeType = false;

    /**
     * READ-ONLY: Strategy key to find field translations.
     * This is the key used for DocumentManagerInterface::getTranslationStrategy.
     */
    public ?string $translator = null;

    /**
     * READ-ONLY: Mapped parent classes.
     *
     * @var string[]
     */
    public array $parentClasses = [];

    /**
     * READ-ONLY: Child class restrictions.
     *
     * If empty then any classes are permitted.
     *
     * @var string[]
     */
    public array $childClasses = [];

    /**
     * READ-ONLY: If the document should be act as a leaf-node and therefore
     *            not be allowed children.
     */
    public bool $isLeaf = false;

    /**
     * The inherited fields of this class.
     */
    private array $inheritedFields = [];

    private InstantiatorInterface $instantiator;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-document mapping
     * metadata of the class with the given name.
     *
     * @param string $className the name of the document class the new instance is used for
     */
    public function __construct($className)
    {
        $this->name = $className;
    }

    /**
     * Initializes a new ClassMetadata instance that will hold the
     * object-relational mapping metadata of the class with the given name.
     */
    public function initializeReflection(ReflectionService $reflService): void
    {
        $this->reflClass = $reflService->getClass($this->name);
        $this->namespace = $reflService->getClassNamespace($this->name);
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     */
    public function wakeupReflection(ReflectionService $reflService): void
    {
        $this->reflClass = $reflService->getClass($this->name);
        $this->namespace = $reflService->getClassNamespace($this->name);
        $fieldNames = array_merge($this->getFieldNames(), $this->getAssociationNames());
        foreach ($fieldNames as $fieldName) {
            $reflField = array_key_exists('declared', $this->mappings[$fieldName])
                ? new \ReflectionProperty($this->mappings[$fieldName]['declared'], $fieldName)
                : $this->reflClass->getProperty($fieldName);
            $reflField->setAccessible(true);
            $this->reflFields[$fieldName] = $reflField;
        }
    }

    /**
     * Check if this node name is valid. Returns null if valid, an exception otherwise.
     *
     * @param string $nodeName The node local name
     */
    public function isValidNodename(string $nodeName): ?RepositoryException
    {
        try {
            $parts = explode(':', $nodeName);
            if (1 > count($parts) || 2 < count($parts)) {
                return new RepositoryException("Name contains illegal characters: $nodeName");
            }
            if ('' === $parts[0]) {
                return new RepositoryException("Name may not be empty: $nodeName");
            }
            PathHelper::assertValidLocalName($parts[0]);
            if (2 === count($parts)) {
                // [0] was the namespace prefix, also check the name
                PathHelper::assertValidLocalName($parts[1]);
                if ('' === $parts[1]) {
                    return new RepositoryException("Local name may not be empty: $nodeName");
                }
            }
        } catch (RepositoryException $e) {
            return $e;
        }

        return null;
    }

    /**
     * Validate Identifier mapping, determine the strategy if none is
     * explicitly set.
     *
     * @throws MappingException if no identifiers are mapped
     */
    public function validateIdentifier(): void
    {
        if (!$this->isMappedSuperclass) {
            if ($this->isIdGeneratorNone()) {
                $this->determineIdStrategy();
            }

            switch ($this->idGenerator) {
                case self::GENERATOR_TYPE_PARENT:
                    if (!($this->parentMapping && $this->nodename)) {
                        throw MappingException::identifierRequired($this->name, 'parent and nodename');
                    }

                    break;
                case self::GENERATOR_TYPE_AUTO:
                    if (!$this->parentMapping) {
                        throw MappingException::identifierRequired($this->name, 'parent');
                    }

                    break;
                case self::GENERATOR_TYPE_REPOSITORY:
                    if (!$this->customRepositoryClassName) {
                        throw MappingException::repositoryRequired($this->name);
                    }

                    break;
                default:
                    if (!$this->identifier) {
                        throw MappingException::identifierRequired($this->name, 'identifier');
                    }

                    break;
            }
        }
    }

    /**
     * Validate that childClasses is empty if isLeaf is true.
     *
     * @throws MappingException if there is a conflict between isLeaf and childClasses
     */
    public function validateChildClasses(): void
    {
        if ($this->isLeaf && count($this->childClasses) > 0) {
            throw new MappingException(sprintf(
                'Cannot map a document as a leaf and define child classes for "%s"',
                $this->name
            ));
        }
    }

    /**
     * Assert that the given class FQN can be a child of the document this
     * metadata represents.
     *
     * @throws OutOfBoundsException
     */
    public function assertValidChildClass(self $class): void
    {
        if ($this->isLeaf()) {
            throw new OutOfBoundsException(sprintf(
                'Document "%s" has been mapped as a leaf. It cannot have children',
                $this->name
            ));
        }

        $childClasses = $this->getChildClasses();

        if (0 === count($childClasses)) {
            return;
        }

        foreach ($childClasses as $childClass) {
            if ($class->name === $childClass || $class->reflClass->isSubclassOf($childClass)) {
                return;
            }
        }

        throw new OutOfBoundsException(sprintf(
            'Document "%s" does not allow children of type "%s". Allowed child classes "%s"',
            $this->name,
            $class->name,
            implode('", "', $childClasses)
        ));
    }

    /**
     * Validate whether this class needs to be referenceable.
     *
     * The document needs to be either referenceable or full versionable.
     * Simple versioning does not imply referenceable.
     *
     * @throws MappingException if there is an invalid reference mapping
     */
    public function validateReferenceable(): void
    {
        if ($this->uuidFieldName && !$this->referenceable && 'full' !== $this->versionable) {
            throw MappingException::notReferenceable($this->name, $this->uuidFieldName);
        }
    }

    /**
     * Validate association targets actually exist.
     *
     * @throws MappingException if there is an invalid reference mapping
     */
    public function validateReferences(): void
    {
        foreach ($this->referenceMappings as $fieldName) {
            $mapping = $this->mappings[$fieldName];
            if (!empty($mapping['targetDocument']) && !class_exists($mapping['targetDocument']) && !interface_exists($mapping['targetDocument'])) {
                throw MappingException::invalidTargetDocumentClass($mapping['targetDocument'], $this->name, $mapping['fieldName']);
            }
        }
    }

    /**
     * Validate translatable fields - ensure that the document has a
     * translator strategy in place.
     *
     * @throws MappingException if there is an inconsistency with translation
     */
    public function validateTranslatables(): void
    {
        if (null === $this->translator && count($this->translatableFields) > 0) {
            throw MappingException::noTranslatorStrategy($this->name, $this->translatableFields);
        }
    }

    /**
     * Validate lifecycle callbacks.
     *
     * @throws MappingException if a declared callback does not exist
     */
    public function validateLifecycleCallbacks(ReflectionService $reflService): void
    {
        foreach ($this->lifecycleCallbacks as $callbacks) {
            foreach ($callbacks as $callbackFuncName) {
                if (!$reflService->hasPublicMethod($this->name, $callbackFuncName)) {
                    throw MappingException::lifecycleCallbackMethodNotFound($this->name, $callbackFuncName);
                }
            }
        }
    }

    /**
     * INTERNAL:
     * Sets the mapped identifier field of this class.
     */
    public function setIdentifier(string $identifier): void
    {
        if ($this->identifier && $this->identifier !== $identifier) {
            throw new MappingException('Cannot map the identifier to more than one property');
        }

        $this->identifier = $identifier;
    }

    /**
     * Registers a custom repository class for the document class.
     */
    public function setCustomRepositoryClassName(?string $repositoryClassName): void
    {
        $this->customRepositoryClassName = $this->fullyQualifiedClassName($repositoryClassName);
        if ($this->customRepositoryClassName && !class_exists($this->customRepositoryClassName)) {
            throw MappingException::repositoryNotExisting($this->name, $this->customRepositoryClassName);
        }
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     */
    public function hasLifecycleCallbacks(string $lifecycleEvent): bool
    {
        return array_key_exists($lifecycleEvent, $this->lifecycleCallbacks);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     */
    public function getLifecycleCallbacks(string $event): array
    {
        return $this->lifecycleCallbacks[$event] ?? [];
    }

    /**
     * Adds a lifecycle callback for documents of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     */
    public function addLifecycleCallback(string $callback, string $event): void
    {
        if (!array_key_exists($event, Event::$lifecycleCallbacks)) {
            throw new MappingException(sprintf(
                '%s is not a valid lifecycle callback event',
                $event
            ));
        }
        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for documents of this class.
     * Any previously registered callbacks are overwritten.
     */
    public function setLifecycleCallbacks(array $callbacks): void
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * @param string|bool $versionable a valid versionable annotation or false to disable versioning
     */
    public function setVersioned($versionable): void
    {
        if ($versionable && !in_array($versionable, self::$validVersionableAnnotations, true)) {
            throw new MappingException(sprintf(
                'Invalid value in "%s" for the versionable annotation: "%s"',
                $this->name,
                $versionable
            ));
        }
        $this->versionable = $versionable;
    }

    public function setReferenceable(bool $referenceable): void
    {
        if ($this->referenceable && !$referenceable) {
            throw new MappingException('Can not overwrite referenceable attribute to false in child class');
        }
        $this->referenceable = $referenceable;
    }

    public function setUniqueNodeType(bool $uniqueNodeType): void
    {
        $this->uniqueNodeType = $uniqueNodeType;
    }

    /**
     * Return true if this document has a unique node type among all mappings.
     */
    public function hasUniqueNodeType(): bool
    {
        return $this->uniqueNodeType;
    }

    public function setNodeType(string $nodeType): void
    {
        $this->nodeType = $nodeType;
    }

    /**
     * Return the JCR node type to be used for this node.
     */
    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    /**
     * Set the JCR mixins.
     *
     * @param string[] $mixins
     */
    public function setMixins(array $mixins): void
    {
        $this->mixins = $mixins;
    }

    /**
     * Return the JCR mixins to be used for this node.
     *
     * @return string[]
     */
    public function getMixins(): array
    {
        return $this->mixins;
    }

    /**
     * Set whether to inherit mixins from parent.
     */
    public function setInheritMixins(bool $inheritMixins): void
    {
        $this->inheritMixins = $inheritMixins;
    }

    /**
     * Return whether to inherit mixins from parent.
     */
    public function getInheritMixins(): bool
    {
        return $this->inheritMixins;
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return \ReflectionProperty[]
     */
    public function getReflectionProperties(): array
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     */
    public function getReflectionProperty(string $name): \ReflectionProperty
    {
        return $this->reflFields[$name];
    }

    /**
     * The namespace this Document class belongs to.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function mapId(array $mapping, self $inherited = null): void
    {
        if (true === ($mapping['id'] ?? false)) {
            $mapping['type'] = 'string';
            $this->setIdentifier($mapping['fieldName']);
            if (array_key_exists('strategy', $mapping)) {
                $this->setIdGenerator($mapping['strategy']);
            }
        }

        $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
    }

    public function mapNode(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'node';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->node = $mapping['fieldName'];
    }

    public function mapNodename(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'nodename';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->nodename = $mapping['fieldName'];
    }

    public function mapParentDocument(array $mapping, self $inherited = null): void
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $mapping['type'] = 'parent';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->parentMapping = $mapping['fieldName'];
    }

    public function mapChild(array $mapping, self $inherited = null): void
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $mapping['type'] = 'child';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, 'nodeName');
        if ($exception = $this->isValidNodename($mapping['nodeName'])) {
            throw MappingException::illegalChildName($this->name, $mapping['fieldName'], $mapping['nodeName'], $exception);
        }
        $this->childMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapChildren(array $mapping, self $inherited = null): void
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $mapping['type'] = 'children';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        if (!is_numeric($mapping['fetchDepth'])) {
            throw new MappingException(sprintf(
                'fetchDepth option must be a numerical value (is "%s") on children mapping "%s" of document %s',
                $mapping['fetchDepth'],
                $mapping['fieldName'],
                $this->name
            ));
        }
        unset($mapping['property']);
        $this->childrenMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapReferrers(array $mapping, self $inherited = null): void
    {
        if (empty($mapping['referencedBy'])) {
            throw MappingException::referrerWithoutReferencedBy($this->name, $mapping['fieldName']);
        }
        if (empty($mapping['referringDocument'])) {
            throw MappingException::referrerWithoutReferringDocument($this->name, $mapping['fieldName']);
        }
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }

        $mapping['sourceDocument'] = $this->name;
        if (array_key_exists('referringDocument', $mapping)) {
            $mapping['referringDocument'] = $this->fullyQualifiedClassName($mapping['referringDocument']);
        }

        $mapping['type'] = 'referrers';
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited, false);
        unset($mapping['strategy']); // this would be a lie, we want the strategy of the referring field
        $this->referrersMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapMixedReferrers(array $mapping, self $inherited = null): void
    {
        if (!(array_key_exists('referenceType', $mapping) && in_array($mapping['referenceType'], [null, 'weak', 'hard'], true))) {
            throw new MappingException(sprintf(
                'You have to specify a "referenceType" for the "%s" mapping which must be null, "weak" or "hard": %s',
                $this->name,
                $mapping['referenceType']
            ));
        }

        if (array_key_exists('referencedBy', $mapping)) {
            throw new MappingException(sprintf(
                'MixedReferrers has no referredBy attribute, use Referrers for this: "%s"',
                $mapping['fieldName']
            ));
        }
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }

        $mapping['type'] = 'mixedreferrers';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->mixedReferrersMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapLocale(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'locale';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->localeMapping = $mapping['fieldName'];
    }

    public function mapDepth(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'depth';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->depthMapping = $mapping['fieldName'];
    }

    public function mapVersionName(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'versionname';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->versionNameField = $mapping['fieldName'];
    }

    public function mapVersionCreated(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = 'versioncreated';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->versionCreatedField = $mapping['fieldName'];
    }

    public function mapLifecycleCallbacks(array $mapping): void
    {
        $this->setLifecycleCallbacks($mapping);
    }

    /**
     * @param ClassMetadata|null $inherited  Metadata of this field in the parent class, if any
     * @param bool               $isField    Whether this is a simple field or an association to another document
     * @param string|bool        $phpcrLabel The name for the PHPCR thing. Usually "property", except for child where this is "name". Referrers use false to not set anything.
     *
     * @throws MappingException
     */
    protected function validateAndCompleteFieldMapping(array $mapping, self $inherited = null, bool $isField = true, $phpcrLabel = 'property'): array
    {
        if ($inherited) {
            if (!array_key_exists('inherited', $mapping)) {
                $this->inheritedFields[$mapping['fieldName']] = $inherited->name;
            }
            if (!array_key_exists('declared', $mapping)) {
                $mapping['declared'] = $inherited->name;
            }
            $this->reflFields[$mapping['fieldName']] = $inherited->getReflectionProperty($mapping['fieldName']);
            $this->mappings[$mapping['fieldName']] = $mapping;

            return $mapping;
        }

        if (empty($mapping['fieldName'])) {
            throw new MappingException(sprintf(
                'Mapping a property requires to specify the field name in "%s".',
                $this->name
            ));
        }

        if (!is_string($mapping['fieldName'])) {
            throw new MappingException(sprintf(
                'Field name must be of type string in "%s".',
                $this->name
            ));
        }

        if (!$this->reflClass->hasProperty($mapping['fieldName'])) {
            throw MappingException::classHasNoField($this->name, $mapping['fieldName']);
        }

        if (empty($mapping['property'])) {
            $mapping['property'] = $mapping['fieldName'];
        }

        if ($phpcrLabel
            && (!array_key_exists($phpcrLabel, $mapping) || empty($mapping[$phpcrLabel]))
        ) {
            $mapping[$phpcrLabel] = $mapping['fieldName'];
        }

        if ($isField && array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
            $mapping['multivalue'] = true;
            if (empty($mapping['assoc'])) {
                $mapping['assoc'] = $mapping['property'].'Keys';
            }
            $mapping['assocNulls'] = $mapping['property'].'Nulls';
        }

        if (array_key_exists($mapping['fieldName'], $this->mappings)) {
            if (!$isField
                || empty($mapping['type'])
                || empty($this->mappings[$mapping['fieldName']])
                || $this->mappings[$mapping['fieldName']]['type'] !== $mapping['type']
            ) {
                throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
            }
        }

        if (!array_key_exists('type', $mapping)) {
            throw MappingException::missingTypeDefinition($this->name, $mapping['fieldName']);
        }

        if ('int' === $mapping['type']) {
            $mapping['type'] = 'long';
        } elseif ('float' === $mapping['type']) {
            $mapping['type'] = 'double';
        }

        $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $reflProp;
        $this->mappings[$mapping['fieldName']] = $mapping;

        return $mapping;
    }

    /**
     * @param string|bool $phpcrLabel
     */
    protected function validateAndCompleteAssociationMapping(array $mapping, self $inherited = null, $phpcrLabel = 'property'): array
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, $phpcrLabel);
        if ($inherited) {
            return $mapping;
        }

        $mapping['sourceDocument'] = $this->name;
        if (array_key_exists('targetDocument', $mapping)) {
            $mapping['targetDocument'] = $this->fullyQualifiedClassName($mapping['targetDocument']);
        }
        if (empty($mapping['strategy'])) {
            $mapping['strategy'] = 'weak';
        } elseif (!in_array($mapping['strategy'], [null, 'weak', 'hard', 'path'], true)) {
            throw new MappingException(sprintf(
                'The attribute "strategy" for the "%s" association has to be either a null, "weak", "hard" or "path": "%s"',
                $this->name,
                $mapping['strategy']
            ));
        }
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $this->mappings[$mapping['fieldName']] = $mapping;

        return $mapping;
    }

    /**
     * Finalize the mapping and make sure that it is consistent.
     *
     * @throws MappingException if inconsistencies are discovered
     */
    public function validateClassMapping(): void
    {
        // associative array fields need a separate property to store the keys.
        // make sure that generated or specified name does not collide with an
        // existing mapping.
        $assocFields = [];
        foreach ($this->fieldMappings as $fieldName) {
            $mapping = $this->mappings[$fieldName];
            if (empty($mapping['assoc'])) {
                continue;
            }

            if (array_key_exists($mapping['assoc'], $this->mappings)) {
                throw MappingException::assocOverlappingFieldDefinition($this->name, $fieldName, $mapping['assoc']);
            }

            if (!empty($assocFields[$mapping['assoc']])) {
                throw MappingException::assocOverlappingAssocDefinition($this->name, $fieldName, $assocFields[$mapping['assoc']]);
            }

            $assocFields[$mapping['assoc']] = $fieldName;
        }

        if (!empty($this->versionNameField) && !$this->versionable) {
            throw new MappingException(sprintf(
                'You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)',
                $this->name,
                $this->versionNameField
            ));
        }

        if (!empty($this->versionCreatedField) && !$this->versionable) {
            throw new MappingException(sprintf(
                'You cannot use the @VersionCreated annotation on the non-versionable document %s (field = %s)',
                $this->name,
                $this->versionCreatedField
            ));
        }

        if (count($this->translatableFields)) {
            if (null === $this->localeMapping) {
                throw new MappingException(sprintf(
                    'You must define a locale mapping for translatable document "%s"',
                    $this->name
                ));
            }
        }

        // we allow mixed referrers on non-referenceable documents. maybe the mix:referenceable is just not mapped
        if (count($this->referrersMappings)) {
            if (!$this->referenceable) {
                throw new MappingException(sprintf(
                    'You can not have referrers mapped on document "%s" as the document is not referenceable',
                    $this->name
                ));
            }

            foreach ($this->referrersMappings as $referrerName) {
                $mapping = $this->mappings[$referrerName];
                // only a santiy check with reflection. otherwise we could run into endless loops
                if (!class_exists($mapping['referringDocument']) && !interface_exists($mapping['referringDocument'])) {
                    throw new MappingException(sprintf(
                        'Invalid referrer mapping on document "%s" for field "%s": The referringDocument class "%s" does not exist',
                        $this->name,
                        $mapping['fieldName'],
                        $mapping['referringDocument']
                    ));
                }
                $reflection = new \ReflectionClass($mapping['referringDocument']);
                if (!$reflection->hasProperty($mapping['referencedBy'])) {
                    throw new MappingException(sprintf(
                        'Invalid referrer mapping on document "%s" for field "%s": The referringDocument "%s" has no property "%s"',
                        $this->name,
                        $mapping['fieldName'],
                        $mapping['referringDocument'],
                        $mapping['referencedBy']
                    ));
                }
            }
        }

        $this->validateIdentifier();
    }

    /**
     * Determine the id strategy for this document. Only call this if no explicit
     * strategy was assigned.
     *
     * @throws MappingException if no strategy is applicable with the mapped fields
     */
    private function determineIdStrategy(): void
    {
        if ($this->parentMapping && $this->nodename) {
            $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);

            return;
        }
        if ($this->parentMapping) {
            $this->setIdGenerator(self::GENERATOR_TYPE_AUTO);

            return;
        }
        if ($this->getIdentifier()) {
            $this->setIdGenerator(self::GENERATOR_TYPE_ASSIGNED);

            return;
        }

        throw new MappingException(sprintf(
            'No id generator could be determined in "%s". Either map a parent and a nodename field and add values to them, or map the id field and configure a mapping strategy',
            $this->name
        ));
    }

    public function mapManyToOne(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = self::MANY_TO_ONE;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapManyToMany(array $mapping, self $inherited = null): void
    {
        $mapping['type'] = self::MANY_TO_MANY;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param string|int $generator
     */
    protected function setIdGenerator($generator): void
    {
        if (is_string($generator)) {
            $generator = constant('Doctrine\ODM\PHPCR\Mapping\ClassMetadata::GENERATOR_TYPE_'.strtoupper($generator));
        }
        $this->idGenerator = $generator;
    }

    /**
     * Sets the translator strategy key.
     */
    public function setTranslator(?string $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Set the mapped parent classes.
     */
    public function setParentClasses(array $parentClasses): void
    {
        $this->parentClasses = $parentClasses;
    }

    /**
     * Return the mapped parent classes.
     *
     * @return string[] of mapped class FQNs
     */
    public function getParentClasses(): array
    {
        return $this->parentClasses;
    }

    /**
     * Return the class names or interfaces that children of this document must
     * be an instance of.
     *
     * @return string[]
     */
    public function getChildClasses(): array
    {
        return $this->childClasses;
    }

    /**
     * Set the class names or interfaces that children of this document must be
     * instance of.
     *
     * @param string[] $childClasses
     */
    public function setChildClasses(array $childClasses): void
    {
        $this->childClasses = $childClasses;
    }

    public function isLeaf(): bool
    {
        return $this->isLeaf;
    }

    public function setIsLeaf(bool $isLeaf): void
    {
        $this->isLeaf = $isLeaf;
    }

    /**
     * Checks whether the class will generate an id via the repository.
     */
    public function isIdGeneratorRepository(): bool
    {
        return self::GENERATOR_TYPE_REPOSITORY === $this->idGenerator;
    }

    /**
     * Checks whether the class uses no id generator.
     */
    public function isIdGeneratorNone(): bool
    {
        return self::GENERATOR_TYPE_NONE === $this->idGenerator;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIdentifier(): array
    {
        return [$this->identifier];
    }

    /**
     * Get identifier field names of this class.
     *
     * Since PHPCR only allows exactly one identifier field this is a proxy
     * to {@see getIdentifier()} and returns an array.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames(): array
    {
        return [$this->identifier];
    }

    public function getReflectionClass(): \ReflectionClass
    {
        return $this->reflClass;
    }

    public function isIdentifier($fieldName): bool
    {
        return $this->identifier === $fieldName;
    }

    public function hasField($fieldName): bool
    {
        if (null === $fieldName) {
            return false;
        }

        return in_array($fieldName, $this->fieldMappings, true)
            || array_key_exists($fieldName, $this->inheritedFields)
            || $this->identifier === $fieldName
            || $this->localeMapping === $fieldName
            || $this->depthMapping === $fieldName
            || $this->node === $fieldName
            || $this->nodename === $fieldName
            || $this->versionNameField === $fieldName
            || $this->versionCreatedField === $fieldName;
    }

    public function hasAssociation($fieldName): bool
    {
        return array_key_exists($fieldName, $this->mappings)
            && in_array($this->mappings[$fieldName]['type'], [self::MANY_TO_ONE, self::MANY_TO_MANY, 'referrers', 'mixedreferrers', 'children', 'child', 'parent'], true);
    }

    /**
     * @return array the association mapping with the field of this name
     *
     * @throws MappingException if the class has no mapping field with this name
     */
    public function getAssociation(string $fieldName): array
    {
        if (!$this->hasAssociation($fieldName)) {
            throw MappingException::associationNotFound($this->name, $fieldName);
        }

        return $this->mappings[$fieldName];
    }

    public function isSingleValuedAssociation($fieldName): bool
    {
        return array_key_exists($fieldName, $this->childMappings)
            || $fieldName === $this->parentMapping
            || array_key_exists($fieldName, $this->referenceMappings) && self::MANY_TO_ONE === $this->mappings[$fieldName]['type'];
    }

    public function isCollectionValuedAssociation($fieldName): bool
    {
        return array_key_exists($fieldName, $this->referenceMappings) && self::MANY_TO_MANY === $this->mappings[$fieldName]['type']
            || array_key_exists($fieldName, $this->referrersMappings)
            || array_key_exists($fieldName, $this->mixedReferrersMappings)
            || array_key_exists($fieldName, $this->childrenMappings);
    }

    public function getFieldNames(): array
    {
        $fields = $this->fieldMappings;
        if ($this->identifier) {
            $fields[] = $this->identifier;
        }
        if ($this->uuidFieldName) {
            $fields[] = $this->uuidFieldName;
        }
        if ($this->localeMapping) {
            $fields[] = $this->localeMapping;
        }
        if ($this->depthMapping) {
            $fields[] = $this->depthMapping;
        }
        if ($this->node) {
            $fields[] = $this->node;
        }
        if ($this->nodename) {
            $fields[] = $this->nodename;
        }
        if ($this->versionNameField) {
            $fields[] = $this->versionNameField;
        }
        if ($this->versionCreatedField) {
            $fields[] = $this->versionCreatedField;
        }

        return $fields;
    }

    public function getAssociationNames(): array
    {
        $associations = array_merge(
            $this->referenceMappings,
            $this->referrersMappings,
            $this->mixedReferrersMappings,
            $this->childrenMappings,
            $this->childMappings
        );
        if ($this->parentMapping) {
            $associations[] = $this->parentMapping;
        }

        return $associations;
    }

    /**
     * PHPCR-ODM uses integer codes for relation types.
     *
     * @return int|string|null
     */
    public function getTypeOfField($fieldName)
    {
        return $this->mappings[$fieldName]['type'] ?? null;
    }

    public function getAssociationTargetClass($fieldName): ?string
    {
        if (empty($this->mappings[$fieldName]['targetDocument'])) {
            throw new MappingException(sprintf(
                'Association name expected, "%s" is not an association in "%s".',
                $fieldName,
                $this->name
            ));
        }

        return $this->mappings[$fieldName]['targetDocument'];
    }

    public function getAssociationMappedByTargetField($assocName)
    {
        throw new BadMethodCallException(sprintf(
            '%s not yet implemented in "%s"',
            __METHOD__,
            $this->name
        ));
    }

    public function isAssociationInverseSide($assocName)
    {
        throw new BadMethodCallException(sprintf(
            '%s not yet implemented in "%s"',
            __METHOD__,
            $this->name
        ));
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @return string|bool class name if the field is inherited, FALSE otherwise
     */
    public function isInheritedField(string $fieldName)
    {
        return $this->inheritedFields[$fieldName] ?? false;
    }

    public function isNullable(string $fieldName): bool
    {
        return $this->getFieldMapping($fieldName)['nullable'] ?? false;
    }

    /**
     * Map a field.
     *
     * - type - The Doctrine Type of this field.
     * - fieldName - The name of the property/field on the mapped php class
     * - name - The Property key of this field in the PHPCR document
     * - id - True for an ID field.
     *
     * @param array $mapping the mapping information
     */
    public function mapField(array $mapping, self $inherited = null): void
    {
        $parentMapping = array_key_exists('fieldName', $mapping) && array_key_exists($mapping['fieldName'], $this->mappings)
            ? $this->mappings[$mapping['fieldName']]
            : null;

        if (!$inherited) {
            if (array_key_exists('id', $mapping) && true === $mapping['id']) {
                $mapping['type'] = 'string';
                $this->setIdentifier($mapping['fieldName']);
                if (array_key_exists('strategy', $mapping)) {
                    $this->setIdGenerator($mapping['strategy']);
                }
            } elseif (array_key_exists('uuid', $mapping) && true === $mapping['uuid']) {
                $mapping['type'] = 'string';
                $mapping['property'] = 'jcr:uuid';
            }

            if ($parentMapping && array_key_exists('type', $parentMapping)) {
                if (array_key_exists('type', $mapping) && $parentMapping['type'] !== $mapping['type']) {
                    throw new MappingException(sprintf(
                        'You cannot change the type of a field via inheritance in "%s"',
                        $this->name
                    ));
                }
                $mapping['type'] = $parentMapping['type'];
            }
        }

        if (array_key_exists('property', $mapping) && 'jcr:uuid' === $mapping['property']) {
            if (null !== $this->uuidFieldName) {
                throw new MappingException(sprintf(
                    'You can only designate a single "Uuid" field in "%s"',
                    $this->name
                ));
            }

            $this->uuidFieldName = $mapping['fieldName'];
        }

        if (!$inherited) {
            if ($parentMapping && array_key_exists('multivalue', $parentMapping)) {
                $mapping['multivalue'] = $parentMapping['multivalue'];
                if (array_key_exists('assoc', $parentMapping)) {
                    $mapping['assoc'] = $parentMapping['assoc'];
                }
            } elseif (!array_key_exists('multivalue', $mapping)) {
                $mapping['multivalue'] = false;
            }

            if (!array_key_exists('nullable', $mapping)) {
                $mapping['nullable'] = $parentMapping['nullable'] ?? false;
            }
        }

        // Add the field to the list of translatable fields
        if (!empty($parentMapping['translated']) || !empty($mapping['translated'])) {
            $mapping['translated'] = true;
        }

        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited);

        // Add the field to the list of translatable fields
        if (!empty($mapping['translated']) && !in_array($mapping['fieldName'], $this->translatableFields, true)) {
            $this->translatableFields[] = $mapping['fieldName'];
        }

        if (!$parentMapping) {
            $this->fieldMappings[] = $mapping['fieldName'];
        }
    }

    /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * Parts that are also NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     *
     * @return string[] the names of all the fields that should be serialized
     */
    public function __sleep(): array
    {
        // This metadata is always serialized/cached.
        $serialized = [
            'nodeType',
            'identifier',
            'name',
            'idGenerator',
            'mappings',
            'fieldMappings',
            'referenceMappings',
            'referrersMappings',
            'mixedReferrersMappings',
            'childrenMappings',
            'childMappings',
        ];

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->parentClasses) {
            $serialized[] = 'parentClasses';
        }

        if ($this->versionable) {
            $serialized[] = 'versionable';
        }

        if ($this->referenceable) {
            $serialized[] = 'referenceable';
        }

        if ($this->uniqueNodeType) {
            $serialized[] = 'uniqueNodeType';
        }

        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }

        if ($this->node) {
            $serialized[] = 'node';
        }

        if ($this->nodename) {
            $serialized[] = 'nodename';
        }

        if ($this->parentMapping) {
            $serialized[] = 'parentMapping';
        }

        if ($this->uuidFieldName) {
            $serialized[] = 'uuidFieldName';
        }

        if ($this->mixins) {
            $serialized[] = 'mixins';
        }

        if ($this->inheritMixins) {
            $serialized[] = 'inheritMixins';
        }

        if ($this->localeMapping) {
            $serialized[] = 'localeMapping';
        }

        if ($this->depthMapping) {
            $serialized[] = 'depthMapping';
        }

        if ($this->translator) {
            $serialized[] = 'translator';
        }

        if ($this->translatableFields) {
            $serialized[] = 'translatableFields';
        }

        return $serialized;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     */
    public function newInstance(): object
    {
        if (!isset($this->instantiator)) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator->instantiate($this->name);
    }

    public function setIdentifierValue(object $document, string $id): void
    {
        if (array_key_exists($this->identifier, $this->reflFields)) {
            $this->reflFields[$this->identifier]->setValue($document, $id);
        }
    }

    public function getIdentifierValue(object $document): string
    {
        if (!$this->identifier) {
            throw new PHPCRException(sprintf(
                'Class %s has no identifier field mapped. Please use $documentManager->getUnitOfWork()->getDocumentId($document) to get the id of arbitrary documents.',
                $this->name
            ));
        }

        return (string) $this->getFieldValue($document, $this->identifier);
    }

    /**
     * Get identifier values of this document.
     *
     * Since PHPCR only allows exactly one identifier field this is a proxy
     * to {@see getIdentifierValue()} and returns an array with the identifier
     * field as a key.
     *
     * If there is no identifier mapped, returns an empty array as per the
     * specification.
     *
     * @param object $document
     */
    public function getIdentifierValues($document): array
    {
        try {
            return [$this->identifier => $this->getIdentifierValue($document)];
        } catch (PHPCRException $e) {
            return [];
        }
    }

    /**
     * Sets the specified field to the specified value on the given document.
     */
    public function setFieldValue(object $document, string $field, $value): void
    {
        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @return mixed|null the value of this field for the document or null if
     *                    not found
     */
    public function getFieldValue(object $document, string $field)
    {
        if (array_key_exists($field, $this->reflFields) && $this->reflFields[$field]->isInitialized($document)) {
            return $this->reflFields[$field]->getValue($document);
        }

        return null;
    }

    /**
     * Gets the mapping of a (regular) field that holds some data but not a
     * reference to another object.
     *
     * @throws MappingException
     */
    public function getFieldMapping(string $fieldName): array
    {
        if (!$this->hasField($fieldName)) {
            throw MappingException::fieldNotFound($this->name, $fieldName);
        }

        return $this->mappings[$fieldName];
    }

    /**
     * Dispatches the lifecycle event of the given document to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param mixed[]|null $arguments the arguments to pass to the callback
     */
    public function invokeLifecycleCallbacks(string $lifecycleEvent, object $document, array $arguments = null): void
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            if (null !== $arguments) {
                call_user_func_array([$document, $callback], $arguments);
            } else {
                $document->$callback();
            }
        }
    }

    public function getUuidFieldName(): ?string
    {
        return $this->uuidFieldName;
    }

    /**
     * Whether $fieldName is the name of the property holding the universally unique identifier of the document.
     */
    public function isUuid(string $fieldName): bool
    {
        return $this->uuidFieldName === $fieldName;
    }

    public function fullyQualifiedClassName(?string $className): ?string
    {
        if (null !== $className && false === strpos($className, '\\') && '' !== $this->namespace) {
            return $this->namespace.'\\'.$className;
        }

        return $className;
    }
}

interface_exists(ReflectionService::class);
