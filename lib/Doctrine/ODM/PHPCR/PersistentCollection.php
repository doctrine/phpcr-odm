<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class PersistentCollection implements Collection
{
    protected const INITIALIZED_NONE = 'not initialized';

    protected const INITIALIZED_FROM_COLLECTION = 'initialized from collection';

    protected const INITIALIZED_FROM_COLLECTION_FORCE = 'initialized from collection to force a new db state';

    protected const INITIALIZED_FROM_PHPCR = 'initialized from phpcr';

    protected ?Collection $collection = null;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     */
    protected bool $isDirty = false;

    /**
     * Initialization status, one of the INITIALIZED_* constants.
     */
    protected string $initialized = self::INITIALIZED_NONE;

    protected DocumentManagerInterface $dm;

    protected ?string $locale = null;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    public function changed(): bool
    {
        return $this->isDirty;
    }

    public function takeSnapshot(): void
    {
        $this->isDirty = false;
    }

    public function unwrap(): Collection
    {
        if ($this->collection instanceof Collection) {
            return $this->collection;
        }

        return new ArrayCollection();
    }

    public function add($element): bool
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->add($element);
    }

    public function clear(): void
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->clear();
    }

    public function contains($element): bool
    {
        $this->initialize();

        return $this->collection->contains($element);
    }

    public function containsKey($key): bool
    {
        $this->initialize();

        return $this->collection->containsKey($key);
    }

    public function count(): int
    {
        $this->initialize();

        return $this->collection->count();
    }

    public function current()
    {
        $this->initialize();

        return $this->collection->current();
    }

    public function exists(\Closure $p): bool
    {
        $this->initialize();

        return $this->collection->exists($p);
    }

    public function filter(\Closure $p): Collection
    {
        $this->initialize();

        return $this->collection->filter($p);
    }

    public function first()
    {
        $this->initialize();

        return $this->collection->first();
    }

    public function forAll(\Closure $p): bool
    {
        $this->initialize();

        return $this->collection->forAll($p);
    }

    public function get($key)
    {
        $this->initialize();

        return $this->collection->get($key);
    }

    public function getIterator(): \Traversable
    {
        $this->initialize();

        return $this->collection->getIterator();
    }

    public function getKeys(): array
    {
        $this->initialize();

        return $this->collection->getKeys();
    }

    public function getValues(): array
    {
        $this->initialize();

        return $this->collection->getValues();
    }

    public function indexOf($element)
    {
        $this->initialize();

        return $this->collection->indexOf($element);
    }

    public function isEmpty(): bool
    {
        $this->initialize();

        return $this->collection->isEmpty();
    }

    public function key()
    {
        $this->initialize();

        return $this->collection->key();
    }

    public function last()
    {
        $this->initialize();

        return $this->collection->last();
    }

    public function map(\Closure $func): Collection
    {
        $this->initialize();

        return $this->collection->map($func);
    }

    public function next()
    {
        $this->initialize();

        return $this->collection->next();
    }

    public function offsetExists($offset): bool
    {
        $this->initialize();

        return $this->collection->offsetExists($offset);
    }

    #[\ReturnTypeWillChange] // type mixed is not available for older php versions
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->collection->offsetGet($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->initialize();
        $this->isDirty = true;

        $this->collection->offsetSet($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->initialize();
        $this->isDirty = true;

        $this->collection->offsetUnset($offset);
    }

    public function partition(\Closure $p): array
    {
        $this->initialize();

        return $this->collection->partition($p);
    }

    public function remove($key)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->remove($key);
    }

    public function removeElement($element): bool
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->removeElement($element);
    }

    public function set($key, $value): void
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->set($key, $value);
    }

    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->collection->slice($offset, $length);
    }

    public function toArray(): array
    {
        $this->initialize();

        return $this->collection->toArray();
    }

    public function __toString(): string
    {
        return __CLASS__.'@'.spl_object_hash($this);
    }

    /**
     * Refresh the collection form the database, all local changes are lost.
     */
    public function refresh(): void
    {
        $this->initialized = self::INITIALIZED_NONE;
        $this->initialize();
    }

    public function isInitialized(): bool
    {
        return self::INITIALIZED_NONE !== $this->initialized;
    }

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     */
    public function isDirty(): bool
    {
        return $this->isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param bool $dirty whether the collection should be marked dirty or not
     */
    public function setDirty(bool $dirty): void
    {
        $this->isDirty = $dirty;
    }

    /**
     * Set the default locale for this collection.
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @param array|Collection $collection     The collection to initialize with
     * @param bool             $forceOverwrite If to force the database to be forced to the state of the collection
     */
    protected function initializeFromCollection($collection, bool $forceOverwrite = false): void
    {
        $this->collection = is_array($collection) ? new ArrayCollection($collection) : $collection;
        $this->initialized = $forceOverwrite ? self::INITIALIZED_FROM_COLLECTION_FORCE : self::INITIALIZED_FROM_COLLECTION;
        $this->isDirty = true;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    abstract public function initialize(): void;
}
