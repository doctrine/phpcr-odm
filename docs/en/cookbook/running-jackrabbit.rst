.. index::
    single: Jackrabbit

Running Jackrabbit
==================

`Apache Jackrabbit`_ is the reference implementation for the Java Content
Repository (JCR) standard. `jackalope-jackrabbit`_ implements PHPCR on top
of the Jackrabbit remoting capabilities.

Get the latest Apache Jackrabbit version from the project's
`official download page`_. Place it in a folder where you want it
to create the repository fields and start is with:

.. code-block:: bash

    $ java -jar jackrabbit-standalone-*.jar

By default the server is listening on the 8080 port, you can change this
by specifying the port on the command line:

.. code-block:: bash

    $ java -jar jackrabbit-standalone-*.jar --port 8888

For unix systems, you can get the start-stop script for ``/etc/init.d``
`here`_.

More information about `running a Jackrabbit server`_ can be found on the
Jackalope wiki.

.. _`Apache Jackrabbit`: http://jackrabbit.apache.org/jcr/index.html
.. _`jackalope-jackrabbit`: https://github.com/jackalope/jackalope-jackrabbit
.. _`official download page`: http://jackrabbit.apache.org/jcr/downloads.html
.. _`here`: https://github.com/sixty-nine/Jackrabbit-startup-script
.. _`running a Jackrabbit server`: https://github.com/jackalope/jackalope/wiki/Running-a-jackrabbit-server
