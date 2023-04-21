<?php

namespace Doctrine\ODM\PHPCR;

final class Event
{
    public const prePersist = 'prePersist';

    public const preRemove = 'preRemove';

    public const preUpdate = 'preUpdate';

    public const preMove = 'preMove';

    public const postRemove = 'postRemove';

    public const postPersist = 'postPersist';

    public const postUpdate = 'postUpdate';

    public const postMove = 'postMove';

    public const postLoad = 'postLoad';

    public const preFlush = 'preFlush';

    public const postFlush = 'postFlush';

    public const endFlush = 'endFlush';

    public const onFlush = 'onFlush';

    public const onClear = 'onClear';

    public const loadClassMetadata = 'loadClassMetadata';

    public const postLoadTranslation = 'postLoadTranslation';

    public const preCreateTranslation = 'preCreateTranslation';

    public const preUpdateTranslation = 'preUpdateTranslation';

    public const preRemoveTranslation = 'preRemoveTranslation';

    public const postRemoveTranslation = 'postRemoveTranslation';

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
