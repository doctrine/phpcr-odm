<?php

namespace Doctrine\Tests\ODM\PHPCR\Decorator;

use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

/**
 * @group unit
 */
class DocumentManagerDecoratorTest extends PHPCRTestCase
{
    public function testCheckIfAllPublicMethodsAreDecorated()
    {
        $dmMethods = get_class_methods('Doctrine\ODM\PHPCR\DocumentManager');
        $dmMethods = array_diff($dmMethods, array('__construct', 'create'));
        sort($dmMethods);

        $dmiMethods = get_class_methods('Doctrine\ODM\PHPCR\DocumentManagerInterface');
        $dmiMethods = array_diff($dmiMethods, array('__construct'));
        sort($dmiMethods);

        $dmdMethods = get_class_methods('Doctrine\ODM\PHPCR\\Decorator\DocumentManagerDecorator');
        $dmdMethods = array_diff($dmdMethods, array('__construct'));
        sort($dmdMethods);

        $this->assertEquals($dmMethods, $dmiMethods);
        $this->assertEquals($dmMethods, $dmdMethods);
    }
}
