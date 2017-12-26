<?php

namespace Doctrine\ODM\PHPCR\Exception;

use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;

/**
 * InvalidArgumentException for the PHPCR-ODM.
 */
class InvalidArgumentException extends \InvalidArgumentException implements PHPCRExceptionInterface
{
}
