<?php

$up = DIRECTORY_SEPARATOR . '..';
$file = DIRECTORY_SEPARATOR . 'autoload.php';
$candiates = array(
    __DIR__ . $up . $file,
    __DIR__ . $up . DIRECTORY_SEPARATOR . 'vendor' . $file,
    __DIR__ . $up . $up . $up . $file,
);

foreach ($candiates as $path) {
    $autoload = @include_once $path;
    if ($autoload) {
        break;
    }
}

if (!$autoload) {
    throw new RuntimeException('Install dependencies to run the console.');
}

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));

$configFile = getcwd() . DIRECTORY_SEPARATOR . 'cli-config.php';

$helperSet = null;
if (file_exists($configFile)) {
    if (!is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_USER_ERROR
        );
    }

    require $configFile;

    foreach ($GLOBALS as $helperSetCandidate) {
        if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
            $helperSet = $helperSetCandidate;
            break;
        }
    }
} else {
    trigger_error(
        'Configuration file [' . $configFile . '] does not exist. See https://github.com/doctrine/phpcr-odm/wiki/Command-line-tool-configuration', E_USER_ERROR
    );
}

$helperSet = ($helperSet) ?: new \Symfony\Component\Console\Helper\HelperSet();

$cli = new \Symfony\Component\Console\Application('Doctrine ODM PHPCR Command Line Interface', Doctrine\ODM\PHPCR\Version::VERSION);
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);
$cli->addCommands(array(
    new \PHPCR\Util\Console\Command\NodeDumpCommand(),
    new \PHPCR\Util\Console\Command\NodeMoveCommand(),
    new \PHPCR\Util\Console\Command\NodeRemoveCommand(),
    new \PHPCR\Util\Console\Command\NodesUpdateCommand(),
    new \PHPCR\Util\Console\Command\NodeTouchCommand(),
    new \PHPCR\Util\Console\Command\NodeTypeListCommand(),
    new \PHPCR\Util\Console\Command\NodeTypeRegisterCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceCreateCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceDeleteCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceExportCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceImportCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceListCommand(),
    new \PHPCR\Util\Console\Command\WorkspacePurgeCommand(),
    new \PHPCR\Util\Console\Command\WorkspaceQueryCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\DocumentMigrateClassCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\DocumentConvertTranslationCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\GenerateProxiesCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\DumpQueryBuilderReferenceCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\InfoDoctrineCommand(),
    new \Doctrine\ODM\PHPCR\Tools\Console\Command\RegisterSystemNodeTypesCommand(),
));
if (isset($extraCommands) && ! empty($extraCommands)) {
    $cli->addCommands($extraCommands);
}
$cli->run();
