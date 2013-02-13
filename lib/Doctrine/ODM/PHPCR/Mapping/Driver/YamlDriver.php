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
use Symfony\Component\Yaml\Yaml;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class YamlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.yml';

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
        try {
            $element = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$element) {
            return;
        }
        $element['type'] = isset($element['type']) ? $element['type'] : 'document';

        if ($element['type'] == 'document') {
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

            $class->setNodeType(isset($element['nodeType']) ? $element['nodeType'] : 'nt:unstructured');
        } elseif ($element['type'] === 'mappedSuperclass') {
            $class->isMappedSuperclass = true;
        }

        if (isset($element['fields'])) {
            foreach ($element['fields'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $type = $mapping;
                    $mapping = array();
                    $mapping['type'] = $type;
                }
                if (!isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                $class->mapField($mapping);
            }
        }
        if (isset($element['id'])) {
            if (is_array($element['id'])) {
                if (!isset($element['id']['fieldName'])) {
                    throw new MappingException("Missing fieldName property for id field");
                }
                $fieldName = $element['id']['fieldName'];
            } else {
                $fieldName = $element['id'];
            }
            $mapping = array('fieldName' => $fieldName, 'id' => true);
            if (isset($element['id']['generator']['strategy'])) {
                $mapping['strategy'] = $element['id']['generator']['strategy'];
            }
            $class->mapId($mapping);
        }
        if (isset($element['node'])) {
            $class->mapNode(array('fieldName' => $element['node']));
        }
        if (isset($element['nodename'])) {
            $class->mapNodename(array('fieldName' => $element['nodename']));
        }
        if (isset($element['parentdocument'])) {
            $mapping = array(
                'fieldName' => $element['parentdocument'],
                'cascade' => (isset($element['cascade'])) ? $this->getCascadeMode($element['cascade']) : 0,
            );

            $class->mapParentDocument($mapping);
        }
        if (isset($element['child'])) {
            foreach ($element['child'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $name = $mapping;
                    $mapping = array();
                    $mapping['name'] = $name;
                }
                if (!isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                $mapping['cascade'] = (isset($mapping['cascade'])) ? $this->getCascadeMode($mapping['cascade']) : 0;
                $class->mapChild($mapping);
            }
        }
        if (isset($element['children'])) {
            foreach ($element['children'] as $fieldName => $mapping) {
                // TODO should we really support this syntax?
                if (is_string($mapping)) {
                    $filter = $mapping;
                    $mapping = array();
                    $mapping['filter'] = $filter;
                }
                if (!isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                if (!isset($mapping['filter'])) {
                    $mapping['filter'] = null;
                }
                if (!isset($mapping['fetchDepth'])) {
                    $mapping['fetchDepth'] = null;
                }
                if (!isset($mapping['ignoreUntranslated'])) {
                    $mapping['ignoreUntranslated'] = false;
                }
                $mapping['cascade'] = (isset($mapping['cascade'])) ? $this->getCascadeMode($mapping['cascade']) : 0;
                $class->mapChildren($mapping);
            }
        }
        if (isset($element['referenceOne'])) {
            foreach ($element['referenceOne'] as $fieldName => $reference) {
                $reference['cascade'] = (isset($reference['cascade'])) ? $this->getCascadeMode($reference['cascade']) : 0;
                $this->addMappingFromReference($class, $fieldName, $reference, 'one');
            }
        }
        if (isset($element['referenceMany'])) {
            foreach ($element['referenceMany'] as $fieldName => $reference) {
                $reference['cascade'] = (isset($reference['cascade'])) ? $this->getCascadeMode($reference['cascade']) : 0;
                $this->addMappingFromReference($class, $fieldName, $reference, 'many');
            }
        }

        if (isset($element['locale'])) {
            $class->mapLocale(array('fieldName' => $element['locale']));
        }

        if (isset($element['referrers'])) {
            foreach ($element['referrers'] as $name => $attributes) {
                $mapping = array(
                    'fieldName' => $name,
                    'filter' => isset($attributes['filter']) ? $attributes['filter'] : null,
                    'referenceType' => isset($attributes['referenceType']) ? $attributes['referenceType'] : null,
                    'cascade' => (isset($attributes['cascade'])) ? $this->getCascadeMode($attributes['cascade']) : 0,
                );
                $class->mapReferrers($mapping);
            }
        }
        if (isset($element['versionName'])) {
            $class->mapVersionName(array('fieldName' => $element['versionName']));
        }
        if (isset($element['versionCreated'])) {
            $class->mapVersionCreated(array('fieldName' => $element['versionCreated']));
        }

        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $type => $methods) {
                foreach ($methods as $method) {
                    $class->addLifecycleCallback($method, constant('Doctrine\ODM\PHPCR\Event::' . $type));
                }
            }
        }

        $class->validateClassMapping();
    }

    private function addMappingFromReference(ClassMetadata $class, $fieldName, $reference, $type)
    {
        $mapping = array_merge(array('fieldName' => $fieldName), $reference);

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
        return Yaml::parse($file);
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param array $cascadeElement The cascade element.
     *
     * @return integer a bitmask of cascade options.
     */
    private function getCascadeMode(array $cascadeElement)
    {
        $cascade = 0;
        foreach ($cascadeElement as $cascadeMode) {
            $constantName = 'Doctrine\ODM\PHPCR\Mapping\ClassMetadata::CASCADE_' . strtoupper($cascadeMode);
            if (!defined($constantName)) {
                throw new MappingException("Cascade mode '$cascadeMode' not supported.");
            }
            $cascade |= constant($constantName);
        }

        return $cascade;
    }
}
