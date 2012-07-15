<?php
if (isset($metadata) && $metadata instanceof \Doctrine\ODM\PHPCR\Mapping\ClassMetadata) {
    /* @var $metadata \Doctrine\ODM\PHPCR\Mapping\ClassMetadata */
    $metadata->setVersioned('simple');
    $metadata->mapId(array(
        'fieldName' => 'id',
        'id'        => true
    ));
}
