<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

class PhpcrWalker
{
    protected $columns = array();
    protected $source;
    protected $constraints = array();
    protected $orderings = array();

    protected $qomf;
    protected $cmf;

    public function __construct(ClassMetadataFactory $cmf, QueryObjectModelFactoryInterface $qomf)
    {
        $this->qomf = $qomf;
        $this->cmf = $cmf;
    }

    public function walk(AbstractNode $node, $res)
    {
        $methodName = sprintf('walk%s', $node->getName());

        if (!method_exists($this, $methodName)) {
            throw new \InvalidArgumentException(sprintf(
                'Do not know how to walk node of type "%s"',
                $node->getName()
            ));
        }

        $res = $this->$methodName($node, $res);

        foreach ($node->getChildren() as $child) {
            $this->walk($child, $res);
        }
    }

    public function walkBuilder($node)
    {
        // do nothing
    }

    public function walkSelect($node)
    {
        foreach ($node->getChildren() as $property) {
            $this->columns[] = $this->qomf->column(
                // do we want to support custom column names in ODM?
                $property->getPropertyName(),
                $property->getPropertyName(), 
                $property->getSelectorName()
            );
        }
    }

    public function walkFrom(AbstractNode $node)
    {
        foreach ($node->getChildren() as $constraint) {
            $res = $this->walk($constraint);
        }
    }

    public function walkSourceDocument(SourceDocument $node, $context)
    {
        $meta = $this->cmf->getMetadataFor($node->getDocumentFqn());

        $document = $this->qomf->selector('nt:unstructured', $meta->getNodeType());

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
    }
}
