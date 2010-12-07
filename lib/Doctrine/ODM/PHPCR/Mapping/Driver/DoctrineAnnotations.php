<?php

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Document extends Annotation
{
    public $nodeType = 'nt:unstructured';
    public $alias;
    public $repositoryClass;
}
final class MappedSuperclass extends Annotation {}

final class Path extends Annotation
{
}
final class Node extends Annotation
{
}
class Property extends Annotation
{
    public $name;
    public $type = 'undefined';
}
final class Id extends Property
{
    public $id = true;
    public $name = 'jcr:uuid';
    public $type = 'string';
}
final class Boolean extends Property
{
    public $type = 'boolean';
}
final class Int extends Property
{
    public $type = 'int';
}
final class Float extends Property
{
    public $type = 'float';
}
final class String extends Property
{
    public $type = 'string';
}
final class Date extends Property
{
    public $type = 'date';
}
final class ArrayField extends Property
{
    public $type = 'array';
}
class Reference extends Annotation
{
    public $targetDocument;
}
final class EmbedOne extends Reference
{
    public $jsonName;
}
final class EmbedMany extends Reference
{
    public $jsonName;
}
final class ReferenceOne extends Reference
{
    public $cascade = array();
}
final class ReferenceMany extends Reference
{
    public $cascade = array();
    public $mappedBy;
}
