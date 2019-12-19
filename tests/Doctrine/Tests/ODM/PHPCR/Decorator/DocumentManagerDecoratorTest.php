<?php

namespace Doctrine\Tests\ODM\PHPCR\Decorator;

use Doctrine\ODM\PHPCR\Decorator\DocumentManagerDecorator;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

/**
 * @group unit
 */
class DocumentManagerDecoratorTest extends PHPCRTestCase
{
    public function testCheckIfAllPublicMethodsAreDecorated()
    {
        $dmMethods = get_class_methods(DocumentManager::class);
        $dmMethods = array_diff($dmMethods, ['__construct', 'create']);
        sort($dmMethods);

        $dmiMethods = get_class_methods(DocumentManagerInterface::class);
        $dmiMethods = array_diff($dmiMethods, ['__construct']);
        sort($dmiMethods);

        $dmdMethods = get_class_methods(OwnDocumentManager::class);
        $dmdMethods = array_diff($dmdMethods, ['__construct']);
        sort($dmdMethods);

        $this->assertEquals($dmMethods, $dmiMethods);
        $this->assertEquals($dmMethods, $dmdMethods);
    }
}

class OwnDocumentManager extends DocumentManagerDecorator
{
}
