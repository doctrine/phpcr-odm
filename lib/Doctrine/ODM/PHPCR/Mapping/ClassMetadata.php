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

namespace Doctrine\ODM\PHPCR\Mapping;

use ReflectionProperty;
use InvalidArgumentException;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\ClassLoader;

/**
 * Metadata class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      David Buchmann <david@liip.ch>
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class ClassMetadata implements ClassMetadataInterface
{
    const MANY_TO_ONE = 4;
    const MANY_TO_MANY = 8;

    const CASCADE_PERSIST = 1;
    const CASCADE_REMOVE  = 2;
    const CASCADE_MERGE   = 4;
    const CASCADE_DETACH  = 8;
    const CASCADE_REFRESH = 16;
    const CASCADE_ALL     = 31;

    /**
     * means no strategy has been set so far.
     */
    const GENERATOR_TYPE_NONE = 0;

    /**
     * means the repository will need to be able to generate the id.
     */
    const GENERATOR_TYPE_REPOSITORY = 1;

    /**
     * NONE means Doctrine will not generate any id for us and you are responsible for manually
     * assigning an id.
     */
    const GENERATOR_TYPE_ASSIGNED = 2;

    /**
     * means the document uses the parent and name mapping to find its place.
     */
    const GENERATOR_TYPE_PARENT = 3;

    protected static $validVersionableAnnotations = array('simple', 'full');

    /**
     * READ-ONLY: The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    public $reflFields = array();

    /**
     * The prototype from which new instances of the mapped class are created.
     *
     * @var object
     */
    private $prototype;

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var \Doctrine\ODM\PHPCR\Id\IdGenerator
     */
    public $idGenerator = self::GENERATOR_TYPE_ASSIGNED;

    /**
     * keep track whether an id strategy was explicitly set
     *
     * @var boolean
     */
    private $idStrategySet = false;

    /**
     * READ-ONLY: The field name of the document identifier.
     */
    public $identifier;

    /**
     * READ-ONLY: The field name of the UUID field
     */
    public $uuidFieldName;

    /**
     * READ-ONLY: The name of the document class that is stored in the phpcr:class property
     */
    public $name;

    /**
     * READ-ONLY: The namespace the document class is contained in.
     *
     * @var string
     * @todo Not really needed. Usage could be localized.
     */
    public $namespace;

    /**
     * READ-ONLY: The JCR Nodetype to be used for this node
     *
     * @var string
     */
    public $nodeType;

    /**
     * READ-ONLY: The field name of the node
     *
     * @var string
     */
    public $node;

    /**
     * READ-ONLY except on document creation: The name of the node
     *
     * @var string
     */
    public $nodename;

    /**
     * READ-ONLY except on document creation: The name of the node
     *
     * @var string
     */
    public $parentMapping;

    /**
     * READ-ONLY: The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: The field mappings of the class.
     *
     * @var array
     */
    public $fieldMappings = array();

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
     * Marks the field as the primary key of the document. Multiple fields of an
     * document can have the id attribute, forming a composite key.
     *
     * @var array
     */
    public $mappings = array();

    /**
     * READ-ONLY: The registered lifecycle callbacks for documents of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public $isMappedSuperclass = false;

    /**
     * READ-ONLY: The reference mappings of the class.
     *
     * @var array
     */
    public $referenceMappings = array();

    /**
     * READ-ONLY: The child mappings of the class.
     *
     * @var array
     */
    public $childMappings = array();

    /**
     * READ-ONLY: The children mappings of the class.
     *
     * @var array
     */
    public $childrenMappings = array();

    /**
     * READ-ONLY: The referrers mappings of the class.
     *
     * @var array
     */
    public $referrersMappings = array();

    /**
     * READ-ONLY: Name of the locale property
     *
     * @var string
     */
    public $localeMapping;

    /**
     * READ-ONLY: Name of the version name property of this document
     *
     * @var string
     */
    public $versionNameField;

    /**
     * READ-ONLY: Name of the version created property of this document
     *
     * @var string
     */
    public $versionCreatedField;

    /**
     * READ-ONLY: List of translatable fields
     *
     * @var array
     */
    public $translatableFields = array();

    /**
     * READ-ONLY: Whether this document should be versioned. If this is not false, it will
     * be one of the values from self::validVersionableAnnotations
     *
     * @var bool|string
     */
    public $versionable = false;

    /**
     * READ-ONLY: determines if the document is referenceable or not
     *
     * @var bool
     */
    public $referenceable = false;

    /**
     * READ-ONLY: Strategy key to find field translations.
     * This is the key used for DocumentManager::getTranslationStrategy
     *
     * @var string
     */
    public $translator;

    /**
     * READ-ONLY: Mapped parent classes.
     *
     * @var array
     */
    public $parentClasses = array();

    /**
     * The inherited fields of this class
     *
     * @var array
     */
    private $inheritedFields = array();

    /**
     * Initializes a new ClassMetadata instance that will hold the object-document mapping
     * metadata of the class with the given name.
     *
     * @param string $className The name of the document class the new instance is used for.
     */
    public function __construct($className)
    {
        $this->name = $className;
    }

    /**
     * Initializes a new ClassMetadata instance that will hold the
     * object-relational mapping metadata of the class with the given name.
     *
     * @param ReflectionService $reflService
     */
    public function initializeReflection(ReflectionService $reflService)
    {
        $this->reflClass = $reflService->getClass($this->name);
        $this->namespace = $reflService->getClassNamespace($this->name);
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @param ReflectionService $reflService
     */
    public function wakeupReflection(ReflectionService $reflService)
    {
        $this->reflClass = $reflService->getClass($this->name);
        $this->namespace = $reflService->getClassNamespace($this->name);
        foreach ($this->getFieldNames() as $fieldName) {
            $reflField = isset($this->mappings[$fieldName]['declared'])
                ? new ReflectionProperty($this->mappings[$fieldName]['declared'], $fieldName)
                : $this->reflClass->getProperty($fieldName)
            ;
            $reflField->setAccessible(true);
            $this->reflFields[$fieldName] = $reflField;
        }
    }

    /**
     * Validate Identifier
     *
     * @throws MappingException if no identifiers are mapped
     */
    public function validateIdentifier()
    {
        // Verify & complete identifier mapping
        if (! $this->isMappedSuperclass) {
            if (! $this->identifier
                && !($this->parentMapping && $this->nodename)
            ) {
                throw MappingException::identifierRequired($this->name);
            }
        }
    }

    /**
     * Validate association targets actually exist.
     *
     * @throws MappingException if there is an invalid reference mapping
     */
    public function validateReferences()
    {
        foreach ($this->referenceMappings as $fieldName) {
            $mapping = $this->mappings[$fieldName];
            if (!empty($mapping['targetDocument']) && !ClassLoader::classExists($mapping['targetDocument']) ) {
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
    public function validateTranslatables()
    {
        if (count($this->translatableFields) > 0) {
            if (null === $this->translator) {
                throw MappingException::noTranslatorStrategy($this->name, $this->translatableFields);
            }
        }
    }

    /**
     * Validate lifecycle callbacks
     *
     * @param ReflectionService $reflService
     *
     * @throws MappingException if a declared callback does not exist
     */
    public function validateLifecycleCallbacks(ReflectionService $reflService)
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
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        if ($this->identifier &&  $this->identifier !== $identifier) {
            throw new MappingException('Cannot map the identifier to more than one property');
        }

        $this->identifier = $identifier;
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $repositoryClassName The class name of the custom repository.
     */
    public function setCustomRepositoryClassName($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     *
     * @return boolean
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     *
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->lifecycleCallbacks[$event]) ? $this->lifecycleCallbacks[$event] : array();
    }

    /**
     * Adds a lifecycle callback for documents of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     *
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
    {
        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for documents of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * @param string $versionable A valid versionable annotation.
     */
    public function setVersioned($versionable)
    {
        if (!in_array($versionable, self::$validVersionableAnnotations)) {
            throw new \InvalidArgumentException("Invalid value in '{$this->name}' for the versionable annotation: '{$versionable}'");
        }
        $this->versionable = $versionable;
    }

    /**
     * @param bool $referenceable
     */
    public function setReferenceable($referenceable)
    {
        $this->referenceable = $referenceable;
    }

    /**
     * @param string $nodeType
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;
    }

    /**
     * Return the JCR node type to be used for this node.
     *
     * @return string
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return array An array of \ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     *
     * @return \ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
    }

    /**
     * The namespace this Document class belongs to.
     *
     * @return string $namespace The namespace name.
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    public function mapId(array $mapping, ClassMetadata $inherited = null)
    {
        if (isset($mapping['id']) && $mapping['id'] === true) {
            $mapping['type'] = 'string';
            $this->setIdentifier($mapping['fieldName']);
            if (isset($mapping['strategy'])) {
                $this->setIdGenerator($mapping['strategy']);
                $this->idStrategySet = true;
            } elseif (null !== $this->parentMapping && null !== $this->nodename) {
                $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);
            }
        }

        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
    }

    public function mapNode(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'node';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->node = $mapping['fieldName'];
    }

    public function mapNodename(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'nodename';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->nodename = $mapping['fieldName'];
        if (null !== $this->parentMapping && !$this->idStrategySet) {
            $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);
        }
    }

    public function mapParentDocument(array $mapping, ClassMetadata $inherited = null)
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }
        $mapping['type'] = 'parent';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->parentMapping = $mapping['fieldName'];
        if (null !== $this->nodename && !$this->idStrategySet) {
            $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);
        }
    }

    public function mapChild(array $mapping, ClassMetadata $inherited = null)
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }
        $mapping['type'] = 'child';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->childMappings[] = $mapping['fieldName'];
    }

    public function mapChildren(array $mapping, ClassMetadata $inherited = null)
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }
        $mapping['type'] = 'children';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->childrenMappings[] = $mapping['fieldName'];
    }

    public function mapReferrers(array $mapping, ClassMetadata $inherited = null)
    {
        if (!(array_key_exists('referenceType', $mapping) && in_array($mapping['referenceType'], array(null, "weak", "hard")))) {
            throw new MappingException("You have to specify a 'referenceType' for the '" . $this->name . "' association which must be null, 'weak' or 'hard': ".$mapping['referenceType']);
        }

        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }
        $mapping['type'] = 'referrers';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->referrersMappings[] = $mapping['fieldName'];
    }

    public function mapLocale(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'locale';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->localeMapping = $mapping['fieldName'];
    }

    public function mapVersionName(array $mapping, ClassMetadata $inherited = null)
    {
        if (!$this->versionable) {
            throw new \InvalidArgumentException(sprintf("You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)", $this->name, $mapping['fieldName']));
        }

        $mapping['type'] = 'versionname';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->versionNameField = $mapping['fieldName'];
    }

    public function mapVersionCreated(array $mapping, ClassMetadata $inherited = null)
    {
        if (!$this->versionable) {
            throw new \InvalidArgumentException(sprintf("You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)", $this->name, $mapping['fieldName']));
        }

        $mapping['type'] = 'versioncreated';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->versionCreatedField = $mapping['fieldName'];
    }

    public function mapLifecycleCallbacks(array $mapping)
    {
        $this->setLifecycleCallbacks($mapping);
    }

    protected function validateAndCompleteFieldMapping($mapping, ClassMetadata $inherited = null, $isField = true)
    {
        if ($inherited) {
            if (!isset($mapping['inherited']) && !$inherited->isMappedSuperclass) {
                $this->inheritedFields[$mapping['fieldName']] = $inherited->name;
            }
            if (!isset($mapping['declared'])) {
                $mapping['declared'] = $inherited->name;
            }
            $this->reflFields[$mapping['fieldName']] = $inherited->getReflectionProperty($mapping['fieldName']);
            $this->mappings[$mapping['fieldName']] = $mapping;

            return $mapping;
        }

        if (empty($mapping['fieldName'])) {
            throw new MappingException("Mapping a property requires to specify the fieldName in '{$this->name}'.");
        }

        if (!is_string($mapping['fieldName'])) {
            throw new MappingException("fieldName must be of type string in '{$this->name}'.");
        }

        if (!isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }

        if ($isField && isset($mapping['assoc'])) {
            $mapping['multivalue'] = true;
            if (empty($mapping['assoc'])) {
                $mapping['assoc'] = $mapping['name'].'Keys';
            }
        }

        if (isset($this->mappings[$mapping['fieldName']])) {
            if (!$isField
                || empty($mapping['type'])
                || empty($this->mappings[$mapping['fieldName']])
                || $this->mappings[$mapping['fieldName']]['type'] !== $mapping['type']
            ) {
                throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
            }
        }

        if (!isset($mapping['type'])) {
            throw MappingException::missingTypeDefinition($this->name, $mapping['fieldName']);
        }

        if ($mapping['type'] === 'int') {
            $mapping['type'] = 'long';
        } elseif ($mapping['type'] === 'float') {
            $mapping['type'] = 'double';
        }

        $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $reflProp;
        $this->mappings[$mapping['fieldName']] = $mapping;

        return $mapping;
    }

    protected function validateAndCompleteAssociationMapping($mapping, ClassMetadata $inherited = null)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        if ($inherited) {
            return $mapping;
        }

        $mapping['sourceDocument'] = $this->name;
        if (isset($mapping['targetDocument']) && strpos($mapping['targetDocument'], '\\') === false && strlen($this->namespace)) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
        }
        if (empty($mapping['strategy'])) {
            $mapping['strategy'] = 'weak';
        } elseif (!in_array($mapping['strategy'], array(null, 'weak', 'hard', 'path'))) {
            throw new MappingException("The attribute 'strategy' for the '" . $this->name . "' association has to be either a null, 'weak', 'hard' or 'path': ".$mapping['strategy']);
        }
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }
        $this->mappings[$mapping['fieldName']] = $mapping;

        return $mapping;
    }

    public function validateClassMapping()
    {
        $assocFields = array();
        foreach ($this->fieldMappings as $fieldName) {
            $mapping = $this->mappings[$fieldName];
            if (empty($mapping['assoc'])) {
                continue;
            }

            if (isset($this->mappings[$mapping['assoc']])) {
                throw MappingException::assocOverlappingFieldDefinition($this->name, $fieldName, $mapping['assoc']);
            }

            if (!empty($assocFields[$mapping['assoc']])) {
                throw MappingException::assocOverlappingAssocDefinition($this->name, $fieldName, $assocFields[$mapping['assoc']]);
            }

            $assocFields[$mapping['assoc']] = $fieldName;
        }

        if (count($this->translatableFields)) {
            if (!isset($this->localeMapping)) {
                throw new MappingException("You must define a locale mapping for translatable document '".$this->name."'");
            }
        }
    }

    public function mapManyToOne($mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = self::MANY_TO_ONE;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[] = $mapping['fieldName'];
    }

    public function mapManyToMany($mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = self::MANY_TO_MANY;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[] = $mapping['fieldName'];
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param string $generator
     */
    protected function setIdGenerator($generator)
    {
        if (is_string($generator)) {
            $generator = constant('Doctrine\ODM\PHPCR\Mapping\ClassMetadata::GENERATOR_TYPE_' . strtoupper($generator));
        }
        $this->idGenerator = $generator;
    }

    /**
     * Sets the translator strategy key
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the mapped parent classes
     *
     * @param array $parentClasses
     */
    public function setParentClasses($parentClasses)
    {
        $this->parentClasses = $parentClasses;
    }

    /**
     * Return the mapped parent classes
     *
     * @return array of mapped class FQNs
     */
    public function getParentClasses()
    {
        return $this->parentClasses;
    }

    /**
     * Checks whether the class will generate an id via the repository.
     *
     * @return boolean TRUE if the class uses the Repository generator, FALSE otherwise.
     */
    public function isIdGeneratorRepository()
    {
        return $this->idGenerator == self::GENERATOR_TYPE_REPOSITORY;
    }

    /**
     * Checks whether the class uses no id generator.
     *
     * @return boolean TRUE if the class does not use any id generator, FALSE otherwise.
     */
    public function isIdGeneratorNone()
    {
        return $this->idGenerator == self::GENERATOR_TYPE_NONE;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return array($this->identifier);
    }

    /**
     * Get identifier field names of this class.
     *
     * Since PHPCR only allows exactly one identifier field this is a proxy
     * to {@see getIdentifier()} and returns an array.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
        return array($this->identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function getReflectionClass()
    {
        return $this->reflClass;
    }

    /**
     * {@inheritDoc}
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifier === $fieldName;
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        return in_array($fieldName, $this->fieldMappings)
            || $this->localeMapping === $fieldName
            || $this->node === $fieldName
            || $this->nodename === $fieldName
            || $this->versionNameField === $fieldName
            || $this->versionCreatedField === $fieldName
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getField($fieldName)
    {
        if ($this->hasField($fieldName)) {
            throw MappingException::fieldNotFound($this->name, $fieldName);
        }

        return $this->mappings[$fieldName];
    }

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->mappings[$fieldName])
            && in_array($this->mappings[$fieldName]['type'], array(self::MANY_TO_ONE, self::MANY_TO_MANY, 'referrers', 'children', 'child', 'parent'))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociation($fieldName)
    {
        if (! $this->hasAssociation($fieldName)) {
            throw MappingException::associationNotFound($this->name, $fieldName);
        }

        return $this->mappings[$fieldName];
    }

    /**
     * {@inheritDoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return isset($this->childMappings[$fieldName])
            || $fieldName === $this->parentMapping
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->referenceMappings[$fieldName])
            || isset($this->referrersMappings[$fieldName])
            || isset($this->childrenMappings[$fieldName])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        $fields = $this->fieldMappings;
        if ($this->localeMapping) {
            $fields[] = $this->localeMapping;
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

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames()
    {
        $associations = array_merge(
            $this->referenceMappings,
            $this->referrersMappings,
            $this->childrenMappings,
            $this->childMappings
        );
        if ($this->parentMapping) {
            $associations[] = $this->parentMapping;
        }

        return $associations;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->mappings[$fieldName]) ?
            $this->mappings[$fieldName]['type'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($fieldName)
    {
        if (!in_array($fieldName, $this->referenceMappings)) {
            throw new InvalidArgumentException("Association name expected, '$fieldName' is not an association in '{$this->name}'.");
        }

        return $this->mappings[$fieldName]['targetDocument'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        throw new \BadMethodCallException(__METHOD__."  not yet implemented in '{$this->name}'");
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        throw new \BadMethodCallException(__METHOD__."  not yet implemented in '{$this->name}'");
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @return boolean string class name if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->inheritedFields[$fieldName]) ? $this->inheritedFields[$fieldName] : false;
    }

    /**
     * Map a field.
     *
     * - type - The Doctrine Type of this field.
     * - fieldName - The name of the property/field on the mapped php class
     * - name - The Property key of this field in the PHPCR document
     * - id - True for an ID field.
     *
     * @param array $mapping The mapping information.
     */
    public function mapField(array $mapping, ClassMetadata $inherited = null)
    {
        $parentMapping = isset($mapping['fieldName']) && isset($this->mappings[$mapping['fieldName']])
            ? $this->mappings[$mapping['fieldName']] : null;

        if (!$inherited) {
            if (isset($mapping['id']) && $mapping['id'] === true) {
                $mapping['type'] = 'string';
                $this->setIdentifier($mapping['fieldName']);
                if (isset($mapping['strategy'])) {
                    $this->setIdGenerator($mapping['strategy']);
                }
            } elseif (isset($mapping['uuid']) && $mapping['uuid'] === true) {
                $mapping['type'] = 'string';
                $mapping['name'] = 'jcr:uuid';
            }

            if (isset($parentMapping['type'])) {
                if (isset($mapping['type']) && $parentMapping['type'] !== $mapping['type']) {
                    throw new MappingException("You cannot change the type of a field via inheritance in '{$this->name}'");
                }
                $mapping['type'] = $parentMapping['type'];
            }
        }

        if (isset($mapping['name']) && $mapping['name'] == 'jcr:uuid') {
            if (null !== $this->uuidFieldName) {
                throw new MappingException("You can only designate a single 'Uuid' field in '{$this->name}'");
            }

            $this->uuidFieldName = $mapping['fieldName'];
        }

        if (!$inherited) {
            if (isset($parentMapping['multivalue'])) {
                $mapping['multivalue'] = $parentMapping['multivalue'];
                if (isset($parentMapping['assoc'])) {
                    $mapping['assoc'] = $parentMapping['assoc'];
                }
            } elseif (!isset($mapping['multivalue'])) {
                $mapping['multivalue'] = false;
            }
        }

        // Add the field to the list of translatable fields
        if (!empty($parentMapping['translated']) || !empty($mapping['translated'])) {
            $mapping['translated'] = true;
        }

        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited);

        // Add the field to the list of translatable fields
        if (!empty($mapping['translated']) && !in_array($mapping['name'], $this->translatableFields)) {
            $this->translatableFields[] = $mapping['name'];
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
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = array(
            'identifier',
            'name',
            'idGenerator',
            'mappings',
            'fieldMappings',
            'referenceMappings',
            'referrersMappings',
            'childrenMappings',
            'childMappings',
        );

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->versionable) {
            $serialized[] = 'versionable';
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

        return $serialized;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }

        return clone $this->prototype;
    }

    /**
     * Sets the document identifier of a document.
     *
     * @param object $document
     * @param mixed  $id
     */
    public function setIdentifierValue($document, $id)
    {
        if (isset($this->reflFields[$this->identifier])) {
            $this->reflFields[$this->identifier]->setValue($document, $id);
        }
    }

    /**
     * Gets the document identifier.
     *
     * @param object $document
     *
     * @return string $id
     */
    public function getIdentifierValue($document)
    {
        return (string) $this->getFieldValue($document, $this->identifier);
    }

    /**
     * Get identifier values of this document.
     *
     * Since PHPCR only allows exactly one identifier field this is a proxy
     * to {@see getIdentifierValue()} and returns an array with the identifier
     * field as a key.
     *
     * @param object $document
     *
     * @return array
     */
    public function getIdentifierValues($document)
    {
        return array($this->identifier => $this->getIdentifierValue($document));
    }

    /**
     * Sets the specified field to the specified value on the given document.
     *
     * @param object $document
     * @param string $field
     * @param mixed  $value
     */
    public function setFieldValue($document, $field, $value)
    {
        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @param object $document the document to get the field from
     * @param string $field    the name of the field
     *
     * @return mixed|null the value of this field for the document or null if
     *      not found
     */
    public function getFieldValue($document, $field)
    {
        if (isset($this->reflFields[$field])) {
            return $this->reflFields[$field]->getValue($document);
        }

        return null;
    }

    /**
     * Dispatches the lifecycle event of the given document to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $lifecycleEvent The lifecycle event.
     * @param object $document       The Document on which the event occurred.
     * @param array  $arguments      the arguments to pass to the callback
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $document, array $arguments = null)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            if ($arguments !== null) {
                call_user_func_array(array($document, $callback), $arguments);
            } else {
                $document->$callback();
            }
        }
    }

    public function getUuidFieldName()
    {
        return $this->uuidFieldName;
    }
}
