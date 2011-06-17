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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class YamlDriver extends AbstractFileDriver
{
    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $fileExtension = '.dcm.yml';

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
            if (!isset($element['alias'])) {
                throw MappingException::aliasIsNotSpecified($className);
            }
            $class->setAlias($element['alias']);
            if (isset($element['isVersioned']) && $element['isVersioned']) {
                $class->setVersioned(true);
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
                $this->addFieldMapping($class, $mapping);
            }
        }
        if (isset($element['id'])) {
            $mapping = array('fieldName' => $element['id'], 'id' => true);
            $this->addIdMapping($class, $mapping);
        }
        if (isset($element['node'])) {
            $mapping = array('fieldName' => $element['node']);
            $this->addNodeMapping($class, $mapping);
        }
        if (isset($element['child'])) {
            $mapping = array('fieldName' => $element['child']);
            $this->addChildMapping($class, $mapping);
        }
        if (isset($element['children'])) {
            $mapping = array('fieldName' => $element['children']);
            $this->addChildrenMapping($class, $mapping);
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
        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $type => $methods) {
                foreach ($methods as $method) {
                    $class->addLifecycleCallback($method, constant('Doctrine\ODM\PHPCR\Event::' . $type));
                }
            }
        }

    }

    private function addIdMapping(ClassMetadata $class, $mapping)
    {
        $class->mapId($mapping);
    }

    private function addFieldMapping(ClassMetadata $class, $mapping)
    {
        $class->mapField($mapping);
    }

    private function addNodeMapping(ClassMetadata $class, $mapping)
    {
        $class->mapNode($mapping);
    }

    private function addChildMapping(ClassMetadata $class, $mapping)
    {
        $class->mapChild($mapping);
    }

    private function addChildrenMapping(ClassMetadata $class, $mapping)
    {
        $class->mapChildren($mapping);
    }

    private function addMappingFromReference(ClassMetadata $class, $fieldName, $reference, $type)
    {
        $mapping = array(
            'cascade'        => isset($reference['cascade']) ? $reference['cascade'] : null,
            'type'           => $type,
            'reference'      => true,
            'targetDocument' => isset($reference['targetDocument']) ? $reference['targetDocument'] : null,
            'fieldName'      => $fieldName,
            'strategy'       => isset($reference['strategy']) ? (string) $reference['strategy'] : 'pushPull',
        );
        $this->addFieldMapping($class, $mapping);
    }

    protected function loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}
