<?php

namespace Doctrine\Tests\ODM\PHPCR\Performance;

class InsertPerformanceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    protected $count = 100;

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->count = isset($GLOBALS['DOCTRINE_PHPCR_PERFORMANCE_COUNT']) ? $GLOBALS['DOCTRINE_PHPCR_PERFORMANCE_COUNT'] : 100;
    }

    public function testInsertDocuments()
    {

        if (\extension_loaded('xdebug')) {
            $this->markTestSkipped('Performance-Testing with xdebug enabled makes no sense.');
        }

        $dm = $this->createDocumentManager();

        $s = microtime(true);

        for($i = 0; $i < $this->count; $i++) {
            $user = new \Doctrine\Tests\Models\CMS\CmsUser();
            $user->name = "Benjamin";
            $user->username = "beberlei";
            $user->status = "active";
            $user->path = "/functional/node$i";

            $dm->persist($user);
        }
        $dm->flush();

        $diff = microtime(true) - $s;

        $this->assertTrue($diff < 1.0, "Inserting " . $this->count . " documents shouldn't take longer than one second, took " . $diff . " seconds.");
    }
}
