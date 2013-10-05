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

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Generator to handle explicitly assigned repository paths.
 */
class AssignedIdGenerator extends IdGenerator
{
    /**
     * Use the identifier field as id and throw exception if not set.
     *
     * {@inheritDoc}
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm, $parent = null)
    {
        $id = $cm->getFieldValue($document, $cm->identifier);
        if (!$id) {
            throw new IdException('ID could not be read from the document instance using the AssignedIdGenerator.');
        }

        return $id;
    }
}
