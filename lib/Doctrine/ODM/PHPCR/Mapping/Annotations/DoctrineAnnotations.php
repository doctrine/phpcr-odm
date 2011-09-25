<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Document extends Annotation
{
    public $nodeType = 'nt:unstructured';
    public $alias;
    public $repositoryClass;
    public $isVersioned;
    public $referenceable;
}
/**
 * @Annotation
 */
final class MappedSuperclass extends Annotation
{
}
/**
 * @Annotation
 */
final class Node extends Annotation
{
}
/**
 * @Annotation
 */
class Property extends Annotation
{
    public $name;
    public $type = 'undefined';
    public $multivalue = false;
}
/**
 * @Annotation
 */
final class Id extends Annotation
{
    public $id = true;
    public $type = 'string';
    public $strategy = 'assigned';
}
/**
 * @Annotation
 */
final class Uuid extends Property
{
    public $name = 'jcr:uuid';
    public $type = 'string';
}
/**
 * @Annotation
 */
final class Version extends Property
{
    public $name = 'jcr:baseVersion';
    public $type = 'string';
    public $isVersionField = true;
}
/**
 * @Annotation
 */
final class Boolean extends Property
{
    public $type = 'boolean';
}
/**
 * @Annotation
 */
final class Int extends Property
{
    public $type = 'long';
}
/**
 * @Annotation
 */
final class Long extends Property
{
    public $type = 'long';
}
/**
 * @Annotation
 */
final class Float extends Property
{
    public $type = 'float';
}
/**
 * @Annotation
 */
final class String extends Property
{
    public $type = 'string';
}
/**
 * @Annotation
 */
final class Date extends Property
{
    public $type = 'date';
}
/**
 * @Annotation
 */
final class Binary extends Property
{
    public $type = 'binary';
}
/**
 * @Annotation
 */
final class ArrayField extends Property
{
    public $type = 'array';
}
/**
 * @Annotation
 */
class Reference extends Annotation
{
    public $targetDocument;
    public $weak = true;
}
/**
 * @Annotation
 */
final class ReferenceOne extends Reference
{
}
/**
 * @Annotation
 */
final class ReferenceMany extends Reference
{
}
/**
 * @Annotation
 */
class Child extends Annotation
{
    public $name;
}
/**
 * @Annotation
 */
class Children extends Annotation
{
    public $filter = null;
}
/**
 * @Annotation
 */
class Referrers extends Annotation
{
    public $filterName = null;
    public $referenceType = null;
}

/**
 * @Annotation
 */
final class EmbeddedDocument extends Annotation {}
/**
 * @Annotation
 */
final class EmbedOne extends Property {}

/**
 * @Annotation
 */
final class PrePersist extends Annotation {}
/**
 * @Annotation
 */
final class PostPersist extends Annotation {}
/**
 * @Annotation
 */
final class PreUpdate extends Annotation {}
/**
 * @Annotation
 */
final class PostUpdate extends Annotation {}
/**
 * @Annotation
 */
final class PreRemove extends Annotation {}
/**
 * @Annotation
 */
final class PostRemove extends Annotation {}
/**
 * @Annotation
 */
final class PreLoad extends Annotation {}
/**
 * @Annotation
 */
final class PostLoad extends Annotation {}
