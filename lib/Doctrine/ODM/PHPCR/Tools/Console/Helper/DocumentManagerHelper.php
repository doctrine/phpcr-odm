<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * Helper class to make DocumentManager available to console command
 */
class DocumentManagerHelper extends Helper
{
    protected $dm;
    
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }
    
    public function getDocumentManager()
    {
        return $this->dm;
    }
    
    public function getName()
    {
        return 'phpcr:documentManager';
    }
}

