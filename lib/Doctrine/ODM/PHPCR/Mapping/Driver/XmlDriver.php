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
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use SimpleXmlElement;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class XmlDriver extends AbstractFileDriver
{
    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $fileExtension = '.dcm.xml';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $xmlRoot = $this->getElement($className);
        if (!$xmlRoot) {
            return;
        }

        if ($xmlRoot->getName() == 'document') {
            if (isset($xmlRoot['repository-class'])) {
                $class->setCustomRepositoryClassName((string) $xmlRoot['repository-class']);
            }
            if (!isset($xmlRoot['alias'])) {
                throw MappingException::aliasIsNotSpecified($className);
            }
            $class->setAlias((string) $xmlRoot['alias']);
            if (isset($xmlRoot['is-versioned']) && $xmlRoot['is-versioned'] === 'true') {
                $class->setVersioned(true);
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
                $this->addFieldMapping($class, $mapping);
            }
        }
        if (isset($xmlRoot->id)) {
            $mapping = array('fieldName' => (string) $xmlRoot->id->attributes()->name, 'id' => true);
            $this->addIdMapping($class, $mapping);
        }
        if (isset($xmlRoot->node)) {
            $mapping = array('fieldName' => (string) $xmlRoot->node->attributes()->name);
            $this->addNodeMapping($class, $mapping);
        }
        if (isset($xmlRoot->child)) {
            $mapping = array('fieldName' => (string) $xmlRoot->child->attributes()->name);
            $this->addChildMapping($class, $mapping);
        }
        if (isset($xmlRoot->{'reference-many'})) {
            foreach ($xmlRoot->{'reference-many'} as $reference) {
                $this->addReferenceMapping($class, $reference, 'many');
            }
        }
        if (isset($xmlRoot->{'reference-one'})) {
            foreach ($xmlRoot->{'reference-one'} as $reference) {
                $this->addReferenceMapping($class, $reference, 'one');
            }
        }

        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $class->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ODM\PHPCR\Event::' . (string) $lifecycleCallback['type']));
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

    private function addReferenceMapping(ClassMetadata $class, $reference, $type)
    {
        $cascade = array_keys((array) $reference->cascade);
        if (1 === count($cascade)) {
            $cascade = current($cascade) ?: next($cascade);
        }
        $attributes = $reference->attributes();
        $mapping = array(
            'cascade'        => $cascade,
            'type'           => $type,
            'reference'      => true,
            'targetDocument' => isset($attributes['target-document']) ? (string) $attributes['target-document'] : null,
            'name'           => (string) $attributes['field'],
        );
        $this->addFieldMapping($class, $mapping);
    }

    protected function loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        foreach (array('document', 'mapped-superclass') as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $documentName = (string) $documentElement['name'];
                    $result[$documentName] = $documentElement;
                }
            }
        }

        return $result;
    }
}
