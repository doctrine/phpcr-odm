# PHPCR ODM for Doctrine2

[![Build Status](https://secure.travis-ci.org/doctrine/phpcr-odm.png?version=master)](http://travis-ci.org/doctrine/phpcr-odm)
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

1. Make sure you have installed the dependencies that can't be installed with
Composer (e.g. when running `travis_doctrine_dbal.sh`, install MySQL). Do not
run `composer install`, it will not work because phpcr-odm requires virtual
packages that change depending on the backend you want to test.
2. Run this command to download jackrabbit and launch it (requires wget)

    ./tests/travis_jackrabbit.sh

Please note that this will also require implementations for virtual packages
that match the backend you want to test, resulting in a change in your
`composer.json`. Make sure you do not check in this change into version control.

3. Run the tests:

    phpunit -c tests/phpunit_jackrabbit.xml.dist
