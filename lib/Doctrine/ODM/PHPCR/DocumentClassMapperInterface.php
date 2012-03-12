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

use Doctrine\ODM\PHPCR\DocumentManager;

use PHPCR\NodeInterface;

interface DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node
     *
     * @param DocumentManager
     * @param NodeInterface $node
     * @param string $className
     *
     * @return string
     *
     * @throws \RuntimeException if no class name could be determined
     */
    function getClassName(DocumentManager $dm, NodeInterface $node, $className = null);

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later
     *
     * @param DocumentManager
     * @param NodeInterface $node
     * @param string $className
     */
    function writeMetadata(DocumentManager $dm, NodeInterface $node, $className);

    /**
     * @param DocumentManager
     * @param object $document
     * @param string $className
     * @throws \InvalidArgumentException
     */
    function validateClassName(DocumentManager $dm, $document, $className);
}
