<?php

namespace Doctrine\ODM\PHPCR\Decorator;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\ChildrenCollection;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Proxy\ProxyFactory;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\ReferrersCollection;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\Persistence\ObjectManagerDecorator;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Query\QueryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\QOM\QueryBuilder as PhpcrQueryBuilder;

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

    public function __construct(DocumentManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function setTranslationStrategy(string $key, TranslationStrategyInterface $strategy): void
    {
        $this->wrapped->setTranslationStrategy($key, $strategy);
    }

    public function getTranslationStrategy(string $key): TranslationStrategyInterface
    {
        return $this->wrapped->getTranslationStrategy($key);
    }

    public function hasLocaleChooserStrategy(): bool
    {
        return $this->wrapped->hasLocaleChooserStrategy();
    }

    public function getLocaleChooserStrategy(): LocaleChooserInterface
    {
        return $this->wrapped->getLocaleChooserStrategy();
    }

    public function setLocaleChooserStrategy(LocaleChooserInterface $strategy): void
    {
        $this->wrapped->setLocaleChooserStrategy($strategy);
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->wrapped->getProxyFactory();
    }

    public function getEventManager(): EventManager
    {
        return $this->wrapped->getEventManager();
    }

    public function getPhpcrSession(): SessionInterface
    {
        return $this->wrapped->getPhpcrSession();
    }

    public function getConfiguration(): Configuration
    {
        return $this->wrapped->getConfiguration();
    }

    public function isOpen(): bool
    {
        return $this->wrapped->isOpen();
    }

    public function findMany(?string $className, array $ids): Collection
    {
        return $this->wrapped->findMany($className, $ids);
    }

    public function findTranslation(?string $className, string $id, string $locale, bool $fallback = true): ?object
    {
        return $this->wrapped->findTranslation($className, $id, $locale, $fallback);
    }

    public function quote(string $val, int $type = PropertyType::STRING): string
    {
        return $this->wrapped->quote($val, $type);
    }

    public function escapeFullText(string $string): string
    {
        return $this->wrapped->escapeFullText($string);
    }

    public function createPhpcrQuery(string $statement, string $language): QueryInterface
    {
        return $this->wrapped->createPhpcrQuery($statement, $language);
    }

    public function createQuery(string $statement, string $language): Query
    {
        return $this->wrapped->createQuery($statement, $language);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->wrapped->createQueryBuilder();
    }

    public function createPhpcrQueryBuilder(): PhpcrQueryBuilder
    {
        return $this->wrapped->createPhpcrQueryBuilder();
    }

    public function getDocumentsByPhpcrQuery(QueryInterface $query, ?string $className = null, ?string $primarySelector = null): Collection
    {
        return $this->wrapped->getDocumentsByPhpcrQuery($query, $className, $primarySelector);
    }

    public function bindTranslation(object $document, string $locale): void
    {
        $this->wrapped->bindTranslation($document, $locale);
    }

    public function removeTranslation(object $document, string $locale): void
    {
        $this->wrapped->removeTranslation($document, $locale);
    }

    public function getLocalesFor(object $document, bool $includeFallbacks): array
    {
        return $this->wrapped->getLocalesFor($document, $includeFallbacks);
    }

    public function isDocumentTranslatable(object $document): bool
    {
        return $this->wrapped->isDocumentTranslatable($document);
    }

    public function move(object $document, string $targetPath): void
    {
        $this->wrapped->move($document, $targetPath);
    }

    public function reorder(object $document, string $srcName, string $targetName, bool $before): void
    {
        $this->wrapped->reorder($document, $srcName, $targetName, $before);
    }

    public function getChildren(object $document, $filter = null, int $fetchDepth = -1, ?string $locale = null): ChildrenCollection
    {
        return $this->wrapped->getChildren($document, $filter, $fetchDepth, $locale);
    }

    public function getReferrers(object $document, ?string $type = null, ?string $name = null, ?string $locale = null, ?string $refClass = null): ReferrersCollection
    {
        return $this->wrapped->getReferrers($document, $type, $name, $locale, $refClass);
    }

    public function flush($document = null): void
    {
        $this->wrapped->flush($document);
    }

    public function getReference(string $documentName, $id)
    {
        return $this->wrapped->getReference($documentName, $id);
    }

    public function checkin(object $document): void
    {
        $this->wrapped->checkin($document);
    }

    public function checkout(object $document): void
    {
        $this->wrapped->checkout($document);
    }

    public function checkpoint(object $document): void
    {
        $this->wrapped->checkpoint($document);
    }

    public function restoreVersion(object $documentVersion, bool $removeExisting = true): void
    {
        $this->wrapped->restoreVersion($documentVersion, $removeExisting);
    }

    public function removeVersion(object $documentVersion): void
    {
        $this->wrapped->removeVersion($documentVersion);
    }

    public function getAllLinearVersions(object $document, int $limit = -1): array
    {
        return $this->wrapped->getAllLinearVersions($document, $limit);
    }

    public function findVersionByName(?string $className, string $id, string $versionName): ?object
    {
        return $this->wrapped->findVersionByName($className, $id, $versionName);
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->wrapped->getUnitOfWork();
    }

    public function close(): void
    {
        $this->wrapped->close();
    }

    public function getNodeForDocument(object $document): NodeInterface
    {
        return $this->wrapped->getNodeForDocument($document);
    }

    public function getDocumentId(object $document): string
    {
        return $this->wrapped->getDocumentId($document);
    }
}
