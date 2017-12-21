<?php

namespace Doctrine\ODM\PHPCR\Decorator;

use Doctrine\Common\Persistence\ObjectManagerDecorator;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use PHPCR\PropertyType;
use PHPCR\Query\QueryInterface;

/**
 * Base class for DocumentManager decorators.
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
     * {@inheritdoc}
     */
    public function setTranslationStrategy($key, TranslationStrategyInterface $strategy)
    {
        return $this->wrapped->setTranslationStrategy($key, $strategy);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationStrategy($key)
    {
        return $this->wrapped->getTranslationStrategy($key);
    }

    /**
     * {@inheritdoc}
     */
    public function hasLocaleChooserStrategy()
    {
        return $this->wrapped->hasLocaleChooserStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocaleChooserStrategy()
    {
        return $this->wrapped->getLocaleChooserStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy)
    {
        return $this->wrapped->setLocaleChooserStrategy($strategy);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory()
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventManager()
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpcrSession()
    {
        return $this->wrapped->getPhpcrSession();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->wrapped->getMetadataFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen()
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata($className)
    {
        return $this->wrapped->getClassMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    public function find($className, $id)
    {
        return $this->wrapped->find($className, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function findMany($className, array $ids)
    {
        return $this->wrapped->findMany($className, $ids);
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslation($className, $id, $locale, $fallback = true)
    {
        return $this->wrapped->findTranslation($className, $id, $locale, $fallback);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        return $this->wrapped->getRepository($className);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($val, $type = PropertyType::STRING)
    {
        return $this->wrapped->quote($val, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeFullText($string)
    {
        return $this->wrapped->escapeFullText($string);
    }

    /**
     * {@inheritdoc}
     */
    public function createPhpcrQuery($statement, $language)
    {
        return $this->wrapped->createPhpcrQuery($statement, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($statement, $language)
    {
        return $this->wrapped->createQuery($statement, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function createPhpcrQueryBuilder()
    {
        return $this->wrapped->createPhpcrQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentsByPhpcrQuery(QueryInterface $query, $className = null, $primarySelector = null)
    {
        return $this->wrapped->getDocumentsByPhpcrQuery($query, $className, $primarySelector);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document)
    {
        $this->wrapped->persist($document);
    }

    /**
     * {@inheritdoc}
     */
    public function bindTranslation($document, $locale)
    {
        return $this->wrapped->bindTranslation($document, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, $locale)
    {
        return $this->wrapped->removeTranslation($document, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, $includeFallbacks = false)
    {
        return $this->wrapped->getLocalesFor($document, $includeFallbacks);
    }

    /**
     * {@inheritdoc}
     */
    public function isDocumentTranslatable($document)
    {
        return $this->wrapped->isDocumentTranslatable($document);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $targetPath)
    {
        return $this->wrapped->move($document, $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $srcName, $targetName, $before)
    {
        return $this->wrapped->reorder($document, $srcName, $targetName, $before);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $this->wrapped->remove($document);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($document)
    {
        return $this->wrapped->merge($document);
    }

    /**
     * {@inheritdoc}
     */
    public function detach($document)
    {
        $this->wrapped->detach($document);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        $this->wrapped->refresh($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren($document, $filter = null, $fetchDepth = null, $locale = null)
    {
        return $this->wrapped->getChildren($document, $filter, $fetchDepth, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferrers($document, $type = null, $name = null, $locale = null, $refClass = null)
    {
        return $this->wrapped->getReferrers($document, $type, $name, $locale, $refClass);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($document = null)
    {
        $this->wrapped->flush($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($documentName, $id)
    {
        return $this->wrapped->getReference($documentName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function checkin($document)
    {
        return $this->wrapped->checkin($document);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout($document)
    {
        return $this->wrapped->checkout($document);
    }

    /**
     * {@inheritdoc}
     */
    public function checkpoint($document)
    {
        return $this->wrapped->checkpoint($document);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreVersion($documentVersion, $removeExisting = true)
    {
        return $this->wrapped->restoreVersion($documentVersion, $removeExisting);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVersion($documentVersion)
    {
        return $this->wrapped->removeVersion($documentVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLinearVersions($document, $limit = -1)
    {
        return $this->wrapped->getAllLinearVersions($document, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function findVersionByName($className, $id, $versionName)
    {
        return $this->wrapped->findVersionByName($className, $id, $versionName);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($document)
    {
        return $this->wrapped->contains($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function clear($className = null)
    {
        $this->wrapped->clear($className);
    }

    public function close()
    {
        return $this->wrapped->close();
    }

    /**
     * {@inheritdoc}
     */
    public function initializeObject($document)
    {
        $this->wrapped->initializeObject($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeForDocument($document)
    {
        return $this->wrapped->getNodeForDocument($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentId($document)
    {
        return $this->wrapped->getDocumentId($document);
    }
}
