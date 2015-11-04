<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION); HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE); ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Query\Query;
use PHPCR\Query\QueryInterface;
use PHPCR\PropertyType;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;

/**
 * DocumentManager interface
 */
interface DocumentManagerInterface extends ObjectManager
{
    /**
     * Add or replace a translation strategy
     *
     * Note that you do not need to set the default strategies attribute and
     * child unless you want to replace them.
     *
     * @param string                       $key      The name of the translation strategy.
     * @param TranslationStrategyInterface $strategy The strategy that is used with this key.
     */
    public function setTranslationStrategy($key, TranslationStrategyInterface $strategy);

    /**
     * Get the translation strategy based on the strategy short name.
     *
     * @param string $key The name of the translation strategy
     *
     * @return TranslationStrategyInterface
     *
     * @throws InvalidArgumentException if there is no strategy registered with the given key
     */
    public function getTranslationStrategy($key);

    /**
     * Check if a language chooser strategy is set
     *
     * @return bool
     */
    public function hasLocaleChooserStrategy();

    /**
     * Get the assigned language chooser strategy previously set with setLocaleChooserStrategy
     *
     * @return LocaleChooserInterface
     */
    public function getLocaleChooserStrategy();

    /**
     * Set the locale chooser strategy for multilanguage documents.
     *
     * Note that there can be only one strategy per session. This is required if you have
     * multilanguage documents and not used if you don't have multilanguage.
     *
     * @param LocaleChooserInterface $strategy
     */
    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy);

    /**
     * Gets the proxy factory used by the DocumentManager to create document proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory();

    /**
     * @return EventManager
     */
    public function getEventManager();

    /**
     * Access the underlying PHPCR session this manager is using.
     *
     * @return \PHPCR\SessionInterface
     */
    public function getPhpcrSession();

    /**
     * @return Configuration
     */
    public function getConfiguration();

    /**
     * Check if the Document manager is open or closed.
     *
     * @return boolean true if open, false if closed
     */
    public function isOpen();

    /**
     * Finds many documents by id.
     *
     * @param null|string $className Only return documents that match the
     *      specified class. All others are treated as not found.
     * @param array       $ids       List of repository paths and/or uuids to
     *      find documents. Non-existing ids are ignored.
     *
     * @return Collection list of documents that where found with the $ids and
     *      if specified the $className.
     */
    public function findMany($className, array $ids);

    /**
     * Load the document from the content repository in the given language.
     *
     * If $fallback is set to true, then the language chooser strategy is used
     * to load the best suited language for the translatable fields.
     *
     * If fallback is true and no translation is found, this method has the
     * same behaviour as find(), all translated fields will simply be null.
     * If fallback is false and the requested translation does not exist, a
     * MissingTranslationException is thrown.
     *
     * Note that this will be the same object as you got with a previous
     * find/findTranslation call - we can't allow copies of objects to exist.
     *
     * @param null|string $className The class name to find the translation for
     * @param string      $id        The identifier of the class (path or uuid)
     * @param string      $locale    The language to try to load.
     * @param boolean     $fallback  Set to true if the language fallback mechanism should be used.
     *
     * @return object the translated document.
     *
     * @throws PHPCRException if $className is specified and does not match
     *      the class of the document that was found at $id.
     * @throws MissingTranslationException if $fallback is false and the
     *      translation was not found
     */
    public function findTranslation($className, $id, $locale, $fallback = true);

    /**
     * Quote a string for inclusion in an SQL2 query
     *
     * @param string $val
     * @param int    $type
     *
     * @return string
     *
     * @see \PHPCR\PropertyType
     */
    public function quote($val, $type = PropertyType::STRING);

    /**
     * Escape the illegal characters for inclusion in a fulltext statement. Escape Character is \\.
     *
     * @param string $string
     *
     * @return string Escaped String
     *
     * @see http://jackrabbit.apache.org/api/1.4/org/apache/jackrabbit/util/Text.html #escapeIllegalJcrChars
     */
    public function escapeFullText($string);

    /**
     * Create a PHPCR Query from a query string in the specified query language to be
     * used with getDocumentsByPhpcrQuery()
     *
     * Note that it is better to use {@link createQuery}, which returns a native ODM
     * query object, when working with the ODM.
     *
     * See \PHPCR\Query\QueryInterface for list of generally supported types
     * and check your implementation documentation if you want to use a
     * different language.
     *
     * @param string $statement The statement in the specified language
     * @param string $language  The query language
     *
     * @return \PHPCR\Query\QueryInterface
     */
    public function createPhpcrQuery($statement, $language);

    /**
     * Create a ODM Query from a query string in the specified query language to be
     * used with getDocumentsByPhpcrQuery()
     *
     * See \PHPCR\Query\QueryInterface for list of generally supported types
     * and check your implementation documentation if you want to use a
     * different language.
     *
     * @param string $statement The statement in the specified language
     * @param string $language  The query language
     *
     * @return Query
     */
    public function createQuery($statement, $language);

    /**
     * Create the fluent query builder.
     *
     * Query returned by QueryBuilder::getQuery()
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder();

    /**
     * Create lower level PHPCR query builder.
     *
     * NOTE: The ODM QueryBuilder (@link createQueryBuilder) is prefered over
     *       the PHPCR QueryBuilder when working with the ODM.
     *
     * @return QueryBuilder
     */
    public function createPhpcrQueryBuilder();

    /**
     * Get document results from a PHPCR query instance
     *
     * @param QueryInterface $query           The query instance as acquired through createPhpcrQuery().
     * @param string|null    $className       Document class.
     * @param string|null    $primarySelector Name of the selector for the document to return in case of a join query.
     *
     * @return array of document instances
     */
    public function getDocumentsByPhpcrQuery(QueryInterface $query, $className = null, $primarySelector = null);

    /**
     * Bind the translatable fields of the document in the specified locale.
     *
     * This method will update the field mapped to Locale if it does not match the $locale argument.
     *
     * @param object $document The document to persist a translation of.
     * @param string $locale   The locale this document currently has.
     *
     * @throws InvalidArgumentException if $document is not an object or not managed.
     * @throws PHPCRException if the document is not translatable
     */
    public function bindTranslation($document, $locale);

    /**
     * Remove the translatable fields of the document in the specified locale
     *
     * @param object $document the document to persist a translation of
     * @param string $locale   the locale this document currently has
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function removeTranslation($document, $locale);

    /**
     * Get the list of locales that exist for the specified document,
     * including those not yet flushed, but bound.
     *
     * @param object  $document         The document to get the locales for.
     * @param boolean $includeFallbacks Whether to include the available language fallbacks.
     *
     * @return array of strings with all locales existing for this particular document
     *
     * @throws InvalidArgumentException if $document is not an object.
     * @throws MissingTranslationException if the document is not translatable
     */
    public function getLocalesFor($document, $includeFallbacks = false);

    /**
     * Determine whether this document is translatable.
     *
     * To be translatable, it needs a translation strategy and have at least
     * one translated field.
     *
     * @param object $document The document to get the locales for.
     *
     * @return bool
     */
    public function isDocumentTranslatable($document);

    /**
     * Move the previously persisted document and all its children in the tree
     *
     * Note that this does not update the Id fields of child documents and
     * neither fields with Child/Children mappings. If you want to continue
     * working with the manager after a move, you are probably safest calling
     * DocumentManager::clear and re-loading the documents you need to use.
     *
     * @param object $document   An already registered document.
     * @param string $targetPath The target path including the nodename.
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function move($document, $targetPath);

    /**
     * Reorder a child of the given document
     *
     * Note that this does not update the fields with Child/Children mappings.
     * If you want to continue working with the manager after a reorder, you are probably
     * safest calling DocumentManager::clear and re-loading the documents you need to use.
     *
     * @param object  $document   The parent document which must be persisted already.
     * @param string  $srcName    The nodename of the child to be reordered.
     * @param string  $targetName The nodename of the target of the reordering.
     * @param boolean $before     Whether to move before or after the target.
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function reorder($document, $srcName, $targetName, $before);

    /**
     * Get the child documents of a given document using an optional filter.
     *
     * This methods gets all child nodes as a collection of documents that matches
     * a given filter (same as PHPCR Node::getNodes)
     *
     * Note that this method only returns children that have been flushed.
     *
     * @param object       $document   Document instance which children should be loaded.
     * @param string|array $filter     Optional filter to filter on children names.
     * @param integer      $fetchDepth Optional fetch depth.
     * @param string       $locale     The locale to use during the loading of this collection.
     *
     * @return ChildrenCollection collection of child documents
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function getChildren($document, $filter = null, $fetchDepth = null, $locale = null);

    /**
     * Get the documents that refer a given document using an optional name.
     *
     * This methods gets a collection of documents that have references to the
     * given document, optionally only hard or weak references, optionally
     * filtered by the referring PHPCR property name.
     *
     * Multilingual documents are loaded in the default locale, unless a locale
     * preference is explicitly specified.
     *
     * Note that this method only returns referrers that have been flushed.
     *
     * @param object       $document The target of the references to be loaded.
     * @param string|null  $type     The reference type, null|'weak'|'hard'.
     * @param string|null  $name     Optional PHPCR property name that holds the reference.
     * @param string       $locale   The locale to use during the loading of this collection.
     * @param string|null  $refClass Class the referrer document must be instanceof.
     *
     * @return ReferrersCollection collection of referrer documents
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function getReferrers($document, $type = null, $name = null, $locale = null, $refClass = null);

    /**
     * Gets a reference to the document identified by the given type and identifier
     * without actually loading it.
     *
     * If partial objects are allowed, this method will return a partial object that only
     * has its identifier populated. Otherwise a proxy is returned that automatically
     * loads itself on first access.
     *
     * @param string        $documentName
     * @param string|object $id
     *
     * @return mixed|object The document reference.
     */
    public function getReference($documentName, $id);

    /**
     * Create a new version of the document that has been previously persisted
     * and flushed.
     *
     * The state that is stored is the one from the last flush, not from the
     * current document state.
     *
     * The document is made read only until you call checkout again.
     *
     * @see checkpoint
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function checkin($document);

    /**
     * Make a checked in document writable again.
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function checkout($document);

    /**
     * Do a checkin operation followed immediately by a checkout operation.
     *
     * A new version is created and the writable document stays in checked out state
     *
     * @param object $document
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function checkpoint($document);

    /**
     * Restores the current checked out document to the values of the given
     * version in storage and refreshes the document object.
     *
     * Note that this does not change anything on the version history.
     *
     * The restore is immediately propagated to the backend.
     *
     * @see findVersionByName
     *
     * @param string $documentVersion The version to be restored.
     * @param bool   $removeExisting  How to handle conflicts with unique
     *      identifiers. If true, existing documents with the identical
     *      identifier will be replaced, otherwise an exception is thrown.
     */
    public function restoreVersion($documentVersion, $removeExisting = true);

    /**
     * Delete the specified version to clean up the history.
     *
     * Note that you can not remove the currently active version, only old
     * versions.
     *
     * @param object $documentVersion The version document as returned by findVersionByName.
     *
     * @throws \PHPCR\RepositoryException when trying to remove the root version or the last version
     */
    public function removeVersion($documentVersion);

    /**
     * Get the version history information for a document
     *
     * labels will be an empty array.
     *
     * @param object $document The document of which to get the version history.
     * @param int    $limit    An optional limit to only get the latest $limit information.
     *
     * @return array of <versionname> => array("name" => <versionname>, "labels" => <array of labels>, "created" => <DateTime>)
     *         oldest version first
     *
     * @throws InvalidArgumentException if $document is not an object.
     */
    public function getAllLinearVersions($document, $limit = -1);

    /**
     * Returns a read-only, detached document instance of the document at the
     * specified path with the specified version name.
     *
     * The id of the returned document representing this version is not the id
     * of the original document.
     *
     * @param null|string $className
     * @param string      $id          Id of the document.
     * @param string      $versionName The version name as given by getLinearPredecessors.
     *
     * @return object the detached document or null if the document is not found
     *
     * @throws InvalidArgumentException if there is a document with $id but no
     *      version with $name
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the implementation
     *      does not support versioning
     */
    public function findVersionByName($className, $id, $versionName);

    /**
     * Client code should not access the UnitOfWork except in special
     * circumstances. Methods on UnitOfWork might be changed without special
     * notice
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork();

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * This is different from the ObjectManager in that it accepts an optional
     * argument to limit the flush to one or more specific documents.
     *
     * @param object|array|null $document optionally limit to a specific
     *      document or an array of documents
     *
     * @return void
     *
     * @throws InvalidArgumentException if $document is neither null nor a
     *      document or an array of documents
     */
    public function flush($document = null);

    /**
     * Closes the DocumentManager. All entities that are currently managed
     * by this DocumentManager become detached. The DocumentManager may no longer
     * be used after it is closed.
     */
    public function close();

    /**
     * Return the node of the given object
     *
     * @param object $document
     *
     * @return \PHPCR\NodeInterface
     *
     * @throws InvalidArgumentException if $document is not an object.
     * @throws PHPCRException                if $document is not managed
     */
    public function getNodeForDocument($document);
}
