<?php

namespace Doctrine\Tests\ODM\PHPCR\Performance;

class InsertPerformanceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function testInsert2000Documents()
    {
        if (\extension_loaded('xdebug')) {
            $this->markTestSkipped('Performance-Testing with xdebug enabled makes no sense.');
        }

        $n = 3000;
        $dm = $this->createDocumentManager();
        $dm->getConfiguration()->setUUIDGenerationBufferSize($n);

        $s = microtime(true);

        for($i = 0; $i < $n; $i++) {
            $user = new \Doctrine\Tests\Models\CMS\CmsUser();
            $user->name = "Benjamin";
            $user->username = "beberlei";
            $user->status = "active";

            $dm->persist($user);
        }
        $dm->flush();

        $diff = microtime(true) - $s;

        $this->assertTrue($diff < 1.0, "Inserting " . $n . " documents shouldn't take longer than one second, took " . $diff . " seconds.");
    }
}