<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
    const preRemoveTranslation = 'preRemoveTranslation';
    const postRemoveTranslation = 'postRemoveTranslation';

    public static $lifecycleCallbacks = array(
        self::prePersist => self::prePersist,
        self::preRemove => self::preRemove ,
        self::preUpdate => self::preUpdate,
        self::preMove => self::preMove,
        self::postRemove => self::postRemove,
        self::postPersist => self::postPersist,
        self::postUpdate => self::postUpdate,
        self::postMove => self::postMove,
        self::postLoad => self::postLoad,
        self::postLoadTranslation => self::postLoadTranslation,
        self::preCreateTranslation => self::preCreateTranslation,
        self::preRemoveTranslation => self::preRemoveTranslation,
        self::postRemoveTranslation => self::postRemoveTranslation,
    );

    private function __construct()
    {
    }
}
