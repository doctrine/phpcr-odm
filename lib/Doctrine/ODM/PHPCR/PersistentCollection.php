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

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Closure;

/**
 * Persistent collection class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class PersistentCollection implements Collection
{
    /** @var ArrayCollection */
    protected $collection;

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
     * @return bool Whether the collection was modified
     */
    public function changed()
    {
        return $this->isDirty;
    }

    /**
     * Set the collection not dirty
     */
    public function takeSnapshot()
    {
        $this->isDirty = false;
    }

    /**
     * @return ArrayCollection The collection
     */
    public function unwrap()
    {
        if ($this->collection instanceof Collection) {
            return $this->collection;
        }

        return new ArrayCollection();
    }

    /** {@inheritDoc} */
    public function add($element)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->add($element);
    }

    /** {@inheritDoc} */
    public function clear()
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->clear();
    }

    /** {@inheritDoc} */
    public function contains($element)
    {
        $this->initialize();

        return $this->collection->contains($element);
    }

    /** {@inheritDoc} */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->collection->containsKey($key);
    }

    /** {@inheritDoc} */
    public function count()
    {
        $this->initialize();

        return $this->collection->count();
    }

    /** {@inheritDoc} */
    public function current()
    {
        $this->initialize();

        return $this->collection->current();
    }

    /** {@inheritDoc} */
    public function exists(Closure $p)
    {
        $this->initialize();

        return $this->collection->exists($p);
    }

    /** {@inheritDoc} */
    public function filter(Closure $p)
    {
        $this->initialize();

        return $this->collection->filter($p);
    }

    /** {@inheritDoc} */
    public function first()
    {
        $this->initialize();

        return $this->collection->first();
    }

    /** {@inheritDoc} */
    public function forAll(Closure $p)
    {
        $this->initialize();

        return $this->collection->forAll($p);
    }

    /** {@inheritDoc} */
    public function get($key)
    {
        $this->initialize();

        return $this->collection->get($key);
    }

    /** {@inheritDoc} */
    public function getIterator()
    {
        $this->initialize();

        return $this->collection->getIterator();
    }

    /** {@inheritDoc} */
    public function getKeys()
    {
        $this->initialize();

        return $this->collection->getKeys();
    }

    /** {@inheritDoc} */
    public function getValues()
    {
        $this->initialize();

        return $this->collection->getValues();
    }

    /** {@inheritDoc} */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->collection->indexOf($element);
    }

    /** {@inheritDoc} */
    public function isEmpty()
    {
        $this->initialize();

        return $this->collection->isEmpty();
    }

    /** {@inheritDoc} */
    public function key()
    {
        $this->initialize();

        return $this->collection->key();
    }

    /** {@inheritDoc} */
    public function last()
    {
        $this->initialize();

        return $this->collection->last();
    }

    /** {@inheritDoc} */
    public function map(Closure $func)
    {
        $this->initialize();

        return $this->collection->map($func);
    }

    /** {@inheritDoc} */
    public function next()
    {
        $this->initialize();

        return $this->collection->next();
    }

    /** {@inheritDoc} */
    public function offsetExists($offset)
    {
        $this->initialize();

        return $this->collection->offsetExists($offset);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->collection->offsetGet($offset);
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->offsetSet($offset, $value);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->offsetUnset($offset);
    }

    /** {@inheritDoc} */
    public function partition(Closure $p)
    {
        $this->initialize();

        return $this->collection->partition($p);
    }

    /** {@inheritDoc} */
    public function remove($key)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->remove($key);
    }

    /** {@inheritDoc} */
    public function removeElement($element)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->removeElement($element);
    }

    /** {@inheritDoc} */
    public function set($key, $value)
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->set($key, $value);
    }

    /** {@inheritDoc} */
    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->collection->slice($offset, $length);
    }

    /** {@inheritDoc} */
    public function toArray()
    {
        $this->initialize();

        return $this->collection->toArray();
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
     * Sets the initialized flag of the collection, forcing it into that state.
     *
     * @param boolean $bool
     */
    public function setInitialized($bool)
    {
        $this->initialized = $bool;
    }

    /**
     * Checks whether this collection has been initialized.
     *
     * @return boolean
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return boolean TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty()
    {
        return $this->isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param boolean $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty)
    {
        $this->isDirty = $dirty;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    abstract public function initialize();
}
