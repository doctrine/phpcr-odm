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

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Persistent collection class.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class PersistentCollection implements Collection
{
    const INITIALIZED_NONE = 'not initialized';
    const INITIALIZED_FROM_COLLECTION = 'initialized from collection';
    const INITIALIZED_FROM_COLLECTION_FORCE = 'initialized from collection to force a new db state';
    const INITIALIZED_FROM_PHPCR = 'initialized from phpcr';

    /** @var ArrayCollection */
    protected $collection;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var bool
     */
    protected $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     *
     * @var string
     */
    protected $initialized = self::INITIALIZED_NONE;

    /**
     * @var DocumentManagerInterface
     */
    protected $dm;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @return bool Whether the collection was modified
     */
    public function changed()
    {
        return $this->isDirty;
    }

    /**
     * Set the collection not dirty.
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

    /** {@inheritdoc} */
    public function add($element)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->add($element);
    }

    /** {@inheritdoc} */
    public function clear()
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->clear();
    }

    /** {@inheritdoc} */
    public function contains($element)
    {
        $this->initialize();

        return $this->collection->contains($element);
    }

    /** {@inheritdoc} */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->collection->containsKey($key);
    }

    /** {@inheritdoc} */
    public function count()
    {
        $this->initialize();

        return $this->collection->count();
    }

    /** {@inheritdoc} */
    public function current()
    {
        $this->initialize();

        return $this->collection->current();
    }

    /** {@inheritdoc} */
    public function exists(Closure $p)
    {
        $this->initialize();

        return $this->collection->exists($p);
    }

    /** {@inheritdoc} */
    public function filter(Closure $p)
    {
        $this->initialize();

        return $this->collection->filter($p);
    }

    /** {@inheritdoc} */
    public function first()
    {
        $this->initialize();

        return $this->collection->first();
    }

    /** {@inheritdoc} */
    public function forAll(Closure $p)
    {
        $this->initialize();

        return $this->collection->forAll($p);
    }

    /** {@inheritdoc} */
    public function get($key)
    {
        $this->initialize();

        return $this->collection->get($key);
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        $this->initialize();

        return $this->collection->getIterator();
    }

    /** {@inheritdoc} */
    public function getKeys()
    {
        $this->initialize();

        return $this->collection->getKeys();
    }

    /** {@inheritdoc} */
    public function getValues()
    {
        $this->initialize();

        return $this->collection->getValues();
    }

    /** {@inheritdoc} */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->collection->indexOf($element);
    }

    /** {@inheritdoc} */
    public function isEmpty()
    {
        $this->initialize();

        return $this->collection->isEmpty();
    }

    /** {@inheritdoc} */
    public function key()
    {
        $this->initialize();

        return $this->collection->key();
    }

    /** {@inheritdoc} */
    public function last()
    {
        $this->initialize();

        return $this->collection->last();
    }

    /** {@inheritdoc} */
    public function map(Closure $func)
    {
        $this->initialize();

        return $this->collection->map($func);
    }

    /** {@inheritdoc} */
    public function next()
    {
        $this->initialize();

        return $this->collection->next();
    }

    /** {@inheritdoc} */
    public function offsetExists($offset)
    {
        $this->initialize();

        return $this->collection->offsetExists($offset);
    }

    /** {@inheritdoc} */
    public function offsetGet($offset)
    {
        $this->initialize();

        return $this->collection->offsetGet($offset);
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->offsetSet($offset, $value);
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->offsetUnset($offset);
    }

    /** {@inheritdoc} */
    public function partition(Closure $p)
    {
        $this->initialize();

        return $this->collection->partition($p);
    }

    /** {@inheritdoc} */
    public function remove($key)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->remove($key);
    }

    /** {@inheritdoc} */
    public function removeElement($element)
    {
        $this->initialize();
        $this->isDirty = true;

        return $this->collection->removeElement($element);
    }

    /** {@inheritdoc} */
    public function set($key, $value)
    {
        $this->initialize();
        $this->isDirty = true;
        $this->collection->set($key, $value);
    }

    /** {@inheritdoc} */
    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->collection->slice($offset, $length);
    }

    /** {@inheritdoc} */
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
        return __CLASS__.'@'.spl_object_hash($this);
    }

    /**
     * Refresh the collection form the database, all local changes are lost.
     */
    public function refresh()
    {
        $this->initialized = self::INITIALIZED_NONE;
        $this->initialize();
    }

    /**
     * Checks whether this collection has been initialized.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return self::INITIALIZED_NONE !== $this->initialized;
    }

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return bool TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty()
    {
        return $this->isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param bool $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty)
    {
        $this->isDirty = $dirty;
    }

    /**
     * Set the default locale for this collection.
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param array|Collection $collection     The collection to initialize with
     * @param bool             $forceOverwrite If to force the database to be forced to the state of the collection
     */
    protected function initializeFromCollection($collection, $forceOverwrite = false)
    {
        $this->collection = is_array($collection) ? new ArrayCollection($collection) : $collection;
        $this->initialized = $forceOverwrite ? self::INITIALIZED_FROM_COLLECTION_FORCE : self::INITIALIZED_FROM_COLLECTION;
        $this->isDirty = true;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    abstract public function initialize();
}
