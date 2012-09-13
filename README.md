PHPCR ODM for Doctrine2
=======================

# Current Status

* most key features implemented
* alpha stage
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/PHPCR)
* [![Build Status](https://secure.travis-ci.org/doctrine/phpcr-odm.png)](http://travis-ci.org/doctrine/phpcr-odm)


# Preconditions

* php >= 5.3
* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))
* phpunit >= 3.6 (if you want to run the tests)
* [composer](http://getcomposer.org/)


# Documentation

Please refer to [doctrine-project.org](http://docs.doctrine-project.org/projects/doctrine-phpcr-odm/en/latest/) for the documentation.


# Running the tests

This examples shows how to run the tests for jackrabbit. You can run the tests
for the other backends. Just replace jackrabbit with the name of the backend
you want to run.

1. Make sure you have installed the dependencies
2. Run this command to download jackrabbit and launch it (requires wget)

    ./tests/travis_jackrabbit.sh

3. Run the tests:

    phpunit -c tests/phpunit_jackrabbit.xml.dist
