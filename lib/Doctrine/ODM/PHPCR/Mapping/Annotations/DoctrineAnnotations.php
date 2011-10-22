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
    /** @var string @Required */
    public $alias;
    /** @var string */
    public $repositoryClass;
    /** @var boolean */
    public $versionable = false;
    /** @var boolean */
    public $referenceable = false;
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
 * The name of this node as in PHPCR\NodeInterface::getName
 * @Annotation
 * @Target("PROPERTY")
 */
final class Nodename
{
}

/**
 * The parent of this node as in PHPCR\NodeInterface::getParent
 * Parent is a reserved keyword in php, thus we use ParentDocument as name.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class ParentDocument
{
}

/**
 * base class for all property types
 */
class Property
{
    /** @var string */
    public $name;
    /** @var string */
    public $type = 'undefined';
    /** @var boolean */
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
final class String extends Property
{
    public $type = 'string';
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
final class Long extends Property
{
    public $type = 'long';
}
/**
 * Convenience alias for Long.
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
final class Double extends Property
{
    public $type = 'double';
}
/**
 * Convenience alias for Double.
 * @Annotation
 * @Target("PROPERTY")
 */
final class Float extends Property
{
    public $type = 'double';
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
final class Boolean extends Property
{
    public $type = 'boolean';
}
/**
 * String that is restricted to name with optional namespace
 * @Annotation
 * @Target("PROPERTY")
 */
final class Name extends Property
{
    public $type = 'string';
}
/**
 * String that is an absolute or relative path in the repository
 * @Annotation
 * @Target("PROPERTY")
 */
final class Path extends Property
{
    public $type = 'string';
}
/**
 * String that is validated to be an URI
 * @Annotation
 * @Target("PROPERTY")
 */
final class Uri extends Property
{
    public $type = 'string';
}
/**
 * Large numbers bcmath compatible strings
 * @Annotation
 * @Target("PROPERTY")
 */
final class Decimal extends Property
{
    public $type = 'string';
}

/**
 * base class for the reference types
 */
class Reference
{
    /** @var string @Required */
    public $targetDocument;
    /** @var boolean */
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
final class Child
{
    /** @var string */
    public $name;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Children
{
    /** @var string */
    public $filter;
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Referrers
{
    /** @var string */
    public $filterName;
    /** @var string */
    public $referenceType;
}

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
