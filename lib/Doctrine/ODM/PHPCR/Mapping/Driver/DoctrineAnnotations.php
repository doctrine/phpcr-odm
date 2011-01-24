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
    public $multivalue = false;
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
    public $type = 'long';
}
final class Long extends Property
{
    public $type = 'long';
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
final class Binary extends Property
{
    public $type = 'binary';
}
final class ArrayField extends Property
{
    public $type = 'array';
}
class Reference extends Annotation
{
    public $targetDocument;
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
