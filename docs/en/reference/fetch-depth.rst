Tuning the node preloading
==========================

Every document is stored in the repository by a PHPCR node. Some repositories allow to
provide a hint how many levels deep nodes should be loaded. If you know already that you
will need the child nodes of a node, you can tell this the DocumentManager to maybe gain
some performance. Be careful though, if the document you load has many children, and you only need
a few of them, preloading them all might be more expensive then the repeated trips to the
content repository to load the actually used children one by one.

This feature can either be used when mapping a :ref:`Children collection <hierarchy-mappings>` or via
an explicit call to ``UnitOfWork::setFetchDepth`` to set a global default fetch depth for this session::

    $dm->getUnitOfWork()->setFetchDepth(2);
