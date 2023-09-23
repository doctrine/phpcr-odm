<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Document as BaseDocument;

#[Attribute(Attribute::TARGET_CLASS)]
final class Document extends BaseDocument
{
}
