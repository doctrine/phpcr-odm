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

/** 
 * @deprecated
 *
 * Including this file is no longer needed, as each annotation is now in its
 * own file, supporting PSR-0 compatible autoloading.
 *
 * The file is kept for backwards compatibility and will be removed in a future
 * version of phpcr-odm.
 */

require_once __DIR__ . '/Document.php';
require_once __DIR__ . '/MappedSuperclass.php';
require_once __DIR__ . '/Node.php';
require_once __DIR__ . '/Nodename.php';
require_once __DIR__ . '/ParentDocument.php';
require_once __DIR__ . '/Property.php';
require_once __DIR__ . '/TranslatableProperty.php';
require_once __DIR__ . '/Id.php';
require_once __DIR__ . '/Uuid.php';
require_once __DIR__ . '/String.php';
require_once __DIR__ . '/Binary.php';
require_once __DIR__ . '/Long.php';
require_once __DIR__ . '/Int.php';
require_once __DIR__ . '/Double.php';
require_once __DIR__ . '/Float.php';
require_once __DIR__ . '/Date.php';
require_once __DIR__ . '/Boolean.php';
require_once __DIR__ . '/Name.php';
require_once __DIR__ . '/Path.php';
require_once __DIR__ . '/Uri.php';
require_once __DIR__ . '/Decimal.php';
require_once __DIR__ . '/Reference.php';
require_once __DIR__ . '/ReferenceOne.php';
require_once __DIR__ . '/ReferenceMany.php';
require_once __DIR__ . '/Child.php';
require_once __DIR__ . '/Children.php';
require_once __DIR__ . '/MixedReferrers.php';
require_once __DIR__ . '/Referrers.php';
require_once __DIR__ . '/VersionName.php';
require_once __DIR__ . '/VersionCreated.php';
require_once __DIR__ . '/Locale.php';
require_once __DIR__ . '/PrePersist.php';
require_once __DIR__ . '/PostPersist.php';
require_once __DIR__ . '/PreUpdate.php';
require_once __DIR__ . '/PostUpdate.php';
require_once __DIR__ . '/PreRemove.php';
require_once __DIR__ . '/PostRemove.php';
require_once __DIR__ . '/PostLoad.php';
