<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\ParentDocument as BaseParentDocument;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ParentDocument extends BaseParentDocument
{
}
