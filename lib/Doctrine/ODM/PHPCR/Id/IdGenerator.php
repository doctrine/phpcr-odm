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
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Used to abstract ID generation
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class IdGenerator
{
    /**
     * Factory method for the predefined strategies.
     *
     * @param int $generatorType
     *
     * @return IdGenerator
     */
    public static function create($generatorType)
    {
        switch ($generatorType) {
            case ClassMetadata::GENERATOR_TYPE_ASSIGNED:
                $instance = new AssignedIdGenerator();
                break;
            case ClassMetadata::GENERATOR_TYPE_REPOSITORY:
                $instance = new RepositoryIdGenerator();
                break;
            case ClassMetadata::GENERATOR_TYPE_PARENT:
                $instance = new ParentIdGenerator();
                break;

            default:
                throw new \InvalidArgumentException("ID Generator does not exist: $generatorType");
        }

        return $instance;
    }

    /**
     * Generate the actual id, to be overwritten by extending classes
     *
     * @param object          $document the object to create the id for
     * @param ClassMetadata   $cm       class metadata of this object
     * @param DocumentManager $dm
     *
     * @return string the id for this document
     */
    abstract public function generate($document, ClassMetadata $cm, DocumentManager $dm);
}
