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

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\PHPCRException;
use PHPCR\RepositoryException;
use PHPCR\Util\PathHelper;
use ReflectionProperty;
use ReflectionClass;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\ClassLoader;
use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;

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
    const CASCADE_REMOVE = 2;
    const CASCADE_MERGE = 4;
    const CASCADE_DETACH = 8;
    const CASCADE_REFRESH = 16;
    const CASCADE_TRANSLATION = 32;
    const CASCADE_ALL = 255;

    /**
     * No strategy has been set so far.
     */
    const GENERATOR_TYPE_NONE = 0;

    /**
     * The repository will be asked to generate the id.
     */
    const GENERATOR_TYPE_REPOSITORY = 1;

    /**
     * Doctrine will not generate any id for us and you are responsible for
     * manually assigning a valid id string in the document.
     *
     * Be aware that in PHPCR, the parent of a node must exist.
     */
    const GENERATOR_TYPE_ASSIGNED = 2;

    /**
     * The document uses the parent and name mapping to find its location in
     * the tree.
     */
    const GENERATOR_TYPE_PARENT = 3;

    /**
     * The document uses the parent mapping to find its location in the tree
     * and will use the PHPCR addNodeAutoNamed feature for the node name.
     */
    const GENERATOR_TYPE_AUTO = 4;

    protected static $validVersionableAnnotations = array('simple', 'full');

    /**
     * READ-ONLY: The ReflectionProperty instances of the mapped class.
     *
     * @var ReflectionProperty[]
     */
    public $reflFields = array();

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var int constant for the id generator to use for this class
     */
    public $idGenerator = self::GENERATOR_TYPE_NONE;

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
    public $nodeType = 'nt:unstructured';

    /**
     * READ-ONLY: The JCR Mixins to be used for this node
     *
     * @var array
     */
    public $mixins = array();

    /**
     * READ-ONLY: The field name of the node
     *
     * @var string
     */
    public $node;

    /**
     * READ-ONLY except on document creation: The field name for the name of the node.
     *
     * @var string
     */
    public $nodename;

    /**
     * READ-ONLY except on document creation: The field name for the parent document.
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
     * Marks the field as the primary key of the document.
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
     * READ-ONLY: The mixed referrers (read only) mappings of the class.
     *
     * @var array
     */
    public $mixedReferrersMappings = array();

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
     * @var InstantiatorInterface
     */
    private $instantiator;

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
        $fieldNames = array_merge($this->getFieldNames(), $this->getAssociationNames());
        foreach ($fieldNames as $fieldName) {
            $reflField = isset($this->mappings[$fieldName]['declared'])
                ? new ReflectionProperty($this->mappings[$fieldName]['declared'], $fieldName)
                : $this->reflClass->getProperty($fieldName)
            ;
            $reflField->setAccessible(true);
            $this->reflFields[$fieldName] = $reflField;
        }
    }

    /**
     * Check if this node name is valid. Returns null if valid, an exception otherwise.
     *
     * @param string $nodeName The node local name
     *
     * @return RepositoryException|null
     */
    public function isValidNodename($nodeName)
    {
        try {
            $parts = explode(':', $nodeName);
            if (1 > count($parts) || 2 < count($parts)) {
                return new RepositoryException("Name contains illegal characters: $nodeName");
            }
            if (0 === strlen($parts[0])) {
                return new RepositoryException("Name may not be empty: $nodeName");
            }
            PathHelper::assertValidLocalName($parts[0]);
            if (2 == count($parts)) {
                // [0] was the namespace prefix, also check the name
                PathHelper::assertValidLocalName($parts[1]);
                if (0 === strlen($parts[1])) {
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
    public function validateIdentifier()
    {
        if (! $this->isMappedSuperclass) {
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
                        throw MappingException::repositoryRequired($this->name, $this->customRepositoryClassName);
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
        $this->customRepositoryClassName = $this->fullyQualifiedClassName($repositoryClassName);
        if ($this->customRepositoryClassName && !class_exists($this->customRepositoryClassName)) {
            throw MappingException::repositoryNotExisting($this->name, $this->customRepositoryClassName);
        }
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
        if (!isset(Event::$lifecycleCallbacks[$event])) {
            throw new MappingException("$event is not a valid lifecycle callback event");
        }
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
     * @param string|boolean $versionable A valid versionable annotation or false to disable versioning.
     */
    public function setVersioned($versionable)
    {
        if ($versionable && !in_array($versionable, self::$validVersionableAnnotations)) {
            throw new MappingException("Invalid value in '{$this->name}' for the versionable annotation: '{$versionable}'");
        }
        $this->versionable = $versionable;
    }

    /**
     * @param bool $referenceable
     */
    public function setReferenceable($referenceable)
    {
        if ($this->referenceable && ! $referenceable) {
            throw new MappingException('Can not overwrite referenceable attribute to false in child class');
        }
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
     * Set the JCR mixins
     *
     * @param array $mixins
     */
    public function setMixins($mixins)
    {
        $this->mixins = $mixins;
    }

    /**
     * Return the JCR mixins to be used for this node.
     *
     * @return array
     */
    public function getMixins()
    {
        return $this->mixins;
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
            }
        }

        $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
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
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->nodename = $mapping['fieldName'];
    }

    public function mapParentDocument(array $mapping, ClassMetadata $inherited = null)
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $mapping['type'] = 'parent';
        $this->validateAndCompleteFieldMapping($mapping, $inherited, false);
        $this->parentMapping = $mapping['fieldName'];
    }

    public function mapChild(array $mapping, ClassMetadata $inherited = null)
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

    public function mapChildren(array $mapping, ClassMetadata $inherited = null)
    {
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = 0;
        }
        $mapping['type'] = 'children';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        if (!is_numeric($mapping['fetchDepth'])) {
            throw new MappingException(
                sprintf('fetchDepth option must be a numerical value (is "%s") on children mapping "%s" of document %s', $mapping['fetchDepth'], $mapping['fieldName'], $this->name)
            );
        }
        unset($mapping['property']);
        $this->childrenMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapReferrers(array $mapping, ClassMetadata $inherited = null)
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
        if (isset($mapping['referringDocument'])) {
            $mapping['referringDocument'] = $this->fullyQualifiedClassName($mapping['referringDocument']);
        }

        $mapping['type'] = 'referrers';
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited, false);
        unset($mapping['strategy']); // this would be a lie, we want the strategy of the referring field
        $this->referrersMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapMixedReferrers(array $mapping, ClassMetadata $inherited = null)
    {
        if (!(array_key_exists('referenceType', $mapping) && in_array($mapping['referenceType'], array(null, "weak", "hard")))) {
            throw new MappingException("You have to specify a 'referenceType' for the '" . $this->name . "' mapping which must be null, 'weak' or 'hard': ".$mapping['referenceType']);
        }

        if (isset($mapping['referencedBy'])) {
            throw new MappingException('MixedReferrers has no referredBy attribute, use Referrers for this: ' . $mapping['fieldName']);
        }
        if (empty($mapping['cascade'])) {
            $mapping['cascade'] = null;
        }

        $mapping['type'] = 'mixedreferrers';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->mixedReferrersMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapLocale(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'locale';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->localeMapping = $mapping['fieldName'];
    }

    public function mapVersionName(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'versionname';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->versionNameField = $mapping['fieldName'];
    }

    public function mapVersionCreated(array $mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = 'versioncreated';
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);
        $this->versionCreatedField = $mapping['fieldName'];
    }

    public function mapLifecycleCallbacks(array $mapping)
    {
        $this->setLifecycleCallbacks($mapping);
    }

    /**
     * @param array         $mapping
     * @param ClassMetadata $inherited  same field of parent document, if any
     * @param bool          $isField    whether this is a field or an association
     * @param string        $phpcrLabel the name for the phpcr thing. usually property,
     *                                  except for child where this is name. referrers
     *                                  use false to not set anything.
     *
     * @return mixed
     *
     * @throws MappingException
     */
    protected function validateAndCompleteFieldMapping(array $mapping, ClassMetadata $inherited = null, $isField = true, $phpcrLabel = 'property')
    {
        if ($inherited) {
            if (!isset($mapping['inherited'])) {
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
            throw new MappingException("Mapping a property requires to specify the field name in '{$this->name}'.");
        }

        if (!is_string($mapping['fieldName'])) {
            throw new MappingException("Field name must be of type string in '{$this->name}'.");
        }

        if (!$this->reflClass->hasProperty($mapping['fieldName'])) {
            throw MappingException::classHasNoField($this->name, $mapping['fieldName']);
        }

        if (empty($mapping['property'])) {
            $mapping['property'] = $mapping['fieldName'];
        }

        if ($phpcrLabel &&
            (!isset($mapping[$phpcrLabel]) || empty($mapping[$phpcrLabel]))
        ) {
            $mapping[$phpcrLabel] = $mapping['fieldName'];
        }

        if ($isField && isset($mapping['assoc'])) {
            $mapping['multivalue'] = true;
            if (empty($mapping['assoc'])) {
                $mapping['assoc'] = $mapping['property'].'Keys';
            }
            $mapping['assocNulls'] = $mapping['property'].'Nulls';
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

    protected function validateAndCompleteAssociationMapping($mapping, ClassMetadata $inherited = null, $phpcrLabel = 'property')
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited, false, $phpcrLabel);
        if ($inherited) {
            return $mapping;
        }

        $mapping['sourceDocument'] = $this->name;
        if (isset($mapping['targetDocument'])) {
            $mapping['targetDocument'] = $this->fullyQualifiedClassName($mapping['targetDocument']);
        }
        if (empty($mapping['strategy'])) {
            $mapping['strategy'] = 'weak';
        } elseif (!in_array($mapping['strategy'], array(null, 'weak', 'hard', 'path'))) {
            throw new MappingException("The attribute 'strategy' for the '" . $this->name . "' association has to be either a null, 'weak', 'hard' or 'path': ".$mapping['strategy']);
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
     * @throws MappingException if inconsistencies are discovered.
     */
    public function validateClassMapping()
    {
        // associative array fields need a separate property to store the keys.
        // make sure that generated or specified name does not collide with an
        // existing mapping.
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

        if (!empty($this->versionNameField) && !$this->versionable){
            throw new MappingException(sprintf("You cannot use the @VersionName annotation on the non-versionable document %s (field = %s)", $this->name, $this->versionNameField));
        }

        if (!empty($this->versionCreatedField) && !$this->versionable){
            throw new MappingException(sprintf("You cannot use the @VersionCreated annotation on the non-versionable document %s (field = %s)", $this->name, $this->versionCreatedField));
        }

        if (count($this->translatableFields)) {
            if (!isset($this->localeMapping)) {
                throw new MappingException("You must define a locale mapping for translatable document '".$this->name."'");
            }
        }

        // we allow mixed referrers on non-referenceable documents. maybe the mix:referenceable is just not mapped
        if (count($this->referrersMappings)) {
            if (!$this->referenceable) {
                throw new MappingException('You can not have referrers mapped on document "'.$this->name.'" as the document is not referenceable');
            }

            foreach ($this->referrersMappings as $referrerName) {
                $mapping = $this->mappings[$referrerName];
                // only a santiy check with reflection. otherwise we could run into endless loops
                if (!ClassLoader::classExists($mapping['referringDocument'])) {
                    throw new MappingException(sprintf('Invalid referrer mapping on document "%s" for field "%s": The referringDocument class "%s" does not exist', $this->name, $mapping['fieldName'], $mapping['referringDocument']));
                }
                $reflection = new ReflectionClass($mapping['referringDocument']);
                if (! $reflection->hasProperty($mapping['referencedBy'])) {
                    throw new MappingException(sprintf('Invalid referrer mapping on document "%s" for field "%s": The referringDocument "%s" has no property "%s"', $this->name, $mapping['fieldName'], $mapping['referringDocument'], $mapping['referencedBy']));
                }
            }
        }

        $this->validateIdentifier();
    }

    /**
     * Determine the id strategy for this document. Only call this if no explicit
     * strategy was assigned.
     *
     * @throws MappingException if no strategy is applicable with the mapped fields.
     */
    private  function determineIdStrategy()
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

        throw new MappingException(sprintf('No id generator could be determined in "%s". Either map a parent and a nodename field and add values to them, or map the id field and configure a mapping strategy', $this->name));
    }

    public function mapManyToOne($mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = self::MANY_TO_ONE;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[$mapping['fieldName']] = $mapping['fieldName'];
    }

    public function mapManyToMany($mapping, ClassMetadata $inherited = null)
    {
        $mapping['type'] = self::MANY_TO_MANY;
        $mapping = $this->validateAndCompleteAssociationMapping($mapping, $inherited);
        $this->referenceMappings[$mapping['fieldName']] = $mapping['fieldName'];
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
        return $this->idGenerator === self::GENERATOR_TYPE_REPOSITORY;
    }

    /**
     * Checks whether the class uses no id generator.
     *
     * @return boolean TRUE if the class does not use any id generator, FALSE otherwise.
     */
    public function isIdGeneratorNone()
    {
        return $this->idGenerator === self::GENERATOR_TYPE_NONE;
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
        if (null === $fieldName) {
            return false;
        }
        return in_array($fieldName, $this->fieldMappings)
            || isset($this->inheritedFields[$fieldName])
            || $this->identifier === $fieldName
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
        if (!$this->hasField($fieldName)) {
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
            && in_array($this->mappings[$fieldName]['type'], array(self::MANY_TO_ONE, self::MANY_TO_MANY, 'referrers', 'mixedreferrers', 'children', 'child', 'parent'))
        ;
    }

    /**
     * @return array the association mapping with the field of this name
     *
     * @throws MappingException if the class has no mapping field with this name
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
            || isset($this->referenceMappings[$fieldName]) && self::MANY_TO_ONE === $this->mappings[$fieldName]['type']
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->referenceMappings[$fieldName]) && self::MANY_TO_MANY === $this->mappings[$fieldName]['type']
            || isset($this->referrersMappings[$fieldName])
            || isset($this->mixedReferrersMappings[$fieldName])
            || isset($this->childrenMappings[$fieldName])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
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
        if (empty($this->mappings[$fieldName]['targetDocument'])) {
            throw new MappingException("Association name expected, '$fieldName' is not an association in '{$this->name}'.");
        }

        return $this->mappings[$fieldName]['targetDocument'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        throw new BadMethodCallException(__METHOD__."  not yet implemented in '{$this->name}'");
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        throw new BadMethodCallException(__METHOD__."  not yet implemented in '{$this->name}'");
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
     * Check if the field is nullable or not.
     *
     * @param string $fieldName  The field name
     *
     * @return boolean TRUE if the field is nullable, FALSE otherwise.
     */
    public function isNullable($fieldName)
    {
        $mapping = $this->getField($fieldName);
        if ($mapping !== false) {
            return isset($mapping['nullable']) && true == $mapping['nullable'];
        }

        return false;
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
                $mapping['property'] = 'jcr:uuid';
            }

            if (isset($parentMapping['type'])) {
                if (isset($mapping['type']) && $parentMapping['type'] !== $mapping['type']) {
                    throw new MappingException("You cannot change the type of a field via inheritance in '{$this->name}'");
                }
                $mapping['type'] = $parentMapping['type'];
            }
        }

        if (isset($mapping['property']) && $mapping['property'] == 'jcr:uuid') {
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

            if (!isset($mapping['nullable'])) {
                $mapping['nullable'] = isset($parentMapping['nullable']) ? $parentMapping['nullable'] : false;
            }
        }

        // Add the field to the list of translatable fields
        if (!empty($parentMapping['translated']) || !empty($mapping['translated'])) {
            $mapping['translated'] = true;
        }

        $mapping = $this->validateAndCompleteFieldMapping($mapping, $inherited);

        // Add the field to the list of translatable fields
        if (!empty($mapping['translated']) && !in_array($mapping['property'], $this->translatableFields)) {
            $this->translatableFields[] = $mapping['property'];
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
        );

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

        if ($this->localeMapping) {
            $serialized[] = 'localeMapping';
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
     *
     * @return object
     */
    public function newInstance()
    {
        if (!$this->instantiator instanceof InstantiatorInterface) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator->instantiate($this->name);
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
     *
     * @return array
     */
    public function getIdentifierValues($document)
    {
        try {
            return array($this->identifier => $this->getIdentifierValue($document));
        } catch (PHPCRException $e) {
            return array();
        }
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

    /**
     * Whether $fieldName is the universally unique identifier of the document.
     *
     * @param string $fieldName
     *
     * @return boolean True if $fieldName is mapped as the uuid, false otherwise.
     */
    public function isUuid($fieldName)
    {
       return $this->uuidFieldName === $fieldName;
    }

    /**
     * @param   string $className
     * @return  string
     */
    public function fullyQualifiedClassName($className)
    {
        if ($className !== null && strpos($className, '\\') === false && strlen($this->namespace) > 0) {
            return $this->namespace . '\\' . $className;
        }

        return $className;
    }
}
