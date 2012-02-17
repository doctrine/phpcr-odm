<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Persistent collection class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 */
class PersistentIdCollection extends PersistentCollection
{
    /** @var array */
    private $ids;

    public function __construct(DocumentManager $dm, Collection $collection, ClassMetadata $class,  array $ids)
    {
        $this->dm = $dm;
        $this->collection = $collection;
        $this->class = $class;
        $this->ids = $ids;

        $this->initialized = (count($ids) == 0);
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            $elements = $this->collection->toArray();

            $repository = $this->dm->getRepository($this->class->getName());
            $objects = $repository->findMany($this->ids);
            foreach ($objects as $object) {
                $this->collection->add($object);
            }
            foreach ($elements as $object) {
                $this->collection->add($object);
            }
        }
    }
}

