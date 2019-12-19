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

use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use PHPCR\NodeInterface;

interface DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node
     *
     * @param DocumentManagerInterface $dm
     * @param NodeInterface            $node
     * @param string                   $className explicit class to use. If set, this
     *                                            class or a subclass of it has to be used. If this is not possible,
     *                                            an InvalidArgumentException has to be thrown.
     *
     * @throws ClassMismatchException if $node represents a class that is not
     *                                a descendant of $className
     *
     * @return string $className if not null, the class configured for this
     *                node if defined and the Generic document if no better class can be
     *                found
     */
    public function getClassName(DocumentManagerInterface $dm, NodeInterface $node, $className = null);

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later
     *
     * @param DocumentManagerInterface $dm
     * @param NodeInterface            $node
     * @param string                   $className
     */
    public function writeMetadata(DocumentManagerInterface $dm, NodeInterface $node, $className);

    /**
     * Check if the document is instance of the specified $className and
     * throw exception if not.
     *
     * @param DocumentManagerInterface $dm
     * @param object                   $document
     * @param string                   $className
     *
     * @throws ClassMismatchException if document is not of type $className
     */
    public function validateClassName(DocumentManagerInterface $dm, $document, $className);
}
