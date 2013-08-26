<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OperandDynamicFactory extends AbstractNode
{
    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC_FACTORY;
    }

    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_DYNAMIC => array(1, 1),
        );
    }

    public function fullTextSearchScore($selectorName)
    {
        return $this->addChild(new OperandDynamicFullTextSearchScore($this, $selectorName));
    }

    public function length($selectorName, $propertyName)
    {
        return $this->addChild(new OperandDynamicPropertyValue($this, $selectorName, $selectorName));
    }

    public function lowerCase()
    {
        return $this->addChild(new OperandDynamicLowerCase($this));
    }

    public function upperCase()
    {
        return $this->addChild(new OperandDynamicUpperCase($this));
    }

    public function documentLocalName($selectorName)
    {
        return $this->addChild(new OperandDynamicDocumentLocalName($this, $selectorName));
    }

    public function documentName($selectorName)
    {
        return $this->addChild(new OperandDynamicDocumentName($this, $selectorName));
    }

    public function propertyValue($selectorName, $propertyName)
    {
        return $this->addChild(new OperandDynamicPropertyValue($this, $selectorName, $propertyName));
    }
}

