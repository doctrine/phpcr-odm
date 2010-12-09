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
class PersistentPathCollection extends PersistentCollection
{
    private $documentName;
    private $dm;
    private $paths;
    public $isInitialized = false;

    public function __construct(Collection $collection, $documentName, DocumentManager $dm, $paths)
    {
        $this->col = $collection;
        $this->documentName = $documentName;
        $this->dm = $dm;
        $this->paths = $paths;
        $this->isInitialized = (count($paths) == 0);
    }

    protected function load()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;

            $elements = $this->col->toArray();

            $repository = $this->dm->getRepository($this->documentName);
            $objects = $repository->findMany($this->paths);
            foreach ($objects as $object) {
                $this->col->add($object);
            }
            foreach ($elements as $object) {
                $this->col->add($object);
            }
        }
    }
}