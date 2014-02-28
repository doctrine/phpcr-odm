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

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SelectorInterface;

/**
 * Operations that a translation strategy must support.
 *
 * A translation strategy is responsible for storing translations to PHPCR and
 * retrieving them again.
 */
interface TranslationStrategyInterface
{
    /**
     * Save the translatable fields of a node
     *
     * @param array         $data     Data to save (field name => value to persist)
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to persist the translations to
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Load the translatable fields of a node.
     *
     * Either loads all translatable fields into the document and returns true or
     * returns false if this is not possible.
     *
     * @param object        $document The document in which to load the data
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to load the translations from
     *
     * @return boolean true if the translation was completely loaded, false otherwise
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Removes all the translated fields for all translations of this node.
     * This will only be called just before the node itself is removed.
     *
     * @param object        $document The document from which the translations must be removed
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata);

    /**
     * Remove the translated fields of a node in a given language
     *
     * The document object is not altered by this operation.
     *
     * @param object        $document The document from which the translations must be removed
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to remove
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Get the list of locales persisted for this node
     *
     * @param object        $document The document that must be checked
     * @param NodeInterface $node     The Physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     *
     * @return array with the locales strings
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata);
    /**
     * Get the location of the property for the base property name in a given
     * language.
     *
     * @param string $alias        The selector alias of the main node.
     * @param string $propertyName The base name of the translated property.
     * @param string $locale       The requested locale.
     *
     * @return array with first alias, then the real property name.
     *
     * @since 1.1
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale);

    /**
     * This method allows a translation strategy to alter the query to
     * integrate translations that are on other nodes.
     *
     * Only called once per alias value. The selector and constraint are passed
     * by reference, the strategy can alter them to let the BuilderConverterPhpcr
     * generate a different query.
     *
     * @param QueryObjectModelFactoryInterface $qomf       The PHPCR query factory.
     * @param SelectorInterface                $selector   The current selector.
     * @param ConstraintInterface|null         $constraint The current constraint, may be empty.
     * @param string                           $alias      The selector alias of the main node.
     * @param string                           $locale     The language to use.
     *
     * @since 1.1
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SelectorInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    );

}
