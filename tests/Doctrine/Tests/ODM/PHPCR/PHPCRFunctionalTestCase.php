<?php

namespace Doctrine\Tests\ODM\PHPCR;

abstract class PHPCRFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    public function createDocumentManager()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\PHPCR\Mapping\\');
        $reader->setAnnotationNamespaceAlias('Doctrine\ODM\PHPCR\Mapping\\', 'phpcr');
        $paths = array();
        $paths[] = __DIR__ . "/../../Models";
        $paths[] = __DIR__ . "/../../../../../lib/Doctrine/ODM/PHPCR/Document";
        $metaDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader, $paths);

        $url = isset($GLOBALS['DOCTRINE_PHPCR_REPOSITORY']) ? $GLOBALS['DOCTRINE_PHPCR_REPOSITORY'] : 'http://127.0.0.1:8080/server/';
        $workspace = isset($GLOBALS['DOCTRINE_PHPCR_WORKSPACE']) ? $GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] : 'tests';
        $user = isset($GLOBALS['DOCTRINE_PHPCR_USER']) ? $GLOBALS['DOCTRINE_PHPCR_USER'] : '';
        $pass = isset($GLOBALS['DOCTRINE_PHPCR_PASS']) ? $GLOBALS['DOCTRINE_PHPCR_PASS'] : '';
        $transport = isset($GLOBALS['DOCTRINE_PHPCR_TRANSPORT']) ? $GLOBALS['DOCTRINE_PHPCR_TRANSPORT'] : null;

        switch ($transport) {
            case 'doctrinedbal':
                $conn = \Doctrine\DBAL\DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));
                $schema = \Jackalope\Transport\Doctrine\RepositorySchema::create();
                foreach ($schema->toSql($conn->getDatabasePlatform()) as $sql) {
                    $conn->exec($sql);
                }
                $transport = new \Jackalope\Transport\DoctrineDBAL($conn);
                $transport->createWorkspace($workspace);
                break;
            default:
                $transport = null;
        }

        $repository = new \Jackalope\Repository(new \Jackalope\Factory, $url, $transport);
        $credentials = new \PHPCR\SimpleCredentials($user, $pass);
        $session = $repository->login($credentials, $workspace);

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setMetadataDriverImpl($metaDriver);
        $config->setPhpcrSession($session);

        return \Doctrine\ODM\PHPCR\DocumentManager::create($config);
    }

    public function resetFunctionalNode($dm)
    {
        $session = $dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }
        $node = $root->addNode('functional');
        $session->save();
        return $node;
   }
}
