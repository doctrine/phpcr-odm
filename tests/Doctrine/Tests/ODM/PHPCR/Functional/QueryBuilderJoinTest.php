<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsAuditItem;

/**
 * @group functional
 */
class QueryBuilderJoinTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $transport = $this->dm->getPhpcrSession()->getTransport();

        $this->resetFunctionalNode($this->dm);

        $address1 = new CmsAddress;
        $address1->country = 'France';
        $address1->city = 'Lyon';
        $address1->zip = '65019';
        $this->dm->persist($address1);

        $address2 = new CmsAddress;
        $address2->country = 'England';
        $address2->city = 'Weymouth';
        $address2->zip = 'AB1DC2';
        $this->dm->persist($address2);

        $user = new CmsUser;
        $user->username = 'dantleech';
        $user->address = $address1;
        $this->dm->persist($user);

        $user = new CmsUser;
        $user->username = 'winstonsmith';
        $user->address = $address2;
        $this->dm->persist($user);

        $user = new CmsUser;
        $user->username = 'anonymous';
        $this->dm->persist($user);

        foreach (array('dantleech', 'winstonsmith', 'dantleech', null) as $i => $username) {
            $auditItem = new CmsAuditItem;
            $auditItem->id = '/functional/audit'.$i;
            $auditItem->message = 'User did something 1';
            $auditItem->username = $username;
            $this->dm->persist($auditItem);
        }

        $this->dm->flush();
    }

    public function testEquiJoinInnerOnReference()
    {
        $qb = $this->dm->createQueryBuilder();
        $qb->fromDocument('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $qb->addJoinInner()
            ->right()->document('Doctrine\Tests\Models\CMS\CmsAddress', 'a')->end()
            ->condition()->equi('u.address', 'a.uuid');
        $qb->where()->eq()->field('a.city')->literal('Lyon');
        $q = $qb->getQuery();
        $res = $q->execute();

        $this->assertCount(1, $res);
        $this->assertEquals('dantleech', $res->current()->username);
    }

    public function provideEquiJoinInner()
    {
        return array(
            array(
                'Inner',
                'CmsUser', 'CmsAuditItem',
                array('a.username' => 'dantleech'), array(
                    array(
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit0',
                    ), 
                    array(
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit2',
                    ),
                ),
            ),
            array(
                'Inner',
                'CmsUser', 'CmsAuditItem',
                array('a.username' => 'winstonsmith'), array(
                    array(
                        'a' => '/functional/winstonsmith',
                        'b' => '/functional/audit1',
                    ), 
                ),
            ),
            array(
                'Inner',
                'CmsUser', 'CmsAuditItem',
                array('a.username' => 'nobody'), array(
                ),
            ),
            array(
                'Inner',
                'CmsAuditItem', 'CmsUser',
                array('a.username' => 'dantleech'), array(
                    array(
                        'a' => '/functional/audit0',
                        'b' => '/functional/dantleech',
                    ),
                    array(
                        'a' => '/functional/audit2',
                        'b' => '/functional/dantleech',
                    ),
                ),
            ),

            array(
                'LeftOuter',
                'CmsAuditItem', 'CmsUser',
                null, array(
                    array(
                        'a' => '/functional/audit0',
                        'b' => '/functional/dantleech',
                    ),
                    array(
                        'a' => '/functional/audit2',
                        'b' => '/functional/dantleech',
                    ),
                    array(
                        'a' => '/functional/audit1',
                        'b' => '/functional/winstonsmith',
                    ),
                ),
            ),

            array(
                'RightOuter',
                'CmsUser', 'CmsAuditItem',
                null, array(
                    array(
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit0',
                    ),
                    array(
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit2',
                    ),
                    array(
                        'a' => '/functional/winstonsmith',
                        'b' => '/functional/audit1',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideEquiJoinInner
     */
    public function testEquiJoinInner($joinType, $leftClass, $rightClass, $criteria = null, $expectedPaths)
    {
        $leftFqn = 'Doctrine\Tests\Models\CMS\\'.$leftClass;
        $rightFqn = 'Doctrine\Tests\Models\CMS\\'.$rightClass;
        $qb = $this->dm->createQueryBuilder();
        $qb->fromDocument($leftFqn, 'a');
        $qb->{'addJoin'.$joinType}()
            ->right()->document($rightFqn, 'b')->end()
            ->condition()->equi('a.username', 'b.username');
        $qb->orderBy()->asc()->field('a.username');

        if ($criteria) {
            foreach ($criteria as $field => $value) {
                $qb->where()->eq()->field($field)->literal($value);
            }
        }

        $q = $qb->getQuery();
        $phpcrQuery = $q->getPhpcrQuery();
        $phpcrRes = $phpcrQuery->execute();
        $phpcrRows = $phpcrRes->getRows();

        // Test PHPCR results for reference
        $this->assertCount(count($expectedPaths), $phpcrRows);
        $found = 0;
        foreach ($phpcrRows as $i => $phpcrRow) {
            foreach ($expectedPaths[$i] as $selector => $expectedPath) {
                $path = $phpcrRow->getPath($selector);
                $this->assertEquals($expectedPath, $path, 'PHPCR Result OK: '.print_r($expectedPaths[$i], true));
            }
        }
    }
}
