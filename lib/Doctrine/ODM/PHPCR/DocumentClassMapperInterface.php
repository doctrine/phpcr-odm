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

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;

use PHPCR\NodeInterface;

interface DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node
     *
     * @param DocumentManager $dm
     * @param NodeInterface   $node
     * @param string          $className explicit class to use. If set, this
     *      class will be used unless the declared document class is a subclass
     *      of this class. In that case the document class is used
     *
     * @return string $className if not null, the class configured for this
     *      node if defined and the Generic document if no better class can be
     *      found
     */
    public function getClassName(DocumentManager $dm, NodeInterface $node, $className = null);

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later
     *
     * @param DocumentManager $dm
     * @param NodeInterface   $node
     * @param string          $className
     */
    public function writeMetadata(DocumentManager $dm, NodeInterface $node, $className);

    /**
     * Determine if the document is instance of the specified $className and
     * throw exception if not.
     *
     * @param DocumentManager $dm
     * @param object          $document
     * @param string          $className
     *
     * @throws \InvalidArgumentException if document is not of type $className
     */
    public function validateClassName(DocumentManager $dm, $document, $className);
}
