<?php


namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;

class MoveEventArgs extends LifecycleEventArgs
{
    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $targetPath;

    /**
     * Constructor.
     *
     * @param object                   $document
     * @param DocumentManagerInterface $dm
     * @param string                   $sourcePath Path the document is moved from
     * @param string                   $targetPath Path the document is moved to, including target name
     */
    public function __construct($document, DocumentManagerInterface $dm, $sourcePath, $targetPath)
    {
        parent::__construct($document, $dm);
        $this->sourcePath = $sourcePath;
        $this->targetPath = $targetPath;
    }

    /**
     * Get the path this document is being moved away from, including the
     * document name.
     *
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * Get the path this document is being moved to, including the new document
     * name.
     *
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }
}
