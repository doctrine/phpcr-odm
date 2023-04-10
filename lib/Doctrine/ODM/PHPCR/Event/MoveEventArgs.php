<?php

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class MoveEventArgs extends LifecycleEventArgs
{
    private string $sourcePath;
    private string $targetPath;

    /**
     * The paths are absolute paths including the document name.
     */
    public function __construct(object $document, DocumentManagerInterface $dm, string $sourcePath, string $targetPath)
    {
        parent::__construct($document, $dm);
        $this->sourcePath = $sourcePath;
        $this->targetPath = $targetPath;
    }

    /**
     * Get the path this document is being moved away from, including the document name.
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Get the path this document is being moved to, including the new document name.
     */
    public function getTargetPath(): string
    {
        return $this->targetPath;
    }
}
