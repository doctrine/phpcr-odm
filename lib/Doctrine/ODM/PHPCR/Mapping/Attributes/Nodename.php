<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Nodename as BaseNodename;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nodename extends BaseNodename
{
}
