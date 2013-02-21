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

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\DocumentManager;

class LoadClassMetadataEventArgs extends EventArgs
{
    /**
     * @var \Doctrine\PHPCR\ODM\Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @var \Doctrine\PHPCR\ODM\DocumentManager
     */
    private $dm;

    /**
     * Constructor.
     *
     * @param \Doctrine\PHPCR\ODM\Mapping\ClassMetadataInfo $classMetadata
     * @param \Doctrine\PHPCR\ODM\DocumentManager $dm
     */
    public function __construct(ClassMetadata $classMetadata, DocumentManager $dm)
    {
        $this->classMetadata = $classMetadata;
        $this->dm            = $dm;
    }

    /**
     * Retrieve associated ClassMetadata.
     *
     * @return \Doctrine\PHPCR\ODM\Mapping\ClassMetadataInfo
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * Retrieve associated DocumentManager.
     *
     * @return \Doctrine\PHPCR\ODM\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * Retrieve associated ObjectManager.
     *
     * @return \Doctrine\PHPCR\ODM\ObjectManager
     */
    public function getObjectManager()
    {
        return $this->dm;
    }
}
