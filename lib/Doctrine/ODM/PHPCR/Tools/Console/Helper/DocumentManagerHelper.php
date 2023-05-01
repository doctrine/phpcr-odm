<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Helper;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\Console\Helper\PhpcrHelper;

/**
 * Helper class to make DocumentManager available to console command.
 */
final class DocumentManagerHelper extends PhpcrHelper
{
    private ?DocumentManagerInterface $dm;

    public function __construct(SessionInterface $session = null, DocumentManagerInterface $dm = null)
    {
        if (!$session && $dm) {
            $session = $dm->getPhpcrSession();
        }
        parent::__construct($session);

        $this->dm = $dm;
    }

    public function getDocumentManager(): ?DocumentManagerInterface
    {
        return $this->dm;
    }

    public function getName(): string
    {
        return 'phpcr';
    }
}
