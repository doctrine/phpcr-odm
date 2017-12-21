<?php

namespace Doctrine\ODM\PHPCR\Tools\Console;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 *
 * Copied from Doctrine\ODM\PHPCR\Tools\Console\MetadataFilter
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class MetadataFilter extends \FilterIterator implements \Countable
{
    /**
     * @var array
     */
    private $filter = [];

    /**
     * Filter Metadatas by one or more filter options.
     *
     * @param array        $metadatas
     * @param array|string $filter
     *
     * @return array
     */
    public static function filter(array $metadatas, $filter)
    {
        $metadatas = new self(new \ArrayIterator($metadatas), $filter);

        return iterator_to_array($metadatas);
    }

    /**
     * @param \ArrayIterator $metadata
     * @param array|string   $filter
     */
    public function __construct(\ArrayIterator $metadata, $filter)
    {
        $this->filter = (array) $filter;

        parent::__construct($metadata);
    }

    /**
     * @return bool
     */
    public function accept()
    {
        if (count($this->filter) == 0) {
            return true;
        }

        $it = $this->getInnerIterator();
        $metadata = $it->current();

        foreach ($this->filter as $filter) {
            $pregResult = preg_match("/$filter/", $metadata->name);

            if ($pregResult === false) {
                throw new \RuntimeException(
                    sprintf("Error while evaluating regex '/%s/'.", $filter)
                );
            }

            if ($pregResult === 0) {
                return false;
            }

            if ($pregResult) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getInnerIterator());
    }
}
