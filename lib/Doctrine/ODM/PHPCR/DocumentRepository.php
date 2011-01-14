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

namespace Doctrine\ODM\PHPCR;

/**
 * An DocumentRepository serves as a repository for documents with generic as well as
 * business specific methods for retrieving documents.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate documents.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class DocumentRepository
{
    /**
     * @var string
     */
    protected $documentName;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * Initializes a new <tt>DocumentRepository</tt>.
     *
     * @param DocumentManager $dm The DocumentManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($dm, Mapping\ClassMetadata $class)
    {
        $this->documentName = $class->name;
        $this->dm = $dm;
        $this->class = $class;
    }

    /**
     * Find a single document by its path
     *
     * @param string $path document path
     * @return object $document
     */
    public function find($path)
    {
        $uow = $this->dm->getUnitOfWork();

        try {
            $node = $this->dm->getPhpcrSession()->getNode($path);
        } catch (\PHPCR\PathNotFoundException $e) {
            return null;
        }

        $hints = array();
        return $uow->createDocument($this->documentName, $node, $hints);
    }

    /**
     * Find many document by path
     *
     * @param array $paths document paths
     * @return array of document objects
     */
    public function findMany(array $paths)
    {
        $uow = $this->dm->getUnitOfWork();

        $documents = array();
        foreach ($paths as $path) {
            // TODO: catch exception and return null when not found?
            $node = $this->dm->getPhpcrSession()->getNode($path);
            $hints = array();
            $documents[] = $uow->createDocument($this->documentName, $node, $hints);
        }
        return $documents;
    }

    /**
     * @param  object $document
     * @return void
     */
    public function refresh($document)
    {
        // TODO: call session->refresh(true) before fetching the node once Jackalope implements it

        $uow = $this->dm->getUnitOfWork();
        $node = $this->dm->getPhpcrSession()->getNode($uow->getDocumentPath($document));

        $hints = array('refresh' => true);
        $uow->createDocument($this->documentName, $node, $hints);
    }

    /**
     * @return string
     */
    public function getDocumentName()
    {
        return $this->documentName;
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }
}
