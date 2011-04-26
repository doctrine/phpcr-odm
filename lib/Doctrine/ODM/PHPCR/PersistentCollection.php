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
    protected $col;
    protected $changed = false;

    /**
     * Loads the collection
     */
    abstract protected function load();

    /**
     * @return  bool    Whether the collection was modified
     */
    public function changed()
    {
        return $this->changed;
    }

    /**
     * @return void
     */
    public function takeSnapshot()
    {
        $this->changed = false;
    }

    /**
     * @return ArrayCollection  The collection
     */
    public function unwrap()
    {
        return $this->col;
    }

    /** {@inheritDoc} */
    public function add($element)
    {
        $this->load();
        $this->changed = true;
        return $this->col->add($element);
    }

    /** {@inheritDoc} */
    public function clear()
    {
        $this->load();
        $this->changed = true;
        return $this->col->clear();
    }

    /** {@inheritDoc} */
    public function contains($element)
    {
        $this->load();
        return $this->col->contains($element);
    }

    /** {@inheritDoc} */
    public function containsKey($key)
    {
        $this->load();
        return $this->col->containsKey($key);
    }

    /** {@inheritDoc} */
    public function count()
    {
        $this->load();
        return $this->col->count();
    }

    /** {@inheritDoc} */
    public function current()
    {
        $this->load();
        return $this->col->current();
    }

    /** {@inheritDoc} */
    public function exists(Closure $p)
    {
        $this->load();
        return $this->col->exists($p);
    }

    /** {@inheritDoc} */
    public function filter(Closure $p)
    {
        $this->load();
        return $this->col->filter($p);
    }

    /** {@inheritDoc} */
    public function first()
    {
        $this->load();
        return $this->col->first();
    }

    /** {@inheritDoc} */
    public function forAll(Closure $p)
    {
        $this->load();
        return $this->col->forAll($p);
    }

    /** {@inheritDoc} */
    public function get($key)
    {
        $this->load();
        return $this->col->get($key);
    }

    /** {@inheritDoc} */
    public function getIterator()
    {
        $this->load();
        return $this->col->getIterator();
    }

    /** {@inheritDoc} */
    public function getKeys()
    {
        $this->load();
        return $this->col->getKeys();
    }

    /** {@inheritDoc} */
    public function getValues()
    {
        $this->load();
        return $this->col->getValues();
    }

    /** {@inheritDoc} */
    public function indexOf($element)
    {
        $this->load();
        return $this->col->indexOf($element);
    }

    /** {@inheritDoc} */
    public function isEmpty()
    {
        $this->load();
        return $this->col->isEmpty();
    }

    /** {@inheritDoc} */
    public function key()
    {
        $this->load();
        return $this->col->key();
    }

    /** {@inheritDoc} */
    public function last()
    {
        $this->load();
        return $this->col->last();
    }

    /** {@inheritDoc} */
    public function map(Closure $func)
    {
        $this->load();
        return $this->col->map($func);
    }

    /** {@inheritDoc} */
    public function next()
    {
        $this->load();
        return $this->col->next();
    }

    /** {@inheritDoc} */
    public function offsetExists($offset)
    {
        $this->load();
        return $this->col->offsetExists($offset);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset)
    {
        $this->load();
        return $this->col->offsetGet($offset);
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value)
    {
        $this->load();
        $this->changed = true;
        return $this->col->offsetSet($offset, $value);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset)
    {
        $this->load();
        $this->changed = true;
        return $this->col->offsetUnset($offset);
    }

    /** {@inheritDoc} */
    public function partition(Closure $p)
    {
        $this->load();
        return $this->col->partition($p);
    }

    /** {@inheritDoc} */
    public function remove($key)
    {
        $this->load();
        $this->changed = true;
        return $this->col->remove($key);
    }

    /** {@inheritDoc} */
    public function removeElement($element)
    {
        $this->load();
        $this->changed = true;
        return $this->col->removeElement($element);
    }

    /** {@inheritDoc} */
    public function set($key, $value)
    {
        $this->load();
        $this->changed = true;
        return $this->col->set($key, $value);
    }

    /** {@inheritDoc} */
    public function slice($offset, $length = null)
    {
        $this->load();
        return $this->col->slice($offset, $length);
    }

    /** {@inheritDoc} */
    public function toArray()
    {
        $this->load();
        return $this->col->toArray();
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
}
