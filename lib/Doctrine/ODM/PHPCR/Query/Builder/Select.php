<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for adding selection fields.
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class Select extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_PROPERTY => array(0, null)
        );
    }

    /**
     * Field to select.
     *
     * <code>
     * $qb->select()
     *   ->field('sel_1.foobar')
     *   ->field('sel_1.barfoo');
     * </code>
     *
     * @param string $field - name of field to check, including alias name
     *
     * @factoryMethod
     * @return Select
     */
    public function field($field)
    {
        return $this->addChild(new Field($this, $field));
    }

    public function getNodeType()
    {
        return self::NT_SELECT;
    }
}

