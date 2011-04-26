<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentNotFoundException;
use Doctrine\ODM\PHPCR\PHPCRException;

class DocumentNotFoundExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function testBaseClass()
    {
        try {
            throw new DocumentNotFoundException;
        } catch (PHPCRException $expected) {
            return;
        }
        $this->fail('DocumentNotFoundException is not a descendant of PHPCRException');
    }

    public function testMessage()
    {
        try {
            throw new DocumentNotFoundException;
        } catch (DocumentNotFoundException $e) {
            $this->assertEquals('Document was not found.', $e->getMessage());
        }
    }

}

