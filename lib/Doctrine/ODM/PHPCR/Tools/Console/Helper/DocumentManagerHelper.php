<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\SessionInterface;

/**
 * Helper class to make DocumentManager available to console command
 */
class DocumentManagerHelper extends Helper
{
    protected $session;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * Constructor
     *
     * @param SessionInterface $session
     * @param DocumentManager $dm
     */
    public function __construct(SessionInterface $session = null, DocumentManager $dm = null)
    {
        if (!$session && $dm) {
            $session = $dm->getPhpcrSession();
        }

        $this->session = $session;
        $this->dm = $dm;
    }

    public function getDocumentManager()
    {
        return $this->dm;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getName()
    {
        return 'phpcr';
    }
}

