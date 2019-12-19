<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsAuditItem;
use Doctrine\Tests\Models\CMS\CmsProfile;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class QueryBuilderJoinTest extends PHPCRFunctionalTestCase
{
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();

        $this->resetFunctionalNode($this->dm);

        $ntm = $this->dm->getPhpcrSession()->getWorkspace()->getNodeTypeManager();
        $ntm->registerNodeTypesCnd('[phpcr:cms_profile] > nt:unstructured', true);

        $address1 = new CmsAddress();
        $address1->country = 'France';
        $address1->city = 'Lyon';
        $address1->zip = '65019';
        $this->dm->persist($address1);

        $address2 = new CmsAddress();
        $address2->country = 'England';
        $address2->city = 'Weymouth';
        $address2->zip = 'AB1DC2';
        $this->dm->persist($address2);

        $user = new CmsUser();
        $user->username = 'dantleech';
        $user->address = $address1;
        $this->dm->persist($user);

        $profile = new CmsProfile();
        $profile->setData('testdata');
        $user->addProfile($profile);
        $this->dm->persist($profile);

        $user = new CmsUser();
        $user->username = 'winstonsmith';
        $user->address = $address2;
        $this->dm->persist($user);

        $user = new CmsUser();
        $user->username = 'anonymous';
        $this->dm->persist($user);

        foreach (['dantleech', 'winstonsmith', 'dantleech', null] as $i => $username) {
            $auditItem = new CmsAuditItem();
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
        $qb->fromDocument(CmsUser::class, 'u');
        $qb->addJoinInner()
            ->right()->document(CmsAddress::class, 'a')->end()
            ->condition()->equi('u.address', 'a.uuid');
        $qb->where()->eq()->field('a.city')->literal('Lyon');
        $q = $qb->getQuery();
        $res = $q->execute();

        $this->assertCount(1, $res);
        $this->assertEquals('dantleech', $res->current()->username);
    }

    public function provideEquiJoinInner()
    {
        return [
            [
                'Inner',
                'CmsUser', 'CmsAuditItem',
                ['a.username' => 'dantleech'], [
                    [
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit0',
                    ],
                    [
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit2',
                    ],
                ],
            ],
            [
                'Inner',
                'CmsUser', 'CmsAuditItem',
                ['a.username' => 'winstonsmith'], [
                    [
                        'a' => '/functional/winstonsmith',
                        'b' => '/functional/audit1',
                    ],
                ],
            ],
            [
                'Inner',
                'CmsUser', 'CmsAuditItem',
                ['a.username' => 'nobody'], [
                ],
            ],
            [
                'Inner',
                'CmsAuditItem', 'CmsUser',
                ['a.username' => 'dantleech'], [
                    [
                        'a' => '/functional/audit0',
                        'b' => '/functional/dantleech',
                    ],
                    [
                        'a' => '/functional/audit2',
                        'b' => '/functional/dantleech',
                    ],
                ],
            ],

            [
                'LeftOuter',
                'CmsAuditItem', 'CmsUser',
                null, [
                    [
                        'a' => '/functional/audit0',
                        'b' => '/functional/dantleech',
                    ],
                    [
                        'a' => '/functional/audit2',
                        'b' => '/functional/dantleech',
                    ],
                    [
                        'a' => '/functional/audit1',
                        'b' => '/functional/winstonsmith',
                    ],
                ],
            ],

            [
                'RightOuter',
                'CmsUser', 'CmsAuditItem',
                null, [
                    [
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit0',
                    ],
                    [
                        'a' => '/functional/dantleech',
                        'b' => '/functional/audit2',
                    ],
                    [
                        'a' => '/functional/winstonsmith',
                        'b' => '/functional/audit1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideEquiJoinInner
     */
    public function testEquiJoinInner($joinType, $leftClass, $rightClass, $criteria, $expectedPaths)
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

    /**
     * Verify that using an outer join on a document that is uniquely typed
     * results in the full expected result set.
     */
    public function testUniqueNodeTypeOuterJoin()
    {
        $qb = $this->dm->createQueryBuilder()
            ->fromDocument(CmsUser::class, 'u')
            ->addJoinLeftOuter()
                ->right()->document(CmsProfile::class, 'p')->end()
                ->condition()->equi('u.profiles', 'p.uuid')
            ->end()->end()
            ->where()->orX()
                ->eq()->field('u.username')->literal('winstonsmith')->end()
                ->eq()->field('p.data')->literal('testdata')->end()
            ->end()->end()
            ->orderBy()->asc()->field('u.username')->end()->end();

        $q = $qb->getQuery();
        $phpcrQuery = $q->getPhpcrQuery();
        $phpcrRes = $phpcrQuery->execute();
        $phpcrRows = $phpcrRes->getRows();

        $this->assertCount(2, $phpcrRows);
        foreach ($phpcrRows as $key => $row) {
            $this->assertEquals(0 == $key ? '/functional/dantleech' : '/functional/winstonsmith', $row->getPath('u'));
        }
    }
}
