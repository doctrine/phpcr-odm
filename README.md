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

There are separate test setups for the `doctrine-dbal` and the `jackrabbit` PHPCR implementations.
Before installing the composer dependencies, you will need to prepare the database for storage and
choose a `phpcr/phpcr-implementation`.
Doing so will change the `composer.json` file - please make sure you do not check in this change
into version control.

### Setting up to test with Jackrabbit

1. Make sure you have `java` and `wget` installed, then run this script to install and start jackrabbit:
    ```
        tests/script_jackrabbit.sh
    ```
2. Require the PHPCR implementation:
   ```
        composer require jackalope/jackalope-jackrabbit --no-update
    ```
3. Now you can install all dependencies with:
    ```
        composer install
    ```
4. Now you can run the tests:
    ```
    vendor/bin/phpunit -c tests/phpunit_jackrabbit.xml.dist
    ```
   You can also copy the phpunit dist file to `./phpunit.xml` to have it selected by default, or
   if you need to customize any configuration options.

### Setting up to test with Doctrine-DBAL

1. For `doctrine-dbal`, make sure that MySQL is installed. If the connection parameters in
   `cli-config.doctrine_dbal.php.dist` are not correct, manually create `cli-config.php` and adjust
   the options as needed. Then run the script to initialize the repository in the database:
    ```
        tests/script_doctrine_dbal.sh
    ```
2. Require the PHPCR implementation
    ```
        composer require jackalope/jackalope-doctrine-dbal --no-update
    ```
3. Now you can install all dependencies with:
    ```
        composer install
    ```
4. Now you can run the tests:
    ```
    vendor/bin/phpunit -c tests/phpunit_doctrine_dbal.xml.dist
    ```
   You can also copy the phpunit dist file to `./phpunit.xml` to have it selected by default, or
   if you need to customize any configuration options.
