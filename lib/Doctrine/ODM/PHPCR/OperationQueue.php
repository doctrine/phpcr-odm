<?php

namespace Doctrine\ODM\PHPCR;

class OperationQueue extends \SplQueue
{
    public function hasDocumentForOperation($document, $type)
    {
        $documentOid = spl_object_hash($document);

        foreach ($this as $operation) {
            list($oid, $document, $data) = $operation;
            if ($oid === $documentOid) {
                return true;
            }
        }

        return false;
    }

    public function filterByOperationType($targetOperationType)
    {
        $res = array();
        foreach ($this as $operation) {
            list ($operationType, $document, $data) = $operation;
            if ($targetOperationType == $operationType) {
                $res[] = $document;
            }
        }

        return $res;
    }

    public function push($operationType, $document, $data = array())
    {
        parent::push(array($operationType, $document, $data));
    }
}
