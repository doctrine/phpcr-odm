# PHPCR ODM for Doctrine2

[![Build Status](https://github.com/doctrine/phpcr-odm/actions/workflows/test-application.yaml/badge.svg?branch=2.x)](https://github.com/doctrine/phpcr-odm/actions/workflows/test-application.yaml)
[![Latest Stable Version](https://poser.pugx.org/doctrine/phpcr-odm/version.png)](https://packagist.org/packages/doctrine/phpcr-odm)
[![Total Downloads](https://poser.pugx.org/doctrine/phpcr-odm/d/total.png)](https://packagist.org/packages/doctrine/phpcr-odm)


## Requirements

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

There are separate test sets for `dbal` and `jackrabbit` backends. 

1. Before installing dependencies you need to add `phpcr/phpcr-implementation` package. It will also require
   implementations for virtual packages  that match the backend you want to test, resulting in a change in your
   `composer.json`. Make sure you do not check in this change into version control.
   1. For `jackrabbit` run following command:
    ```
        composer require jackalope/jackalope-jackrabbit:2.* --no-update
    ```
   2. For `dbal`  run following command:
    ```
        composer require jackalope/jackalope-doctrine-dbal:2.* --no-update
    ```
2. Next install all dependencies with:
    ```
        composer install
    ```
3. To install the dependencies that can't be installed with Composer you need to 
    1. For `jackrabbit`, make sure that you `Java` and `wget` is installed, and next run following command:
    ```
        tests/script_jackrabbit.sh
    ```
    2. For `dbal`, make sure that MySQL is installed, and next run following command:
    ```
        tests/script_doctrine_dbal.sh
    ```
4. Now you can run the tests
    1. For `jackrabbit`, make sure that you `Java` and `wget` is installed, and next run following command:
    ```
    vendor/bin/phpunit -c tests/phpunit_jackrabbit.xml.dist
    ```
    2. For `dbal`, make sure that MySQL is installed, and next run following command:
    ```
    vendor/bin/phpunit -c tests/phpunit_doctrine_dbal.xml.dist
    ```
