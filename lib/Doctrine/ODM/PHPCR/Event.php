<?php

namespace Doctrine\ODM\PHPCR;

final class Event
{
    const prePersist = 'prePersist';

    const preRemove = 'preRemove';

    const preUpdate = 'preUpdate';

    const preMove = 'preMove';

    const postRemove = 'postRemove';

    const postPersist = 'postPersist';

    const postUpdate = 'postUpdate';

    const postMove = 'postMove';

    const postLoad = 'postLoad';

    const preFlush = 'preFlush';

    const postFlush = 'postFlush';

    const endFlush = 'endFlush';

    const onFlush = 'onFlush';

    const onClear = 'onClear';

    const loadClassMetadata = 'loadClassMetadata';

    const postLoadTranslation = 'postLoadTranslation';

    const preCreateTranslation = 'preCreateTranslation';

    const preUpdateTranslation = 'preUpdateTranslation';

    const preRemoveTranslation = 'preRemoveTranslation';

    const postRemoveTranslation = 'postRemoveTranslation';

    public static $lifecycleCallbacks = [
        self::prePersist => self::prePersist,
        self::preRemove => self::preRemove,
        self::preUpdate => self::preUpdate,
        self::preMove => self::preMove,
        self::postRemove => self::postRemove,
        self::postPersist => self::postPersist,
        self::postUpdate => self::postUpdate,
        self::postMove => self::postMove,
        self::postLoad => self::postLoad,
        self::postLoadTranslation => self::postLoadTranslation,
        self::preCreateTranslation => self::preCreateTranslation,
        self::preUpdateTranslation => self::preUpdateTranslation,
        self::preRemoveTranslation => self::preRemoveTranslation,
        self::postRemoveTranslation => self::postRemoveTranslation,
    ];

    private function __construct()
    {
    }
}
