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

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.

 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{

    /**
     * {@inheritdoc}
     */
    protected $cacheSalt = '\$PHPCRODMCLASSMETADATA';
    
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     *  The used metadata driver.
     *
     * @var \Doctrine\ODM\PHPCR\Mapping\Driver\Driver
     */
    private $driver;

    /**
     * Creates a new factory instance that uses the given DocumentManager instance.
     *
     * @param $dm The DocumentManager instance
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm     = $dm;
        $conf = $this->dm->getConfiguration();
        $this->setCacheDriver($conf->getMetadataCacheImpl());
        $this->driver = $conf->getMetadataDriverImpl();
    }

    /**
     * {@inheritdoc}
     * 
     * @throws MappingException
     */
    public function getMetadataFor($className)
    {
        if ($metadata = parent::getMetadataFor($className)) {
            return $metadata;
        }
        throw MappingException::classNotMapped($className);
    }

    /**
     * {@inheritdoc}
     * 
     * @throws MappingException
     */
    function loadMetadata($className)
    {
        if (class_exists($className)) {
            return parent::loadMetadata($className);
        }
        throw MappingException::classNotFound($className);
    }


    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->dm->getConfiguration()->getDocumentNamespace($namespaceAlias) 
            . '\\' . $simpleClassName;
    }

    /**
     * {@inheritdoc}
     * 
     * @todo unclear usage of rootEntityFound
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound) {
        if ($parent) {
            $this->getDriver()->loadMetadataForClass($parent->name, $parent);
        }
        $this->getDriver()->loadMetadataForClass($class->name, $class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver() {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize() {
        $this->initialized = true;
    }
    
}
