<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

/**
 * Class which converts a Builder tree to a PHPCR Query
 */
class BuilderConverterPhpcr
{
    protected $qomf;
    protected $mdf;

    protected $selectorMetadata;

    public function __construct(ClassMetadataFactory $mdf, QueryObjectModelFactoryInterface $qomf)
    {
        $this->qomf = $qomf;
        $this->mdf = $mdf;
    }

    protected function getMetadata($selectorName)
    {
        if (!isset($this->selectorMetadata[$selectorName])) {
            throw new \RuntimeException(sprintf(
                'Selector name "%s" has not known. The following selectors'.
                'Are valid: "%s"',
                implode(array_keys($this->selectorMetadata))
            ));
        }

        return $this->selectorMetadata[$selectorName];
    }

    protected function getFieldMapping($selectorName, $propertyName)
    {
        $fieldMeta = $this->getMetadata(
            $property->getSelectorName()
        )->getField($property->getPropertyName());

        return $fieldMeta;
    }

    public function getQuery(Builder $builder)
    {
        $from = $builder->getChildrenOfType('From');

        if (!$from) {
            throw new \RuntimeException(
                'No From (source) node in query'
            );
        }

        // dispatch From first
        $this->dispatchMany($builder->getChildrenOfType('From'));

        // dispatch everything else
        $this->dispatchMany($builder->getChildrenOfType('Select'));
    }

    protected function dispatchMany($nodes)
    {
        foreach ($nodes as $node) {
            $this->dispatch($node);
        }
    }

    protected function dispatch(AbstractNode $node)
    {
        $methodName = sprintf('walk%s', $node->getName());

        if (!method_exists($this, $methodName)) {
            throw new \InvalidArgumentException(sprintf(
                'Do not know how to walk node of type "%s"',
                $node->getName()
            ));
        }

        $this->$methodName($node, $res);

        return $res;
    }

    public function walkSelect($node)
    {
        foreach ($node->getChildren() as $property) {
            $mapping = $this->getFieldMapping(
                $proprety->getSelectorName(),
                $property->getPropertyName()
            );

            $this->columns[] = $this->qomf->column(
                // do we want to support custom column names in ODM?
                $mapping['property'],
                $mapping['property']
            );
        }
    }

    public function walkFrom(AbstractNode $node)
    {
        $res = $this->walk($constraint);
        $this->from = $res;
    }

    public function walkSourceDocument(SourceDocument $node)
    {
        // make sure we add the phpcr:{class,classparents} constraints
        // From is dispatched first, so these will always be the primary
        // constraints.
        $this->constraints[] = $this->qomf->orConstraint(
            $this->qomf->comparison(
                $this->qomf->propertyValue('phpcr:class'),
                $this->qomf->literal($node->getDocumentFqn())
            ),
            $this->qomf->comparison(
                $this->qomf->propertyValue('phpcr:classparents'),
                $this->qomf->literal($node->getDocumentFqn())
            )
        );

        // index the metadata for this document
        $meta = $this->mdf->getMetadataFor($node->getDocumentFqn());
        $this->selectorMetadata[$node->getSelectorName()] = $meta;

        // get the PHPCR Selector
        $selector = $this->qomf->selector(
            'nt:unstructured', 
            $meta->getNodeType()
        );

        return $selector;
    }

    public function walkSourceJoin(SourceJoin $node, $context)
    {
    }
}
