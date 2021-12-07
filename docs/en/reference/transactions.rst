.. _transactions:

Transactions
============

The ODM provides transaction support if the underlying persistence layer supports them.


transactional()
~~~~~~~~~~~~~~~

The ``Doctrine\ODM\PHPCR\DocumentManager#transactional()`` method provides support for, executing
functions in a transactional-safe manner if the underlying PHPCR transport supports it. Any PHP
``callable`` type is supported.

Example::

    use Doctrine\ODM\PHPCR\DocumentManager;

    $documentManager = new DocumentManager($session);

    $documentManager->transactional(function(DocumentManager $documentManager) {
        $document = new Article('Cool story!');
        $comment  = $article->comment('Amazing indeed!');

        $documentManager->persist($document);
        $documentManager->persist($comment);
    });

As you can see, there is also no need to call ``Doctrine\ODM\PHPCR\DocumentManager#flush()``,
since ``Doctrine\ODM\PHPCR\DocumentManager#transactional()`` will do it implicitly.

If an ``Exception`` is thrown during ``Doctrine\ODM\PHPCR\DocumentManager#transactional()``
execution, then the ``Doctrine\ODM\PHPCR\DocumentManager`` will be closed and the current transaction
rolled back.

.. note::

    Multiple ``Doctrine\ODM\PHPCR\DocumentManager#transactional()`` calls are currently not supported,
    as nested transaction support is not supported either.

.. note::

    ``Doctrine\ODM\PHPCR\DocumentManager#transactional()`` will be executed in a non transactional-safe
    way if the underlying PHPCR transport does not support transactions.
