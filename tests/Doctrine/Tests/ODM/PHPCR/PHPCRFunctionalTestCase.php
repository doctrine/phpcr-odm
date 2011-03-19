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

        $url = isset($_GLOBALS['DOCTRINE_PHPCR_REPOSITORY']) ? $_GLOBALS['DOCTRINE_PHPCR_REPOSITORY'] : 'http://127.0.0.1:8080/server/';
        $workspace = isset($_GLOBALS['DOCTRINE_PHPCR_WORKSPACE']) ? $_GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] : 'tests';
        $user = isset($_GLOBALS['DOCTRINE_PHPCR_USER']) ? $_GLOBALS['DOCTRINE_PHPCR_USER'] : '';
        $pass = isset($_GLOBALS['DOCTRINE_PHPCR_PASS']) ? $_GLOBALS['DOCTRINE_PHPCR_PASS'] : '';

        $repository = new \Jackalope\Repository(new \Jackalope\Factory, $url);
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
