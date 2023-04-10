<?php

namespace Doctrine\ODM\PHPCR\Exception;

use Doctrine\ODM\PHPCR\PHPCRException;

/**
 * Missing translation exception class.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class CascadeException extends PHPCRException
{
    public static function newDocumentFound(string $documentString): self
    {
        return new self('A new document was found through a relationship that was not'
                        ." configured to cascade persist operations: $documentString."
                        .' Explicitly persist the new document or configure cascading persist operations'
                        .' on the relationship.');
    }
}
