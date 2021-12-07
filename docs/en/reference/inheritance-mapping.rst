Inheritance Mapping
===================

Document class inheritance
--------------------------

Mapped documents can simply extend each other. Mappings of the base class are
inherited for the extending class. It is even possible to overwrite the mapping
of the base class, but you should be very careful with that, as this can lead
to semantically broken data structures.

.. note::

    Overwriting mixins works as overwriting any other setting. This means that
    if your mapping has any mixins, you need to explicitly repeat any mixins
    from anchestor classes that you want to keep.

Typically, the purpose of such inheritance is to model the is-a relationship in
your models and to reuse the mappings and functions of the base class.


.. note::

    Contrary to ORM, the PHPCR-ODM with its NoSQL nature can handle documents
    that extend each other just like any other document. This means both
    your super class and the extending classes can be concrete classes and
    be stored in the repository. If you explicitly want to declare that objects
    of a class should not be persisted, you can use ``MappedSuperclass`` to
    provide common mappings but prevent such objects from being stored.

    There are also no restrictions on referencing between any of those classes.

    You can also query for them, but if you query for documents of a specific
    type, you will not find the super type documents.

To use this feature, just have your document classes extend each other. You should not
repeat mappings that exist in the super class, they are inherited automatically.

.. tip::

    Doctrine will follow the inheritance of your document class. As soon as one
    class is not mapped, it will stop the process. Make sure to map all parent
    classes if you have a deep inheritance tree.
