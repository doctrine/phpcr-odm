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
use PHPCR\RepositoryException;
use PHPCR\Util\NodeHelper;

/**
 * Generate the id using the auto naming strategy
 */
class FieldSlugifierIdGenerator extends ParentIdGenerator
{
    /**
     * @var callable
     */
    private $slugifier;

    /**
     * @param callable Slugifier callable
     */
    public function __construct($slugifier)
    {
        $this->slugifier = $slugifier;
    }

    /**
     * Use the parent field together with an auto generated name to generate the id
     *
     * {@inheritDoc}
     */
    public function generate($document, ClassMetadata $class, DocumentManager $dm, $parent = null)
    {
        if (null === $parent) {
            $parent = $class->parentMapping ? $class->getFieldValue($document, $class->parentMapping) : null;
        }

        if (null === $parent) {
            throw IdException::noIdNoParent($document, $class->parentMapping);
        }

        if (!isset($class->idGeneratorOptions['field'])) {
            throw new \InvalidArgumentException(
                'The field slugifier ID generator requires that you specify the '.
                '"field" option specifying the field to be slugified.'
            );
        }

        $fieldName = $class->idGeneratorOptions['field'];

        if (!$class->hasField($fieldName)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" has been specified as the field to be slugified by the ' .
                'field slugifier ID generator. But it is not mapped',
                $fieldName
            ));
        }

        $value = $class->getFieldValue($document, $fieldName);

        if (!$value) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot slugify node name from empty field value for field "%s"',
                $fieldName
            ));
        }

        $slugified = $this->slugify($value);

        $parentId = $dm->getUnitOfWork()->getDocumentId($parent);

        return $parentId . '/' . $slugified;
    }

    /**
     * Try and call the slugifier
     */
    private function slugify($string)
    {
        static $resolvedSlugifier = null;

        if ($resolvedSlugifier) {
            return $resolvedSlugifier($string);
        }

        $slugifier = $this->slugifier;

        if ($slugifier instanceof \Closure) {
            return $resolvedSlugifier = function ($string) use ($slugifier) {
                $slugifier($string);
            };
        }

        if (is_array($string)) {
            return $resolvedSlugifier = function ($string) use ($slugifier) {
                call_user_func_array($slugifier[0], $slugifier[1]);
            };
        }

        if (is_string($string)) {
            return $resolvedSlugifier = function ($string) use ($slugifier) {
                call_user_func($string);
            };
        }

        throw new \InvalidArgumentException(sprintf(
            'Could not call given slugifier callable of type "%s"',
            gettype($string)
        ));
    }
}
