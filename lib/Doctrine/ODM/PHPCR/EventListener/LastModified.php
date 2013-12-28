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

namespace Doctrine\ODM\PHPCR\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * This listener ensures that the jcr:lastModified property is updated
 * on prePersist and preUpdate events.
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class LastModified
{
    /**
     * @param LifecycleEventArgs $e
     */
    public function prePersist(LifecycleEventArgs $e)
    {
        $this->updateLastModifiedProperty($e);
    }

    /**
     * @param LifecycleEventArgs $e
     */
    public function preUpdate(LifecycleEventArgs $e)
    {
        $this->updateLastModifiedProperty($e);
    }

    /**
     * If the document has the mixin mix:lastModified then update the field
     * that is mapped to jcr:lastModified.
     *
     * @param LifecycleEventArgs $e
     */
    protected function updateLastModifiedProperty(LifecycleEventArgs $e)
    {
        /** 
         * @var \Jackalope\Node $document
         */
        $document = $e->getObject();


        /**
         * @var \Doctrine\ODM\PHPCR\DocumentManager $dm
         */
        $dm = $e->getObjectManager();

        $metadata = $dm->getClassMetadata(get_class($document));
        $mixins = $metadata->getMixins();

        if (!in_array('mix:lastModified', $mixins)) {
            return;
        }

        foreach ($metadata->getFieldNames() as $fieldName) {
            $field = $metadata->getField($fieldName);
            if ('jcr:lastModified' == $field['property']) {
                $metadata->setFieldValue($document, $fieldName, new \DateTime);
                break;
            }
        }
    }
}
