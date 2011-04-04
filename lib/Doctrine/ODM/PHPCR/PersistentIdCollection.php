<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;

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
    private $documentName;
    private $dm;
    private $ids;
    public $isInitialized = false;

    public function __construct(Collection $collection, $documentName, DocumentManager $dm, $ids)
    {
        $this->col = $collection;
        $this->documentName = $documentName;
        $this->dm = $dm;
        $this->ids = $ids;
        $this->isInitialized = (count($ids) == 0);
    }

    protected function load()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;

            $elements = $this->col->toArray();

            $repository = $this->dm->getRepository($this->documentName);
            $objects = $repository->findMany($this->ids);
            foreach ($objects as $object) {
                $this->col->add($object);
            }
            foreach ($elements as $object) {
                $this->col->add($object);
            }
        }
    }
}
