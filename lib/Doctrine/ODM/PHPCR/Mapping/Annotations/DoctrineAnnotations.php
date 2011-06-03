<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

final class Document extends Annotation
{
    public $nodeType = 'nt:unstructured';
    public $alias;
    public $repositoryClass;
    public $isVersioned;
}
final class MappedSuperclass extends Annotation
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
final class Id extends Annotation
{
    public $id = true;
    public $type = 'string';
    public $strategy = 'assigned';
}
final class Uuid extends Property
{
    public $name = 'jcr:uuid';
    public $type = 'string';
}
final class Version extends Property
{
    public $name = 'jcr:baseVersion';
    public $type = 'string';
    public $isVersionField = true;
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
class Child extends Annotation
{
    public $name;
}

final class EmbeddedDocument extends Annotation {}
final class EmbedOne extends Property {}

/* Annotations for lifecycle callbacks */
final class HasLifecycleCallbacks extends Annotation {}
final class PrePersist extends Annotation {}
final class PostPersist extends Annotation {}
final class PreUpdate extends Annotation {}
final class PostUpdate extends Annotation {}
final class PreRemove extends Annotation {}
final class PostRemove extends Annotation {}
final class PreLoad extends Annotation {}
final class PostLoad extends Annotation {}

