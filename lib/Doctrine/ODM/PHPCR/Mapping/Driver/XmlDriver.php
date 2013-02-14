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

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as DoctrineMappingException;
use SimpleXmlElement;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    /**
     * {@inheritdoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        /** @var $class \Doctrine\ODM\PHPCR\Mapping\ClassMetadata */
        try {
            $xmlRoot = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$xmlRoot) {
            return;
        }

        if ($xmlRoot->getName() == 'document') {
            if (isset($xmlRoot['repository-class'])) {
                $class->setCustomRepositoryClassName((string) $xmlRoot['repository-class']);
            }

            if (isset($xmlRoot['translator'])) {
                $class->setTranslator((string) $xmlRoot['translator']);
            }

            if (isset($xmlRoot['versionable']) && $xmlRoot['versionable'] !== 'false') {
                $class->setVersioned((string) $xmlRoot['versionable']);
            }

            if (isset($xmlRoot['referenceable']) && $xmlRoot['referenceable'] !== 'false') {
                $class->setReferenceable((bool) $xmlRoot['referenceable']);
            }

            $class->setNodeType(isset($xmlRoot['nodeType']) ? (string) $xmlRoot['nodeType'] : 'nt:unstructured');
        } elseif ($xmlRoot->getName() === 'mapped-superclass') {
            $class->isMappedSuperclass = true;
        }

        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping = array();
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string) $value;
                    // convert bool fields
                    if ($key === 'id' || $key === 'multivalue') {
                        $mapping[$key] = ('true' === $mapping[$key]) ? true : false;
                    }
                }
                $class->mapField($mapping);
            }
        }
        if (isset($xmlRoot->id)) {
            $mapping = array(
                'fieldName' => (string) $xmlRoot->id->attributes()->name,
                'id' => true,
            );
            if (isset($xmlRoot->id->generator) && isset($xmlRoot->id->generator->attributes()->strategy)) {
                $mapping['strategy'] = (string) $xmlRoot->id->generator->attributes()->strategy;
            }
            $class->mapId($mapping);
        }
        if (isset($xmlRoot->node)) {
            $class->mapNode(array('fieldName' => (string) $xmlRoot->node->attributes()->name));

        }
        if (isset($xmlRoot->nodename)) {
            $class->mapNodename(array('fieldName' => (string) $xmlRoot->nodename->attributes()->name));
        }
        if (isset($xmlRoot->parentdocument)) {
            $mapping = array(
                'fieldName' => (string) $xmlRoot->parentdocument->attributes()->name,
                'cascade' => (isset($xmlRoot->parentdocument->cascade)) ? $this->getCascadeMode($xmlRoot->parentdocument->cascade) : 0,
            );
            $class->mapParentDocument($mapping);
        }
        if (isset($xmlRoot->child)) {
            foreach ($xmlRoot->child as $child) {
                $attributes = $child->attributes();
                $mapping = array(
                    'fieldName' => (string) $attributes->fieldName,
                    'cascade' => (isset($child->cascade)) ? $this->getCascadeMode($child->cascade) : 0,
                );
                if (isset($attributes['name'])) {
                    $mapping['name'] = (string) $attributes->name;
                }
                $class->mapChild($mapping);
            }
        }
        if (isset($xmlRoot->children)) {
            foreach ($xmlRoot->children as $children) {
                $attributes = $children->attributes();
                $mapping = array(
                    'fieldName' => (string) $attributes->fieldName,
                    'cascade' => (isset($children->cascade)) ? $this->getCascadeMode($children->cascade) : 0,
                    'filter' => isset($attributes['filter']) ? (string) $attributes->filter : null,
                    'fetchDepth' => isset($attributes['fetchDepth']) ? (int) $attributes->fetchDepth : null,
                    'ignoreUntranslated' => !empty($attributes['ignoreUntranslated']),
            );
                $class->mapChildren($mapping);
            }
        }
        if (isset($xmlRoot->{'reference-many'})) {
            foreach ($xmlRoot->{'reference-many'} as $reference) {
                $reference['cascade'] = (isset($reference->cascade)) ? $this->getCascadeMode($reference->cascade) : 0;
                $this->addReferenceMapping($class, $reference, 'many');
            }
        }
        if (isset($xmlRoot->{'reference-one'})) {
            foreach ($xmlRoot->{'reference-one'} as $reference) {
                $reference['cascade'] = (isset($reference->cascade)) ? $this->getCascadeMode($reference->cascade) : 0;
                $this->addReferenceMapping($class, $reference, 'one');
            }
        }

        if (isset($xmlRoot->locale)) {
            $class->mapLocale(array('fieldName' => (string) $xmlRoot->locale->attributes()->fieldName));
        }

        if (isset($xmlRoot->referrers)) {
            foreach ($xmlRoot->referrers as $referrers) {
                $attributes = $referrers->attributes();
                $mapping = array(
                    'fieldName' => (string) $attributes->fieldName,
                    'cascade' => (isset($referrers->cascade)) ? $this->getCascadeMode($referrers->cascade) : 0,
                    'filter' => isset($attributes['filter']) ? (string) $attributes->filter : null,
                    'referenceType' => isset($attributes['reference-type']) ? (string) $attributes->{'reference-type'} : null,
                );
                $class->mapReferrers($mapping);
            }
        }
        if (isset($xmlRoot->{'version-name'})) {
            $class->mapVersionName(array('fieldName' => (string) $xmlRoot->{'version-name'}->attributes()->fieldName));
        }
        if (isset($xmlRoot->{'version-created'})) {
            $class->mapVersionCreated(array('fieldName' => (string) $xmlRoot->{'version-created'}->attributes()->fieldName));
        }

        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $class->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ODM\PHPCR\Event::' . (string) $lifecycleCallback['type']));
            }
        }

        $class->validateClassMapping();
    }

    private function addReferenceMapping(ClassMetadata $class, $reference, $type)
    {
        $attributes = (array) $reference->attributes();
        $mapping = $attributes["@attributes"];

        if ($type === 'many') {
            $class->mapManyToMany($mapping);
        } elseif ($type === 'one') {
            $class->mapManyToOne($mapping);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMappingFile($file)
    {
        $result = array();
        $entity = libxml_disable_entity_loader(true);
        $xmlElement = simplexml_load_string(file_get_contents($file));
        libxml_disable_entity_loader($entity);

        foreach (array('document', 'mapped-superclass') as $type) {
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
     * @param SimpleXMLElement $cascadeElement cascade element.
     *
     * @return integer a bitmask of cascade options.
     */
    private function getCascadeMode(SimpleXMLElement $cascadeElement)
    {
        $cascade = 0;
        foreach ($cascadeElement->children() as $action) {
            // According to the JPA specifications, XML uses "cascade-persist"
            // instead of "persist". Here, both variations
            // are supported because both YAML and Annotation use "persist"
            // and we want to make sure that this driver doesn't need to know
            // anything about the supported cascading actions
            $cascadeMode = str_replace('cascade-', '', $action->getName());
            $constantName = 'Doctrine\ODM\PHPCR\Mapping\ClassMetadata::CASCADE_' . strtoupper($cascadeMode);
            if (!defined($constantName)) {
                throw new MappingException("Cascade mode '$cascadeMode' not supported.");
            }
            $cascade |= constant($constantName);
        }

        return $cascade;
    }
}
