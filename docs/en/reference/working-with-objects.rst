Working with Objects
====================

This chapter explains how to work with the ``DocumentManager`` and the
``UnitOfWork``. The Unit of Work encapsulates the information to be written
to PHPCR when you call ``DocumentManager#flush()``.

A Unit of Work can be manually closed by calling ``DocumentManager#close()``.
Any changes to documents within this UnitOfWork that have not yet been
persisted are lost.

.. note::

    It is very important to understand that only
    ``DocumentManager::flush()`` ever causes write operations against the
    repository to be executed. Any other methods such as
    ``DocumentManager::persist($document)`` or
    ``DocumentManager::remove($document)`` only notify the UnitOfWork to
    perform these operations during flush.

    Not calling ``DocumentManager::flush()`` will lead to all changes
    during that request being lost.

.. tip::

    The ``DocumentManager`` is very similar to the Doctrine ORM EntityManager and
    this chapter is similar to its `corresponding ORM chapter <https://doctrine-orm.readthedocs.org/en/latest/reference/working-with-objects.html>`_.
    This chapter tries to help you by highlighting the places where PHPCR-ODM
    is different from the ORM.


Documents and the Identity Map
------------------------------

Objects managed by Doctrine PHPCR-ODM are called *documents*.
Every document has an identifier, which is its PHPCR path. The path is unique
inside the workspace. Take the following example, where you find an article
with the headline "Hello World" with the ID ``/cms/article/hello-world``::

    $article = $documentManager->find(null, '/cms/article/hello-world');
    $article->setHeadline('Hello World dude!');

    $article2 = $documentManager->find(null, '/cms/article/hello-world');
    echo $article2->getHeadline(); // Hello World dude!

.. note::

    The first argument to ``find()`` is the document class name. While the ORM
    has a table per class and thus always needs the document class name,
    PHPCR-ODM has one tree for all documents. The above call will find you
    whatever document is at that path. Note that you may optionally specify
    the class name to have PHPCR-ODM detect if the document is not of the
    expected type.

In this case, the article is retrieved from the document manager twice,
but modified in between. Doctrine 2 realizes that it is the same ID and will
only ever give you access to one instance of the Article with ID
``/cms/article/hello-world``, no matter how often do you retrieve it from
the ``DocumentManager`` and even no matter what kind of Query method you are
using (find, findBy, query builder, getDocumentsByPhpcrQuery). This is
called "Identity Map" pattern, which means Doctrine keeps a map of each
document that has been retrieved in the current PHP request and keeps
returning you the same instances.

In the previous example the ``echo`` prints "Hello World dude!" to the
screen. You can even verify that ``$article`` and ``$article2`` are
indeed pointing to the same instance by running the following
code::

    if ($article === $article2) {
        echo "Yes we are the same!";
    }

Sometimes you want to clear the identity map of a ``DocumentManager`` to
start over. We use this regularly in our unit tests to enforce
loading documents from the repository again instead of serving them
from the identity map. You can call ``DocumentManager::clear()`` to
achieve this result.

.. note::

    In PHPCR-ODM, the ID is the PHPCR path of the document. This means it is
    possible to change the ID of a document by moving it in the tree using the
    the ``DocumentManager::move()`` operation.
    To create a reference to a document that is stable over move operations,
    make the document *referenceable* and map the ``Uuid`` field. You can find
    a document by its universally unique identifier.


Document Graph Traversal
------------------------

Although Doctrine allows for a complete separation of your domain
model (Document classes) there will never be a situation where
documents are "missing" when traversing associations. You can walk
all the associations inside your document models as deep as you
want.

Take the following example of a single ``Article`` document fetched
from newly opened DocumentManager::

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

    /**
     * @PHPCR\Document
     */
    class Article
    {
        /**
         * @PHPCR\Id
         */
        private $id;

        /**
         * @PHPCR\Field(type="string")
         */
        private $headline;

        /**
         * @PHPCR\ReferenceOne
         */
        private $author;

        /**
         * @PHPCR\Referrers(referrerDocument="Comment", referencedBy="article")
         */
        private $comments;

        public function __construct {
            $this->comments = new ArrayCollection();
        }

        public function getAuthor() { return $this->author; }
        public function getComments() { return $this->comments; }
    }

    $article = $em->find(null, '/cms/article/hello-world');

This code retrieves an ``Article`` instance with ID
``/cms/article/hello-world``, executing a single ``getNode()`` operation
on the repository, retrieving only the data required for the specified
article. However, you can still access the associated properties author
and comments and the associated documents they contain.

This works by utilizing the lazy loading pattern. Instead of
passing you back a real Author instance and a collection of
comments, Doctrine will create proxy instances for you. Only if you
access these proxies for the first time they will go through the
``DocumentManager`` and load their state from the repository.

.. note::

    In PHPCR-ODM, relations between documents are expressed in *references*.
    References are directed links. You can map the backlinks using the
    ``Referrers`` mapping.

This lazy-loading process happens behind the scenes, hidden from
your code. Have a look at the following example::

    $article = $em->find(null, '/cms/article/hello-world');

    // accessing a method of the user instance triggers the lazy-load
    echo "Author: " . $article->getAuthor()->getName() . "\n";

    if ($article->getAuthor() instanceof User) {
        // getAuthor returns a proxy class which is instanceof User
    }

    // accessing the comments as an iterator triggers the lazy-load
    // retrieving ALL the comments of this article from the repository
    // using a single getNodes call
    foreach ($article->getComments() AS $comment) {
        echo $comment->getText() . "\n\n";
    }

    // Article::$comments passes instanceof tests for the Collection interface
    // But it will NOT pass for the ArrayCollection interface
    if ($article->getComments() instanceof \Doctrine\Common\Collections\Collection) {
        echo "This will always be true!";
    }

A slice of the generated proxy classes code looks like the
following example. Real proxy class override *all* public
methods along the lines of the ``getName()`` method shown below::

    class UserProxy extends User implements Proxy
    {
        private function _load()
        {
            // lazy loading code
        }

        public function getName()
        {
            $this->_load();
            return parent::getName();
        }
        // .. other public methods of User
    }

.. warning::

    Traversing the object graph for parts that are lazy-loaded will
    easily trigger lots of repository lookups and will perform badly if used
    too heavily. If you often use child documents for example, look into
    the ``fetchDepth`` configuration.


Persisting documents
--------------------

When you create a new document, the ``DocumentManager`` knows nothing about it.
You need to call ``DocumentManager::persist($document)`` to make the document
MANAGED. You only need to do that on object instantiation. From now on,
whenever you modify the object you loaded from the ``DocumentManager``, it will
automatically be synchronized with the repository when
``DocumentManager::flush()`` is invoked.

.. note::

    Invoking the ``persist`` method for a document does NOT
    cause an immediate addNode on the repository.
    Doctrine applies a strategy called "transactional write-behind",
    which means that it will delay most SQL commands until
    ``DocumentManager::flush()`` is invoked which will then issue all
    necessary PHPCR calls to synchronize your documents with the
    repository in the most efficient way - a single, short transaction -
    taking care of maintaining referential integrity.


Example::

    $user = new User;
    $user->setName('Mr.Right');
    $dm->persist($user);
    $dm->flush();

.. note::

    Generated document identifiers / primary keys are
    guaranteed to be available after the next successful flush
    operation that involves the document in question. You may not rely on
    a generated identifier to be available directly after invoking
    ``persist``. The inverse is also true. After a failed flush,
    a document may already show a generated identifier even though
    it was not persisted.


The semantics of the persist operation, applied on a document X, are
as follows:

*  If X is a new document, it becomes managed. The document X will be
   entered into the repository as a result of the flush operation;
*  If X is a pre-existing managed document, it is ignored by the
   persist operation. However, the persist operation is cascaded to
   documents referenced by X if the relationships from X to these
   other documents are mapped with ``cascade=PERSIST`` or ``cascade=ALL`` (see
   "Transitive Persistence");
*  If X is a removed document, it becomes managed;
*  If X is a detached document, an exception will be thrown on
   flush.

Removing documents
------------------

A document can be removed from persistent storage by passing it to
the ``DocumentManager::remove($document)`` method. By applying the
``remove`` operation on some document, that document becomes REMOVED,
which means that its persistent state will be deleted once
``DocumentManager::flush()`` is invoked.

.. note::

    Just like ``persist``, invoking ``remove`` with a document
    does NOT cause an immediate remove() to be issued on the
    repository. The document will be deleted on the next invocation of
    ``DocumentManager::flush()`` that involves that document. This
    means that documents scheduled for removal can still be queried
    for and appear in query and collection results. See
    the section on :ref:`Repository and UnitOfWork Out-Of-Sync <workingobjects_repository_uow_outofsync>`
    for more information.


Example::

    $dm->remove($user);
    $dm->flush();

The semantics of the remove operation, applied to a document X are
as follows:

*  If X is a new document, it is ignored by the remove operation.
   However, the remove operation is cascaded to documents referenced by
   X, if the relationship from X to these other documents is mapped
   with ``cascade=REMOVE`` or ``cascade=ALL`` (see "Transitive Persistence");
*  If X is a managed document, the remove operation causes it to
   become removed. The remove operation is cascaded to documents
   referenced by X, if the relationships from X to these other
   documents is mapped with ``cascade=REMOVE`` or ``cascade=ALL`` (see
   "Transitive Persistence");
*  If X is a detached document, an ``InvalidArgumentException`` will be
   thrown;
*  If X is a removed document, it is ignored by the remove operation;
*  A removed document X will be removed from the repository as a result
   of the flush operation.

After a document has been removed, its in-memory state is the same as
before the removal, except that the identifier is set to null.

Removing a document will also **automatically delete any children** of it.
Note that no events will be triggered for the removed children, only for
the document explicitly removed.

By default, references and referring documents are not deleted. You can enable
this by configuring cascading removal on the association mapping. If an association
is marked as ``CASCADE=REMOVE``, PHPCR-ODM will follow this association. If
its a Single association it will pass this document to
``DocumentManager::remove()``. If the association is a collection, Doctrine
will loop over all its elements and pass them to``DocumentManager::remove()``.
In both cases the cascade remove semantics are applied recursively.
For large object graphs this removal strategy can be very costly.

.. note::

    Contrary to the ORM, the PHPCR query language knows no DELETE statement.
    If you expect to remove large object graphs, try to model them in a way
    that you can simply remove the parent, as children removal is as cheap
    as having a relational database cascade removal through foreign keys.

Detaching documents
-------------------

You can make Doctrine stop tracking a document by detaching it from
the ``UnitOfWork``. To do this, you invoke the
``DocumentManager::detach($document)`` method with the document. Changes
made to the detached document, including removal of the document, will
not be synchronized to the repository after the document has been
detached.

Doctrine will discard all references to a detached document.

Example::

    $dm->detach($document);

The semantics of the detach operation, applied to a document X are
as follows:

*  If X is a managed document, the detach operation causes it to
   become detached. The detach operation is cascaded to documents
   referenced by X, if the relationships from X to these other
   documents is mapped with ``cascade=DETACH`` or ``cascade=ALL`` (see
   "Transitive Persistence"). Documents which previously referenced X
   will continue to reference X;
*  If X is a new or detached document, it is ignored by the detach
   operation;
*  If X is a removed document, the detach operation is cascaded to
   documents referenced by X, if the relationships from X to these
   other documents is mapped with ``cascade=DETACH`` or ``cascade=ALL`` (see
   "Transitive Persistence"). Documents which previously referenced X
   will continue to reference X.

There are several situations in which a document will become detached
automatically without invoking the ``detach`` method:

*  When ``DocumentManager::clear()`` is invoked, all documents that are
   currently managed by the ``DocumentManager`` instance become detached;
*  When serializing a document. The document retrieved upon subsequent
   unserialization will be detached (This is the case for all documents
   that are serialized and stored in some cache).

The ``detach`` operation is usually not as frequently needed and
used as ``persist`` and ``remove``.

Merging documents
-----------------

Merging documents refers to the merging of (usually detached)
documents into the context of a ``DocumentManager`` so that they become
managed again. To merge the state of a document into a
``DocumentManager`` use the ``DocumentManager::merge($document)`` method. The
state of the passed document will be merged into a managed copy of
this document and this copy will subsequently be returned.

Example::

    $detachedDocument = unserialize($serializedDocument); // some detached document
    $document = $em->merge($detachedDocument);
    // $document now refers to the fully managed copy returned by the merge operation.
    // The DocumentManager now manages the persistence of $document as usual.


The semantics of the merge operation, applied to a document X, are
as follows:

*  If X is a detached document, the state of X is copied onto a
   pre-existing managed document instance X' of the same identity;
*  If X is a new document instance, a new managed copy X' will be
   created and the state of X is copied onto this managed instance;
*  If X is a removed document instance, an ``InvalidArgumentException``
   will be thrown;
*  If X is a managed document, it is ignored by the merge operation,
   however, the merge operation is cascaded to documents referenced by
   relationships from X if these relationships have been mapped with
   the cascade element value MERGE or ALL (see "Transitive
   Persistence");
*  For all documents Y referenced by relationships from X having the
   cascade element value ``MERGE`` or ``ALL``, Y is merged recursively as Y'.
   For all such Y referenced by X, X' is set to reference Y'. (Note
   that if X is managed then X is the same object as X'.);
*  If X is a document merged to X', with a reference to another
   document Y, where ``cascade=MERGE`` or ``cascade=ALL`` is not specified, then
   navigation of the same association from X' yields a reference to a
   managed object Y' with the same persistent identity as Y.

The ``merge`` operation is usually not as frequently needed and
used as ``persist`` and ``remove``. The most common scenario for
the ``merge`` operation is to reattach documents to a ``DocumentManager``
that come from some cache (and are therefore detached) and you want
to modify and persist such a document.

.. warning::

    If you need to perform multiple merges of documents that share
    certain subparts of their object-graphs and cascade merge, then you
    have to call ``DocumentManager::clear()`` between the successive
    calls to ``DocumentManager::merge()``. Otherwise you might end up
    with multiple copies of the "same" object in the repository, however
    with different IDs, or a duplicate ID conflict - depending on how
    you generate IDs.

.. note::

    If you load some detached documents from a cache and you do
    not need to persist or delete them or otherwise make use of them
    without the need for persistence services there is no need to use
    ``merge``. I.e. you can simply pass detached objects from a cache
    directly to the view.


Synchronization with the Repository
-----------------------------------

The state of persistent documents is synchronized with the repository
by calling ``flush`` on a ``DocumentManager`` by commiting the underlying
``UnitOfWork``. The synchronization involves writing any updates to
persistent documents and their relationships to the repository.
Thereby bidirectional relationships are persisted based on the
references held by the owning side of the relationship as explained
in the Association Mapping chapter.

When ``DocumentManager::flush()`` is called, Doctrine inspects all
managed, new and removed documents and will perform the necessary
operations.

.. _workingobjects_repository_uow_outofsync:

Effects of Repository and UnitOfWork being Out-Of-Sync
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

As soon as you begin to change the state of documents, call persist or remove the
contents of the UnitOfWork and the repository will get out of sync. They can
only be synchronized by calling ``DocumentManager::flush()``. This section
describes the effects of repository and UnitOfWork being out of sync.

*  Documents that are scheduled for removal can still be queried from the repository.
   They are returned from queries, calls to getReferrers and getChildren and
   stay visible in collections;
*  Documents that are passed to ``DocumentManager::persist`` do not turn up in query
   results and do not appear in collections;
*  Documents that have changed will not be overwritten with the state from the repository.
   This is because the identity map will detect the construction of an already existing
   document and assumes its the most up to date version.

``DocumentManager::flush()`` is never called implicitly by Doctrine. You
always have to trigger it manually.

Synchronizing New and Managed Documents
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The flush operation applies to a managed document with the following
semantics:

*  The document itself is synchronized to the repository using PHPCR
   API calls, only if at least one persistent field has changed;
*  No PHPCR API calls are executed if the document did not change.

The flush operation applies to a new document with the following
semantics:

* The document itself is synchronized to the repository using
  PHPCR API calls.

For all (initialized) relationships of the new or managed document
the following semantics apply to each associated document X:

*  If X is new and persist operations are configured to cascade on
   the relationship, X will be persisted;
*  If X is new and no persist operations are configured to cascade
   on the relationship, an exception will be thrown as this indicates
   a programming error;
*  If X is removed and persist operations are configured to cascade
   on the relationship, an exception will be thrown as this indicates
   a programming error (X would be re-persisted by the cascade);
*  If X is detached and persist operations are configured to
   cascade on the relationship, an exception will be thrown (This leads
   to the same result as passing X to persist()).

Synchronizing Removed Documents
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The flush operation applies to a removed document by deleting its
persistent state from the repository. No cascade options are relevant
for removed documents on flush, the cascade remove option is already
executed during ``DocumentManager::remove($document)``.

The size of a Unit of Work
~~~~~~~~~~~~~~~~~~~~~~~~~~

The size of a Unit of Work mainly depends on the number of managed
documents at a particular point in time.

The cost of flushing
~~~~~~~~~~~~~~~~~~~~

How costly a flush operation is, mainly depends on two factors:


*  The size of the document manager's current Unit of Work;
*  The configured change tracking policies.

You can get the size of a Unit of Work as follows::

    $uowSize = $dm->getUnitOfWork()->size();

The size represents the number of managed documents in the Unit of
Work. This size affects the performance of flush() operations due
to change tracking (see "Change Tracking Policies") and, of course,
memory consumption, so you may want to check it from time to time
during development.

.. note::

    Do not invoke ``flush`` after every change to a document
    or every single invocation of persist/remove/merge/... This is an
    anti-pattern and unnecessarily reduces the performance of your
    application. Instead, form units of work that operate on your
    documents and call ``flush`` when you are done. While serving a
    single HTTP request there should be usually no need for invoking
    ``flush`` more than 0-2 times.


Direct Access to a Unit of Work
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can get direct access to the Unit of Work by calling
``DocumentManager::getUnitOfWork()``. This will return the UnitOfWork
instance the ``DocumentManager`` is currently using::

    $uow = $em->getUnitOfWork();

.. note::

    Directly manipulating a UnitOfWork is not recommended.
    When working directly with the UnitOfWork API, respect methods
    marked as INTERNAL by not using them and carefully read the API
    documentation.


Document State
~~~~~~~~~~~~~~

As outlined in the architecture overview, a document can be in one of
four possible states: NEW, MANAGED, REMOVED, DETACHED. If you
explicitly need to find out what the current state of a document is
in the context of a certain ``DocumentManager`` you can ask the
underlying ``UnitOfWork``::

    switch ($dm->getUnitOfWork()->getDocumentState($document)) {
        case UnitOfWork::STATE_MANAGED:
            ...
        case UnitOfWork::STATE_REMOVED:
            ...
        case UnitOfWork::STATE_DETACHED:
            ...
        case UnitOfWork::STATE_NEW:
            ...
    }

The states mean the following:

* **MANAGED**: The document is associated with a ``DocumentManager``
  and it is not scheduled for removal.
* **REMOVED**: The document has been passed to ``DocumentManager::remove()``
  but no flush operation executing the removal was triggered yet. A
  REMOVED document is still associated with a ``DocumentManager``
  until the next flush operation.
* **DETACHED**: The document has persistent state and identity but is
  currently not associated with a ``DocumentManager``.
* **NEW**: The document has no persistent state and identity
  and is not associated with a ``DocumentManager`` (for example those
  just created via the "new" operator).

.. _workingobjects-query:

Querying
--------

Doctrine PHPCR-ODM provides the following ways, in increasing level of
power and flexibility, to query for persisted documents. You should
always start with the simplest one that suits your needs.

By Primary Key
~~~~~~~~~~~~~~

The most basic way to query for a persisted document is by its
identifier (PHPCR path) using the
``DocumentManager::find(null, $id)`` method. Here is an
example::

    /** @var $em DocumentManager */
    $user = $em->find('MyProject\Domain\User', $id);

The return value is either the found document instance or null if no
instance could be found with the given identifier.

If you need several documents and know their paths, you can have a considerable
performance gain by using ``DocumentManager::findMany(null, $ids)`` as then
all those documents are loaded from the repository in one request.

You can also specify the class name instead of null to filter to only find
instances of that class. If you go through the repository for a document class
this is equivalent to calling find on the ``DocumentManager`` with that document
class.


By Simple Conditions
~~~~~~~~~~~~~~~~~~~~

To query for one or more documents based on several conditions that
form a logical conjunction, use the ``findBy`` and ``findOneBy``
methods on a repository as follows::

    /** @var $dm DocumentManager */

    // All users that are 20 years old
    $users = $dm->getRepository('MyProject\Domain\User')->findBy(array('age' => 20));

    // All users that are 20 years old and have a surname of 'Miller'
    $users = $dm->getRepository('MyProject\Domain\User')->findBy(array('age' => 20, 'surname' => 'Miller'));

    // A single user by its nickname
    $user = $dm->getRepository('MyProject\Domain\User')->findOneBy(array('nickname' => 'romanb'));

.. warning::

    Note that due to the nature of PHPCR, the primary identifier is no field.
    You can thus not use ``findBy(array('id' => '/my/path'))`` but should
    pass the ID into the ``find`` method. There is also findMany if you
    need to fetch several documents.

You can also query by references through the repository::

    $number = $dm->find('MyProject\Domain\Phonenumber', '/path/to/phone/number');
    $user = $dm->getRepository('MyProject\Domain\User')->findOneBy(array('phone' => $number->getUuid()));

Be careful that this only works by passing the uuid of the associated
document, not yet by passing the associated document itself.

The ``DocumentRepository::findBy()`` method additionally accepts orderings,
limit and offset as second to fourth parameters::

    $tenUsers = $dm
        ->getRepository('MyProject\Domain\User')
        ->findBy(array('age' => 20), array('name' => 'ASC'), 10, 0);

.. note::

    The ORM has a shortcut for querying by one field, using the ``__call``
    handler. In PHPCR-ODM this is not yet implemented, so the rest of this
    section does not work yet.

A DocumentRepository also provides a mechanism for more concise
calls through its use of ``__call``. Thus, the following two
examples are equivalent::

    // A single user by its nickname
    $user = $dm->getRepository('MyProject\Domain\User')->findOneBy(array('nickname' => 'romanb'));

    // A single user by its nickname (__call magic)
    $user = $dm->getRepository('MyProject\Domain\User')->findOneByNickname('romanb');


By Lazy Loading
~~~~~~~~~~~~~~~

Whenever you have a managed document instance at hand, you can
traverse and use any associations of that document that are
configured LAZY as if they were in-memory already. Doctrine will
automatically load the associated documents on demand through the
concept of lazy-loading.


By Query Builder
~~~~~~~~~~~~~~~~

PHPCR-ODM provides a query builder that wraps around native PHPCR queries.
See :doc:`query-builder`.


By Native Queries
~~~~~~~~~~~~~~~~~

PHPCR-ODM has no DQL (yet), but you can query using the JCR-SQL2 query
language or the JCR-QOM to build a query object tree.

You can create your SQL2 query by calling ``DocumentManager::createPhpcrQuery``
with the query as string, or get the phpcr-utils query builder by calling
``DocumentManager::createPhpcrQueryBuilder``. You can either execute that query
to get raw PHPCR nodes, or pass a PHPCR query to
``DocumentManager::getDocumentsByPhpcrQuery`` to get documents.


Custom Repositories
~~~~~~~~~~~~~~~~~~~

By default the ``DocumentManager`` returns a default implementation of
``Doctrine\ODM\PHPCR\DocumentRepository`` when you call
``DocumentManager::getRepository($documentClass)``. You can overwrite
this behaviour by specifying the class name of your own Document
Repository in the Annotation, XML or YAML metadata.

In applications that require lots of specialized queries, using a
custom repository is the recommended way of grouping these queries
in a central location::

    namespace MyDomain\Model;

    use Doctrine\ODM\PHPCR\DocumentRepository;

    /**
     * @PHPCR\Document(repositoryClass="MyDomain\Model\UserRepository")
     */
    class User
    {

    }

    class UserRepository extends DocumentRepository
    {
        public function getAllAdminUsers()
        {
            $qb = $this->dm->getQueryBuilder();
            // ... build some fancy query
            return $qb->getQuery()->getResult();
        }
    }

You can access your repository now by calling::

    /** @var $dm DocumentManager */

    $admins = $dm->getRepository('MyDomain\Model\User')->getAllAdminUsers();
