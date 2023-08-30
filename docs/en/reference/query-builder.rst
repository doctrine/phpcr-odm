.. _qbref:

The QueryBuilder
================

The PHPCR-ODM query builder enables you to create queries at the abstraction
level of the ODM using a fluent interface.

An example query::

    $qb = $documentManager->createQueryBuilder();

    $qb->from()->document('Blog\User', 'u');
    $qb->where()->eq()->field('u.name')->literal('dtl');

    $query = $qb->getQuery();

This query will select all documents of class ``Blog\User`` which
have a value of ``dtl`` for the field ``name``.

The first line retrieves a new instance of the query builder from the document
manager.

The second specifies that we want documents of type ``Blog\User`` and that
the string "u" will be used as the alias name.

The third line says that we want only documents where the value of the
field "name" from the alias named "u" is equal (eq) to the
literal string "dtl".

The forth and final line retrieves the :ref:`query <queryref>` object.

Alternatively the above query can be written more fluently by the using
``end()`` terminators as follows::

    $qb = $documentManager->createQueryBuilder();
    $qb->from()
        ->document('Blog\User')
      ->end()
      ->where()
        ->eq()
          ->field('a.name')
          ->literal('dtl');

Concepts
--------

Leaf and Factory Nodes
~~~~~~~~~~~~~~~~~~~~~~

The query builder is a tree structure composed of two different categories of
nodes. *Factory nodes* and *Leaf nodes*. Factory nodes create and
add new nodes to the query builder tree and then return the newly created node. Factory methods
accept no arguments and always have *children*. A factory node has zero
arguments.

Leaf nodes have no children and always return the parent node after adding
themselves to the query builder tree. The parent node is always a factory
node and the leaf node always has arguments::

    // the query builder is a factory node
    $qb = $dm->createQueryBuilder();

    // from() returns a new factory node
    $from = $qb->from();

    // document() is a leaf node, it returns the parent factory
    $from = $from->document('Post', 'p');

    // end() returns the parent, in this case the query builder.
    $qb = $from->end();

Fluent Interface
~~~~~~~~~~~~~~~~

The API makes use of a fluent API which enables an entire query to be
constructed in a single, unbroken, statement.

Factory node methods append nodes as children to themselves and return either
other factory nodes or, if the factory method returns a leaf, the method will
return its owning class instance::

    $qb->where()->eq()->field('p.title', 'p')->literal('My Post');

In the example above:

* The ``where`` method of the ``QueryBuilder`` adds and returns a
  ``ConstraintFactory`` which provides the ``eq()`` method.

* The ``eq()`` method adds and returns an ``OperandFactory`` which contains the
  ``field()`` and ``literal()`` methods.

Up to this point the return values have all been factory classes.

* The ``field()`` and ``literal()`` methods add leaf nodes and they return the
  same class of which they are part - the ``OperandFactory`` - the same node
  which provides the ``eq()`` method.

This model presents a problem when we want to proceed to a previous node
without breaking the chain, this is where the ``end()`` method comes in.

The ``end()`` method is a special method that will always return the parent of the
current node, allowing us to construct the query in full without breaking the
chain. A practical application of this is when we do more complicated things,
such as chaining operands::

    $qb->where()->eq()->lowerCase()->field('p.title')->end()->literal('my post');

Here the ``lowerCase()`` method would return the ``LowerCase`` operand, which will
transform the value of its child member to lowercase. Because ``field()`` will
return its parent we need to call ``end()`` to go back once more to the
``ConstraintFactory`` (as returned by ``eq()``).

.. note::

    It is only necessary to add an ``end()`` terminator when you wish to
    append additional leaf nodes to the *same statement*. In this document we
    will not add ``end()`` terminators where they are not required.

Types and Cardinality
~~~~~~~~~~~~~~~~~~~~~

Each node has an associated node type::

    $qb->getNodeType(); // returns "builder"
    $qb->where()->getNodeType(); // returns "where"
    $qb->andWhere()->getNodeType(); // returns "where"
    $qb->where()->eq()->getNodeType(); // returns "constraint"
    $qb->where()->eq()->field()->getNodeType(); // returns "operand"

Node types (not to be confused with PHPCR node types) are used to validate the
query builder trees structure. Each factory node declares how many children of
each type it is allowed, this is the node child cardinality map. The
:doc:`Query Builder Reference <query-builder-reference>` document lists the cardinalities of all the
factory nodes.

Exceeding or not achieving the minimum or maximum child cardinality for a
given node type will cause an exception to be thrown when retrieving the
query, for example::

    // throws exception, query builder node needs at least one "from".
    $qb->getQuery();

    // throws exception, eq() needs one dynamic and one static operand
    $qb->where()->eq()->field('p.title');
    $qb->getQuery();

    // throws exception, eq() needs one dynamic and one static operand
    $qb->where()->eq()->field('p.title')->field('p.name');
    $qb->getQuery();

    // ok
    $qb->where()->eq()->field('p.title')->literal('My Post');
    $qb->getQuery();

The cardinality for each node is documented in the
:doc:`query-builder-reference`, for an example see
:ref:`qbref_node_querybuilder`.

Aliases and fields
~~~~~~~~~~~~~~~~~~

The term "alias" refers to the string that is assigned to a document source,
either a ``SourceFrom`` or a ``SourceJoin``::

    $qb->from('Blog\Post', 'post');

In the example above, "post" is the alias. The alias is subsequently used
whenever the source is referenced. The following example show some instances
where we reference the alias::

    $qb->where()->eq()->field('post.title')->literal('foobar');
    // or
    $qb->where()->fieldIsset('post.username');
    // or
    $qb->where()->child('/blog', 'post');

The term "field" refers to the property of an aliased document. In the first
of the above examples we reference the property ``$post`` on the dcoument
``Blog\Post``. Note that the alias and property name are delimited by a dot
".".

Retrieving a query builder instance
-----------------------------------

You can create instances of the query builder in one of two ways, either via
the ``DocumentManager`` or via a ``DocumentRepository``.

Via the document manager
~~~~~~~~~~~~~~~~~~~~~~~~

You can instantiate the ``QueryBuilder`` with the ``DocumentManager`` using the
``createQueryBuilder`` method::

    $qb = $documentManager->createQueryBuilder();

Via a document repository
~~~~~~~~~~~~~~~~~~~~~~~~~

You can also instantiate a ``QueryBuilder`` from a ``DocumentRepsitory``
instance, doing so will automatically select only those records which are
associated with the ``DocumentRepository``::

   $postsRepository = $dm->getRepository('Blog\Post');
   $qb = $postsRepository->createQueryBuilder('p');
   $posts = $qb->getQuery()->execute();

The above code block will select all documents in the document tree of class
``Blog\Post``. This feature is especially useful within a document repository
class.

Example showing the use of the query builder in a ``DocumentRepository``::

   namespace Blog;

   use Doctrine\ODM\PHPCR\DocumentRepository;

   class PostRepository extends DocumentRepository
   {
       public function getPostsByAuthor($authorName)
       {
           $qb = $this->createQueryBuilder('p');
           $qb->where()->eq()->field('p.author')->literal('dtl');

           return $qb->getQuery()->execute();
       }
   }

Note that we specify the string "a" as an argument to
``createQueryBuilder`` - this is the alias name (analagous to "alias" in
Doctrine ORM terms), more on these later.

Working with the QueryBuilder
-----------------------------

.. _qbref_from:

Specifying the document source - from
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ODM query builder requires you to specify a source from which records
should be selected. This source can either be a specified document or a
"join". Joins join two sources using a given "join condition".

.. note::

    A raw PHPCR query will allow you to select from ALL records and to hydrate
    a result set of mixed document classes, the PHPCR-ODM query builder
    requires however that you specify a single document source - this is because the
    PHPCR query builder is not bound to the field mappings of the ODM.

From Single Source
""""""""""""""""""

.. code-block:: php

    // select documents of class Foo\Bar.
    $qb->from()->document('Blog\Post', 'p');

The above example will setup the query builder to select documents only of class
``Blog\Post`` using the *alias name* "p". The alias name is the alias used
in subsequent references to this document source or properties within this
document.

From Joined Source
""""""""""""""""""

Joins allow you to take other documents into account when selecting records.

When selecting from multiple sources it is mandatory to specify a *primary
alias* as an argument to the ``from`` factory node.

The following will retrieve a collection of ``Blog\Post`` documents for active users::

    // select documents from a join
    $qb->from('p')->joinInner()
        ->left()->document('Blog\Post', 'p')->end()
        ->right()->document('Blog\User', 'u')->end()
        ->condition()->equi('p.username', 'u.username');

    $qb->where()
        ->eq()->field('u.status')->literal('active');

    $posts = $qb->getQuery()->execute();

Using the document source ``p`` as the primary document source we select from
a ``joinInner`` source, with ``Blog\Post`` documents on the left (alias ``p``)
and ``Blog\User`` documents on the right (alias ``u``) we join the left and
right sources using an ``equi`` (equality) join on the ``username`` columns.

We then specify that only blog posts which have associated users with the
status "active" are selected.

Joining with an Association
"""""""""""""""""""""""""""

The following is another example which joins on an *association*.  The
``CmsUser`` class is associated with a single address::

    $qb->fromDocument('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        ->addJoinInner()
        ->right()->document('Doctrine\Tests\Models\CMS\CmsAddress', 'a')->end()
        ->condition()->equi('u.address', 'a.uuid');
        ->where()->eq()->field('a.city')->literal('Lyon');
    $users = $qb->getQuery()->execute();

This query selects all ``CmsUser`` documents which have an associated address
where the ``city`` field has a value of ``Lyon``.

For detailed information see :ref:`the query builder reference <qbref_method_querybuilder_from>`.

.. _qbref_select:

Selecting specific properties - select
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can specify fields to populate with values using the ``select`` factory
node, this is currently only useful when :ref:`hydrating to PHPCR nodes
<queryref_hydration>`. The default (object) hydration will *always* hydrate
all fields regardless of what you specify::

   $qb->from('Demo\User', 'u');
   $qb->select()
     ->field('u.firstname')
     ->field('u.lastname');

   $query = $qb->getQuery();

   // field selection only used when hydrating to nodes
   $node = $query->getSingleResult(Query::HYDRATE_PHPCR);
   $node->getProperty('firstname');

.. _qbref_limiting:

Limiting the number of results
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can specify a maximum number of results and the index of the first result
(the offset)::

   // select a maximum of 10 records.
   $qb->from()->document('User')
      ->setMaxResults(10);

   // select a maximum of 10 records from the position of the 20th record.
   $qb->from()->document('User')
      ->setMaxResults(10)
      ->setFirstResult(20);

.. _qbref_where:

Specifying selection criteria
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can specify selection criteria using the ``where`` factory node::

   // setup our document source with alias "u"
   $qb->from('Blog\User', 'u');

   // where name is "daniel"
   $qb->where()
     ->eq()->field('u.name')->literal('daniel');

   // where username is "dtl" AND name is "daniel"
   $qb->where()->eq()->field('u.username')->literal('dtl');
   $qb->andWhere()->eq()->field('u.name')->literal('daniel');

   // which is equivalent to
   $qb->where()->andX()
     ->eq()->field('u.username')->literal('dtl')->end()
     ->eq()->field('u.name')->literal('daniel');

   // where username is "dtl" OR name is "daniel"
   $qb->where()->eq()->field('u.username')->literal('dtl');
   $qb->orWhere()->eq()->field('u.name')->literal('daniel');

   // which is equivalent to
   $qb->where()->orX()
     ->eq()->field('u.username')->literal('dtl')->end()
     ->eq()->field('u.name')->literal('daniel');

   // where the lowercase value of node name is equal to dtl
   $qb->where()
       ->eq()
           ->lowercase()->localName('a')->end()
           ->literal('dtl');

   // where the lowercase value of node name is NOT equal to dtl
   $qb->where()
       ->eq()
           ->lowercase()->localName('a')->end()
           ->literal('dtl');

.. note::

    If your code builds a query from distributed places, it is perfectly legal
    to only use ``andWhere`` / ``orWhere`` without a first ``where``.

.. _qbref_ordering:

Ordering results
~~~~~~~~~~~~~~~~

You can specify the property or properties by which to order the queries
results with the ``orderBy`` factory node. You can specify additional
orderings with ``addOrderBy``.

Add a single ordering::

   $qb->orderBy()
     ->asc()->field('u.username'); // username asc

Descending::

   $qb->orderBy()
     ->desc()->field('u.username');

Add three orderings - equivilent to the SQL ``ORDER BY username ASC, name ASC, website DESC``::

   $qb->orderBy()
     ->asc()->field('u.username')->end()
     ->asc()->field('u.name')->end()
     ->desc()->field('u.website');

Adding multiple orderings using ``addOrderBy``::

   $qb->orderBy()->asc()->field('u.username');
   $qb->addOrderBy()->asc()->field('u.name');

.. _qb-translation:

Querying translated documents
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If your documents contain :doc:`translated fields <multilang>`, the
query builder automatically handles them both for ``where`` and ``orderBy``
when using the annotation or child translation strategy.

It will use the "current" locale according to the LocaleChooser. If you want
to query in a different locale, you can also specify the locale explicitly::

    $qb = $dm->createQueryBuilder();
    $qb
        ->setLocale('fr')
        ->from()
            ->document('Demo\Document', 'd')
        ->end()
        ->where()->fieldIsset('d.title')->end()
        ->orderBy()
            ->asc()->field('d.title')->end()
        ->end();

Additional notes
----------------

Querying multivalue fields
~~~~~~~~~~~~~~~~~~~~~~~~~~

Multivalue fields can be queried using either `eq()` or `like()` in the same
way as you would for a single value field::

   // Find all posts which have a tag "general"
   $qb->where()->eq()->field('p.tags')->literal('general');

   // Find all posts which have a tag containing the string "foo"
   $qb->where()->like()->field('p.tags')->literal('%foo%');

Using the Query Builder in Tests
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Mocking the query builder in a unit test is not easy - it requires that you
mock the node classes and setup the methods to return the correct node classes
at the correct time. In short, we recommend that you use the real query
builder class and a special companion class, the ``QueryBuilderTester``.

The ``QueryBuilderTester`` provides a couple of methods:

* **getNode**: Retrieve a node from the query builder by its "node type" path.
* **dumpNodePaths**: Dump all the "node type" paths in the query builder
  instance.

.. code-block:: php

    use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
    use Doctrine\ODM\PHPCR\Tools\Test\QueryBuilderTester;

    $test = // pretend we have a PHPUnit_Framework_TestCase
    $qb = new QueryBuilder;
    $qb->where()->eq()->field('p.title')->literal('Foobar');

    $qbTester = new QueryBuilderTester($qb);

    // ->getNode - retrieve node by its nodetype path.
    $literalNode = $qbTester->getNode('where.constraint.operand_statuc');
    $fieldNode = $qbTester->getNode('where.constraint.operand_dynamic');

    $test->assertEquals('Foobar', $literalNode->getValue());
    $test->assertEquals('p', $fieldNode->getSelectorName());
    $test->assertEquals('title', $fieldNode->getPropertyName());

    $qb->where()->andX()
        ->eq()->field('p.title')->literal('Foobar')->end()
        ->fieldIsset('p.username');

    // first constraint is the "andX", the second constraint node of "andX" is "fieldIsset"
    $fieldIsset = $qbTester->getNode('where.constraint.constraint[1]');

    // ->dumpNodePaths - dump all the node paths of the query builder
    $res = $qbTester->dumpNodePaths();
