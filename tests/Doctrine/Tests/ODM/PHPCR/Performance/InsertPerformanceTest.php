<?php

namespace Doctrine\Tests\ODM\PHPCR\Performance;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;

class InsertPerformanceTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var int
     */
    private $count = 100;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->count = $GLOBALS['DOCTRINE_PHPCR_PERFORMANCE_COUNT'] ?? 100;
    }

    public function testInsertDocuments()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Performance-Testing with xdebug enabled makes no sense.');
        }

        $s = microtime(true);

        for ($i = 0; $i < $this->count; ++$i) {
            $user = new CmsUser();
            $user->name = 'Benjamin';
            $user->username = 'beberlei';
            $user->status = 'active';

            $this->dm->persist($user);
        }
        $this->dm->flush();

        $diff = microtime(true) - $s;

        $this->assertLessThan(1.0, $diff, 'Inserting '.$this->count." documents shouldn't take longer than one second, took ".$diff.' seconds.');
    }
}
