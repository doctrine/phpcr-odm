<?php

namespace Doctrine\ODM\PHPCR\Exception;

use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;

/**
 * InvalidArgumentException for the PHPCR-ODM.
 */
class OutOfBoundsException extends \OutOfBoundsException implements PHPCRExceptionInterface
{
}
