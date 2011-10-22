<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Closure;

/**
 * Persistent collection class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class PersistentCollection implements Collection
{
    /** @var ArrayCollection */
    protected $coll;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    protected $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     *
     * @var boolean
     */
    protected $initialized = false;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @return  bool    Whether the collection was modified
     */
    public function changed()
    {
        return $this->isDirty;
    }

    /**
     * @return void
     */
    public function takeSnapshot()
    {
        $this->isDirty = false;
    }

    /**
     * @return ArrayCollection  The collection
     */
    public function unwrap()
    {
        return $this->coll;
    }

    /** {@inheritDoc} */
    public function add($element)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->add($element);
    }

    /** {@inheritDoc} */
    public function clear()
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->clear();
    }

    /** {@inheritDoc} */
    public function contains($element)
    {
        $this->initialize();
        return $this->coll->contains($element);
    }

    /** {@inheritDoc} */
    public function containsKey($key)
    {
        $this->initialize();
        return $this->coll->containsKey($key);
    }

    /** {@inheritDoc} */
    public function count()
    {
        $this->initialize();
        return $this->coll->count();
    }

    /** {@inheritDoc} */
    public function current()
    {
        $this->initialize();
        return $this->coll->current();
    }

    /** {@inheritDoc} */
    public function exists(Closure $p)
    {
        $this->initialize();
        return $this->coll->exists($p);
    }

    /** {@inheritDoc} */
    public function filter(Closure $p)
    {
        $this->initialize();
        return $this->coll->filter($p);
    }

    /** {@inheritDoc} */
    public function first()
    {
        $this->initialize();
        return $this->coll->first();
    }

    /** {@inheritDoc} */
    public function forAll(Closure $p)
    {
        $this->initialize();
        return $this->coll->forAll($p);
    }

    /** {@inheritDoc} */
    public function get($key)
    {
        $this->initialize();
        return $this->coll->get($key);
    }

    /** {@inheritDoc} */
    public function getIterator()
    {
        $this->initialize();
        return $this->coll->getIterator();
    }

    /** {@inheritDoc} */
    public function getKeys()
    {
        $this->initialize();
        return $this->coll->getKeys();
    }

    /** {@inheritDoc} */
    public function getValues()
    {
        $this->initialize();
        return $this->coll->getValues();
    }

    /** {@inheritDoc} */
    public function indexOf($element)
    {
        $this->initialize();
        return $this->coll->indexOf($element);
    }

    /** {@inheritDoc} */
    public function isEmpty()
    {
        $this->initialize();
        return $this->coll->isEmpty();
    }

    /** {@inheritDoc} */
    public function key()
    {
        $this->initialize();
        return $this->coll->key();
    }

    /** {@inheritDoc} */
    public function last()
    {
        $this->initialize();
        return $this->coll->last();
    }

    /** {@inheritDoc} */
    public function map(Closure $func)
    {
        $this->initialize();
        return $this->coll->map($func);
    }

    /** {@inheritDoc} */
    public function next()
    {
        $this->initialize();
        return $this->coll->next();
    }

    /** {@inheritDoc} */
    public function offsetExists($offset)
    {
        $this->initialize();
        return $this->coll->offsetExists($offset);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset)
    {
        $this->initialize();
        return $this->coll->offsetGet($offset);
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->offsetSet($offset, $value);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->offsetUnset($offset);
    }

    /** {@inheritDoc} */
    public function partition(Closure $p)
    {
        $this->initialize();
        return $this->coll->partition($p);
    }

    /** {@inheritDoc} */
    public function remove($key)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->remove($key);
    }

    /** {@inheritDoc} */
    public function removeElement($element)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->removeElement($element);
    }

    /** {@inheritDoc} */
    public function set($key, $value)
    {
        $this->initialize();
        $this->isDirty = true;
        return $this->coll->set($key, $value);
    }

    /** {@inheritDoc} */
    public function slice($offset, $length = null)
    {
        $this->initialize();
        return $this->coll->slice($offset, $length);
    }

    /** {@inheritDoc} */
    public function toArray()
    {
        $this->initialize();
        return $this->coll->toArray();
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    abstract public function initialize();
}
