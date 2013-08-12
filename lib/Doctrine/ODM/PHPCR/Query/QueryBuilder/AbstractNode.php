<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

abstract class AbstractNode
{
    protected $children;
    protected $parent;

    public function getName()
    {
        $refl = new \ReflectionClass($this);
        $short = $refl->getShortName();

        return $short;
    }

    abstract public function getCardinalityMap();

    public function addChild(AbstractNode $node)
    {
        $cardinalities = $this->getCardinalityMap();

        $validChild = true;
        $unbounded = false;
        $end = false;

        if (isset($cardinalities[$node->getName()])) {
            $currentCardinality = isset($this->children[$node->getName()]) ? 
                count($this->children[$node->getName()]) : 0;
            list($min, $max) = $cardinalities[$node->getName()];
            if (null !== $max) {
                $unbounded = true;
            }
        } else {
            $validChild = false;
        }

        // determine end criteria
        if (true === $unbounded && false === $validChild) {
            $end = true;
            $this->validateEnd();
        } else {
            if (false === $validChild) {
                throw new \Exception(sprintf(
                    'QueryBuilder node "%s" cannot be appended to "%s". '.
                    'Must be one of "%s"',
                    $node->getName(),
                    $this->getName(),
                    implode(', ', array_keys($cardinalities))
                ));
            }

            if (($currentCardinality + 1) > $max) {
                throw new \OutOfBoundsException(sprintf(
                    'QueryBuilder node "%s" cannot be appended to "%s"'.
                    'Number of "%s" nodes cannot exceed "%s"',
                    $node->getName(),
                    $this->getName(),
                    $node->getName(),
                    $max
                ));
            }
        }

        if (true === $end) {
            $this->parent->addChild($node);
            return $this->parent;
        } else {
            $this->children[$node->getName()][] = $node;
            return $this;
        }
    }

    protected function validateEnd()
    {
        $cardinalities = $this->getCardinalityMap();
        list($min, $max) = $cardinalities[$node->getName()];
        $currentCardinality = count($this->children[$node->getName()]);

        if ($currentCardinality < $min) {
            throw new \Exception(
                'QueryBuilder node "%s" cannot be appended to "%s"'.
                'Number of nodes for "%s" cannot be less than "%d"',
                $node->getName(),
                $this->getName(),
                $node->getName(),
                $min
            );
        }
    }
}
