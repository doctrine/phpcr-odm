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

use Doctrine\Common\Persistence\ObjectManager;

class MoveEventArgs extends LifecycleEventArgs
{
    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $targetPath;

    /**
     * Constructor
     *
     * @param object                              $document   The object
     * @param \Doctrine\ODM\PHPCR\DocumentManager $dm         The document manager
     * @param string                              $sourcePath The source path
     * @param string                              $targetPath The target path
     */
    public function __construct($document, ObjectManager $om, $sourcePath, $targetPath)
    {
        parent::__construct($document, $om);
        $this->sourcePath = $sourcePath;
        $this->targetPath = $targetPath;
    }

    /**
     * @deprecated  Will be dropped in favor of getObject in 1.0
     * @return      object
     */
    public function getDocument()
    {
        return $this->getEntity();
    }

    /**
     * @deprecated  Will be dropped in favor of getObjectManager in 1.0
     * @return      \Doctrine\ODM\PHPCR\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }

    /**
     * Get the source path
     *
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * Get the target path
     *
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }
}
