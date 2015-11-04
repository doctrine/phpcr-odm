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

namespace Doctrine\ODM\PHPCR\Decorator;

use Doctrine\Common\Persistence\ObjectManagerDecorator;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use PHPCR\PropertyType;
use PHPCR\Query\QueryInterface;

/**
 * Base class for DocumentManager decorators
 *
 * @since 1.3
 */
abstract class DocumentManagerDecorator extends ObjectManagerDecorator implements DocumentManagerInterface
{
    /**
     * @var DocumentManagerInterface
     */
    protected $wrapped;

    /**
     * @param DocumentManagerInterface $wrapped
     */
    public function __construct(DocumentManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritDoc}
     */
    public function setTranslationStrategy($key, TranslationStrategyInterface $strategy)
    {
        return $this->wrapped->setTranslationStrategy($key, $strategy);
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslationStrategy($key)
    {
        return $this->wrapped->getTranslationStrategy($key);
    }

    /**
     * {@inheritDoc}
     */
    public function hasLocaleChooserStrategy()
    {
        return $this->wrapped->hasLocaleChooserStrategy();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleChooserStrategy()
    {
        return $this->wrapped->getLocaleChooserStrategy();
    }

    /**
     * {@inheritDoc}
     */
    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy)
    {
        return $this->wrapped->setLocaleChooserStrategy($strategy);
    }

    /**
     * {@inheritDoc}
     */
    public function getProxyFactory()
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritDoc}
     */
    public function getPhpcrSession()
    {
        return $this->wrapped->getPhpcrSession();
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataFactory()
    {
        return $this->wrapped->getMetadataFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function isOpen()
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className)
    {
        return $this->wrapped->getClassMetadata($className);
    }

    /**
     * {@inheritDoc}
     */
    public function find($className, $id)
    {
        return $this->wrapped->find($className, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function findMany($className, array $ids)
    {
        return $this->wrapped->findMany($className, $ids);
    }

    /**
     * {@inheritDoc}
     */
    public function findTranslation($className, $id, $locale, $fallback = true)
    {
        return $this->wrapped->findTranslation($className, $id, $locale, $fallback);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository($className)
    {
        return $this->wrapped->getRepository($className);
    }

    /**
     * {@inheritDoc}
     */
    public function quote($val, $type = PropertyType::STRING)
    {
        return $this->wrapped->quote($val, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function escapeFullText($string)
    {
        return $this->wrapped->escapeFullText($string);
    }

    /**
     * {@inheritDoc}
     */
    public function createPhpcrQuery($statement, $language)
    {
        return $this->wrapped->createPhpcrQuery($statement, $language);
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($statement, $language)
    {
        return $this->wrapped->createQuery($statement, $language);
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function createPhpcrQueryBuilder()
    {
        return $this->wrapped->createPhpcrQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentsByPhpcrQuery(QueryInterface $query, $className = null, $primarySelector = null)
    {
        return $this->wrapped->getDocumentsByPhpcrQuery($query, $className, $primarySelector);
    }

    /**
     * {@inheritDoc}
     */
    public function persist($document)
    {
        $this->wrapped->persist($document);
    }

    /**
     * {@inheritDoc}
     */
    public function bindTranslation($document, $locale)
    {
        return $this->wrapped->bindTranslation($document, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function removeTranslation($document, $locale)
    {
        return $this->wrapped->removeTranslation($document, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalesFor($document, $includeFallbacks = false)
    {
        return $this->wrapped->getLocalesFor($document, $includeFallbacks);
    }

    /**
     * {@inheritDoc}
     */
    public function isDocumentTranslatable($document)
    {
        return $this->wrapped->isDocumentTranslatable($document);
    }

    /**
     * {@inheritDoc}
     */
    public function move($document, $targetPath)
    {
        return $this->wrapped->move($document, $targetPath);
    }

    /**
     * {@inheritDoc}
     */
    public function reorder($document, $srcName, $targetName, $before)
    {
        return $this->wrapped->reorder($document, $srcName, $targetName, $before);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($document)
    {
        $this->wrapped->remove($document);
    }

    /**
     * {@inheritDoc}
     */
    public function merge($document)
    {
        return $this->wrapped->merge($document);
    }

    /**
     * {@inheritDoc}
     */
    public function detach($document)
    {
        $this->wrapped->detach($document);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($document)
    {
        $this->wrapped->refresh($document);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren($document, $filter = null, $fetchDepth = null, $locale = null)
    {
        return $this->wrapped->getChildren($document, $filter, $fetchDepth, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getReferrers($document, $type = null, $name = null, $locale = null, $refClass = null)
    {
        return $this->wrapped->getReferrers($document, $type, $name, $locale, $refClass);
    }

    /**
     * {@inheritDoc}
     */
    public function flush($document = null)
    {
        $this->wrapped->flush($document);
    }

    /**
     * {@inheritDoc}
     */
    public function getReference($documentName, $id)
    {
        return $this->wrapped->getReference($documentName, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function checkin($document)
    {
        return $this->wrapped->checkin($document);
    }

    /**
     * {@inheritDoc}
     */
    public function checkout($document)
    {
        return $this->wrapped->checkout($document);
    }

    /**
     * {@inheritDoc}
     */
    public function checkpoint($document)
    {
        return $this->wrapped->checkpoint($document);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreVersion($documentVersion, $removeExisting = true)
    {
        return $this->wrapped->restoreVersion($documentVersion, $removeExisting);
    }

    /**
     * {@inheritDoc}
     */
    public function removeVersion($documentVersion)
    {
        return $this->wrapped->removeVersion($documentVersion);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        return $this->wrapped->getAllLinearVersions($document, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function findVersionByName($className, $id, $versionName)
    {
        return $this->wrapped->findVersionByName($className, $id, $versionName);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($document)
    {
        return $this->wrapped->contains($document);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWork()
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritDoc}
     */
    public function clear($className = null)
    {
        $this->wrapped->clear($className);
    }

    /**
     */
    public function close()
    {
        return $this->wrapped->close();
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($document)
    {
        $this->wrapped->initializeObject($document);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeForDocument($document)
    {
        return $this->wrapped->getNodeForDocument($document);
    }
}
