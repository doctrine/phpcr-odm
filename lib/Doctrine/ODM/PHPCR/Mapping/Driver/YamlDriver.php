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

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Symfony\Component\Yaml\Yaml;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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
        $element = $this->getElement($className);
        if (!$element) {
            return;
        }
        $element['type'] = isset($element['type']) ? $element['type'] : 'document';

        if ($element['type'] == 'document') {
            if (isset($element['repositoryClass'])) {
                $class->setCustomRepositoryClassName($element['repositoryClass']);
            }

            if (isset($element['versionable']) && $element['versionable']) {
                $class->setVersioned($element['versionable']);
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
            $mapping = array('fieldName' => $element['id'], 'id' => true);
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
            $class->mapParentDocument(array('fieldName' => $element['parentdocument']));
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
                $class->mapChild($mapping);
            }
        }
        if (isset($element['children'])) {
            foreach ($element['children'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $filter = $mapping;
                    $mapping = array();
                    $mapping['filter'] = $filter;
                }
                if (!isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
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

        // TODO: referrers, locale

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

    }

    private function addMappingFromReference(ClassMetadata $class, $fieldName, $reference, $type)
    {
        $class->mapField(array(
            'type'           => $type,
            'reference'      => true,
            'targetDocument' => isset($reference['targetDocument']) ? $reference['targetDocument'] : null,
            'fieldName'      => $fieldName,
            'strategy'       => isset($reference['strategy']) ? (string) $reference['strategy'] : 'pushPull',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMappingFile($file)
    {
        return Yaml::parse($file);
    }
}