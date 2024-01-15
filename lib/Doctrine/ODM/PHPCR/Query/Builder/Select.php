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
    public function getCardinalityMap(): array
    {
        return [
            self::NT_PROPERTY => [0, null],
        ];
    }

    /**
     * Field to select::.
     *
     * <code>
     * $qb->select()
     *     ->field('sel_1.foobar')
     *     ->field('sel_1.barfoo')
     * ->end();
     * </code>
     *
     * @param string $field - name of field to check, including alias name
     *
     * @factoryMethod
     */
    public function field(string $field): Select
    {
        return $this->addChild(new Field($this, $field));
    }

    public function getNodeType(): string
    {
        return self::NT_SELECT;
    }
}
