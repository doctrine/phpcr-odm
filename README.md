# PHPCR ODM for Doctrine2

[![Build Status](https://secure.travis-ci.org/doctrine/phpcr-odm.png)](http://travis-ci.org/doctrine/phpcr-odm)
[![Latest Stable Version](https://poser.pugx.org/doctrine/phpcr-odm/version.png)](https://packagist.org/packages/doctrine/phpcr-odm)
[![Total Downloads](https://poser.pugx.org/doctrine/phpcr-odm/d/total.png)](https://packagist.org/packages/doctrine/phpcr-odm)


## Requirements

* php >= 5.3
* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))
* [composer](http://getcomposer.org/)
* See also the `require` section of [composer.json](composer.json)


## Documentation

Please refer to [doctrine-project.org](http://docs.doctrine-project.org/projects/doctrine-phpcr-odm/en/latest/) for the documentation.


## Contributing

Pull requests are welcome. Please include tests to prevent regressions whenever
possible.

Thanks to
[everyone who has contributed](https://github.com/doctrine/phpcr-odm/contributors) already.


## Running the tests

This examples shows how to run the tests for jackrabbit. You can run the tests
for the other backends. Just replace jackrabbit with the name of the backend
you want to run.

1. Make sure you have installed the dependencies
2. Run this command to download jackrabbit and launch it (requires wget)

    ./tests/travis_jackrabbit.sh

3. Run the tests:

    phpunit -c tests/phpunit_jackrabbit.xml.dist
