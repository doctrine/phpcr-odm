<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Document
{
    /** @var string */
    public $nodeType = 'nt:unstructured';
    /** @var string */
    public $alias;
    /** @var string */
    public $repositoryClass;
    /** @var Boolean */
    public $isVersioned;
    /** @var Boolean */
    public $referenceable;
}
/**
 * @Annotation
 * @Target("CLASS")
 */
final class MappedSuperclass
{
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Node
{
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Property
{
    /** @var string @Required */
    public $name;
    /** @var string */
    public $type = 'undefined';
    /** @var Boolean */
    public $multivalue = false;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Id
{
    public $id = true;
    public $type = 'string';
    /** @var string */
    public $strategy = 'assigned';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Uuid extends Property
{
    public $name = 'jcr:uuid';
    public $type = 'string';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Version extends Property
{
    public $name = 'jcr:baseVersion';
    public $type = 'string';
    public $isVersionField = true;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Boolean extends Property
{
    public $type = 'boolean';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Int extends Property
{
    public $type = 'long';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Long extends Property
{
    public $type = 'long';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Float extends Property
{
    public $type = 'float';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class String extends Property
{
    public $type = 'string';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Date extends Property
{
    public $type = 'date';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Binary extends Property
{
    public $type = 'binary';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ArrayField extends Property
{
    public $type = 'array';
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Reference
{
    /** @var string @Required */
    public $targetDocument;
    /** @var Boolean */
    public $weak = true;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ReferenceOne extends Reference
{
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ReferenceMany extends Reference
{
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Child
{
    /** @var string @Required */
    public $name;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Children
{
    /** @var string */
    public $filter;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Referrers
{
    /** @var string */
    public $filterName;
    /** @var string @Required */
    public $referenceType;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class EmbeddedDocument {}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class EmbedOne extends Property {}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PrePersist {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostPersist {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreUpdate {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostUpdate {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreRemove {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostRemove {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreLoad {}
/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostLoad {}
