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

use ReflectionProperty;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;

/**
 * Metadata class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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
    const TO_ONE = 5;
    const TO_MANY = 10;
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_ONE = 4;
    const MANY_TO_MANY = 8;

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
     * The ReflectionProperty instances of the mapped class.
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
     * @var AbstractIdGenerator
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
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: The field mappings of the class.
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
    public $fieldMappings = array();

    /**
     * READ-ONLY: Array of fields to also load with a given method.
     *
     * @var array
     */
    public $alsoLoadMethods = array();

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
     * @var array
     */
    public $associationsMappings = array();

    /**
     * Mapping of child doucments that are child nodes in the repository
     */
    public $childMappings = array();

    /**
     * Mapping of children: access child nodes through a collection
     */
    public $childrenMappings = array();

    /**
     * Mapping of referrers: access referrer nodes through a collection
     */
    public $referrersMappings = array();

    /**
     * Mapping of locale (actual locale)
     */
    public $localeMapping;

    /**
     * Name of the version name property of this document
     * @var string
     */
    public $versionNameField;

    /**
     * Name of the version created property of this document
     * @var string
     */
    public $versionCreatedField;

    /**
     * List of translatable fields
     * @var array
     */
    public $translatableFields = array();

    /**
     * Whether this document should be versioned. If this is not false, it will
     * be one of the values from self::validVersionableAnnotations
     *
     * @var bool|string
     */
    public $versionable = false;

    /**
     * determines if the document is referenceable or not
     *
     * @var bool
     */
    public $referenceable = false;

    /**
     * Strategy key to find field translations.
     * This is the key used for DocumentManager::getTranslationStrategy
     * @var string
     */
    public $translator;

    /**
     * READ-ONLY: The Id generator options.
     *
     * @var array
     */
    public $generatorOptions = array();

    /**
     * @var array
     */
    private $inheritedFields = array();

    /**
     * @var array
     */
    private $declaredFields = array();

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
        foreach ($this->fieldMappings as $field => $mapping) {
            if (isset($mapping['declared'])) {
                $reflField = new ReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
        foreach ($this->fieldMappings as $field => $mapping) {
            if (isset($mapping['declared'])) {
                $reflField = new ReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
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
        $this->identifier = $identifier;
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClassName($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
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
     * @param bool $versionable
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
     * Registers a custom repository class for the document class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
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

    public function mapId(array $mapping)
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

        $this->validateAndCompleteFieldMapping($mapping, false);
    }

    public function setFieldInherited($fieldName, $className)
    {
        $this->inheritedFields[$fieldName] = $className;
    }

    public function setFieldDeclared($fieldName, $className)
    {
        $this->declaredFields[$fieldName] = $className;
    }

    public function mapNode(array $mapping)
    {
        $this->validateAndCompleteFieldMapping($mapping, false);
        $this->node = $mapping['fieldName'];
    }

    public function mapNodename(array $mapping)
    {
        $this->validateAndCompleteFieldMapping($mapping, false);
        $this->nodename = $mapping['fieldName'];
        if (null !== $this->parentMapping && !$this->idStrategySet) {
            $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);
        }
    }

    public function mapParentDocument(array $mapping)
    {
        $this->validateAndCompleteFieldMapping($mapping, false);
        $this->parentMapping = $mapping['fieldName'];
        if (null !== $this->nodename && !$this->idStrategySet) {
            $this->setIdGenerator(self::GENERATOR_TYPE_PARENT);
        }
    }

    public function mapChild(array $mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        if (!isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }
        $this->childMappings[$mapping['fieldName']] = $mapping;
    }

    public function mapChildren(array $mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        $mapping['name'] = $mapping['fieldName'];
        $this->childrenMappings[$mapping['fieldName']] = $mapping;
    }

    public function mapReferrers(array $mapping)
    {
        $mapping = $this->validateAndCompleteReferrersMapping($mapping, false);
        $mapping['name'] = $mapping['fieldName'];
        $this->referrersMappings[$mapping['fieldName']] = $mapping;
    }

    public function mapLocale(array $mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        $this->localeMapping = $mapping['fieldName'];
    }

    public function mapVersionName(array $mapping)
    {
        if (!$this->versionable) {
            throw new \InvalidArgumentException(sprintf("You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)", $this->name, $mapping['fieldName']));
        }

        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        $this->versionNameField = $mapping['fieldName'];
    }

    public function mapVersionCreated(array $mapping)
    {
        if (!$this->versionable) {
            throw new \InvalidArgumentException(sprintf("You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)", $this->name, $mapping['fieldName']));
        }

        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        $this->versionCreatedField = $mapping['fieldName'];
    }

    public function mapLifecycleCallbacks(array $mapping)
    {
        $this->setLifecycleCallbacks($mapping);
    }

    protected function validateAndCompleteReferrersMapping($mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);
        if (!(array_key_exists('referenceType', $mapping) && in_array($mapping['referenceType'], array(null, "weak", "hard")))) {
            throw new MappingException("You have to specify a 'referenceType' for the '" . $this->name . "' association which must be null, 'weak' or 'hard': ".$mapping['referenceType']);
        }
        return $mapping;
    }

    protected function validateAndCompleteFieldMapping($mapping, $isField = true)
    {
        if (!isset($mapping['fieldName'])) {
            throw new MappingException("Mapping a property requires to specify the fieldName.");
        }

        if ($isField && !isset($mapping['name'])) {
            $mapping['name'] = $mapping['fieldName'];
        }

        if (isset($this->fieldMappings[$mapping['fieldName']])
            || ($this->node == $mapping['fieldName'])
            || ($this->nodename == $mapping['fieldName'])
            || ($this->parentMapping == $mapping['fieldName'])
            || ($this->versionNameField == $mapping['fieldName'])
            || ($this->versionCreatedField == $mapping['fieldName'])
            || isset($this->associationsMappings[$mapping['fieldName']])
            || isset($this->childMappings[$mapping['fieldName']])
            || isset($this->childrenMappings[$mapping['fieldName']])
            || isset($this->referrersMappings[$mapping['fieldName']])
        ) {
            if (!$isField
                || empty($mapping['type'])
                || empty($this->fieldMappings[$mapping['fieldName']])
                || $this->fieldMappings[$mapping['fieldName']]['type'] !== $mapping['type']
            ) {
                throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
            }
        }

        if ($isField && !isset($mapping['type'])) {
            throw MappingException::missingTypeDefinition($this->name, $mapping['fieldName']);
        }

        $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $reflProp;

        return $mapping;
    }

    protected function validateAndCompleteAssociationMapping($mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, false);

        $mapping['sourceDocument'] = $this->name;
        if (isset($mapping['targetDocument']) && strpos($mapping['targetDocument'], '\\') === false && strlen($this->namespace)) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
        }
        if (isset($mapping['strategy']) && !in_array($mapping['strategy'], array(null, 'weak', 'hard', 'path'))) {
            throw new MappingException("The attribute 'strategy' for the '" . $this->name . "' association has to be either a null, 'weak', 'hard' or 'path': ".$mapping['strategy']);
        }
        return $mapping;
    }

    public function mapManyToOne($mapping)
    {
        $mapping = $this->validateAndCompleteAssociationMapping($mapping);
        $mapping['type'] = self::MANY_TO_ONE;

        $this->storeAssociationMapping($mapping);
    }

    public function mapManyToMany($mapping)
    {
        $mapping = $this->validateAndCompleteAssociationMapping($mapping);
        $mapping['type'] = self::MANY_TO_MANY;

        $this->storeAssociationMapping($mapping);
    }

    public function storeAssociationMapping($mapping)
    {
        if (empty($mapping['strategy'])) {
            $mapping['strategy'] = 'weak';
        }
        $this->associationsMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * Gets the mapping of a field.
     *
     * @param string $fieldName  The field name.
     * @return array  The field mapping.
     */
    public function getFieldMapping($fieldName)
    {
        if (!isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param string $generator
     */
    public function setIdGenerator($generator)
    {
        if (is_string($generator)) {
            $generator = constant('Doctrine\ODM\PHPCR\Mapping\ClassMetadata::GENERATOR_TYPE_' . strtoupper($generator));
        }
        $this->idGenerator = $generator;
    }

    /**
     * Sets the Id generator options.
     */
    public function setIdGeneratorOptions($generatorOptions)
    {
        $this->generatorOptions = $generatorOptions;
    }

    /**
     * Sets the translator strategy key
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
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
        return $this->identifier;
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
        return $this->identifier === $fieldName ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) && true === $this->fieldMappings[$fieldName]['multivalue'];
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames()
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) ?
            $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'  not yet implemented');
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @return boolean string clas naem if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->inheritedFields[$fieldName]) ? $this->inheritedFields[$fieldName] : false;
    }

    /**
     * Get all the inherited fields
     *
     * @return array all inherited field
     */
    public function getInheritedFields()
    {
        return $this->inheritedFields;
    }

    /**
     * Checks whether a mapped field is declared previously.
     *
     * @return boolean string class name if the field is declared, FALSE otherwise.
     */
    public function isDeclaredField($fieldName)
    {
        return isset($this->declaredFields[$fieldName]) ? $this->declaredFields[$fieldName] : false;
    }

    /**
     * Get all the declared fields
     *
     * @return array all declared field
     */
    public function getDeclaredFields()
    {
        return $this->declaredFields;
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
    public function mapField(array $mapping)
    {
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

        $mapping = $this->validateAndCompleteFieldMapping($mapping);

        if (!isset($mapping['multivalue'])) {
            $mapping['multivalue'] = false;
        }

        if ($mapping['type'] === 'int') {
            $mapping['type'] = 'long';
        }

        // Add the field to the list of translatable fields
        if (isset($mapping['translated']) && $mapping['translated']) {
            if (! array_key_exists($mapping['name'], $this->translatableFields)) {
                $this->translatableFields[] = $mapping['name'];
            }
        }

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
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
            'fieldMappings',
            'identifier',
            'name',
//            'collection',
//            'generatorType',
            'generatorOptions',
            'idGenerator'
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
     * @param mixed $id
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
     * @param mixed $value
     */
    public function setFieldValue($document, $field, $value)
    {
        $this->reflFields[$field]->setValue($document, $value);
    }

    /**
     * Gets the specified field's value off the given document.
     *
     * @param object $document
     * @param string $field
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
     * @param string $event The lifecycle event.
     * @param Document $document The Document on which the event occured.
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
}
