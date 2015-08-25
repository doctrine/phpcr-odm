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

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * A dummy translation strategy for non-translated fields
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      David Buchmann <mail@davidbu.ch>
 */
class NonTranslatedStrategy implements TranslationStrategyInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $dm;

    /**
     * @param DocumentManagerInterface $dm
     */
    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @inheritDoc
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($data as $field => $propValue) {
            $mapping = $metadata->mappings[$field];
            $propName = $mapping['property'];

            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (isset($mapping['assoc'])) {
                    $propValue = $this->dm->getUnitOfWork()->processAssoc($node, $mapping, $propValue);
                }
            }

            $node->setProperty($propName, $propValue);
        }
    }

    /**
     * @inheritDoc
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * @inheritDoc
     *
     * Remove the (untranslated) fields listed in $metadata->translatableFields
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $node->setProperty($mapping['property'], null);
        }
    }

    /**
     * @inheritDoc
     *
     * This will remove all fields that are now declared as translated
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * @inheritDoc
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * @inheritDoc
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale)
    {
        return array($alias, $propertyName);
    }

    /**
     * @inheritDoc
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    ) {
        // nothing to alter
    }
}
