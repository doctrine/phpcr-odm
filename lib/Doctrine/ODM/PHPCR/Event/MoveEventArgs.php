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

/**
 * MoveEventArgs
 */
class MoveEventArgs extends \Doctrine\Common\EventArgs
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

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
    public function __construct($document, $dm, $sourcePath, $targetPath)
    {
        $this->document = $document;
        $this->dm = $dm;
        $this->sourcePath = $sourcePath;
        $this->targetPath = $targetPath;
    }

    /**
     * Get the document
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Get the document manager
     *
     * @return \Doctrine\ODM\PHPCR\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
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
