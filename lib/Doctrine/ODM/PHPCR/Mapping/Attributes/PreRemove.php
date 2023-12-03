<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\PreRemove as BasePreRemove;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class PreRemove extends BasePreRemove implements MappingAttribute
{
}
