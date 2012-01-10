#!/bin/bash

git submodule update --init --recursive

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

mysql -e 'create database IF NOT EXISTS phpcr_odm_tests;' -u root

php $DIR/../lib/vendor/jackalope/tests/vendor/vendors_doctrine_dbal.php

cp cli-config.doctrine_dbal.php.dist cli-config.php
./bin/phpcr jackalope:init:dbal
./bin/phpcr doctrine:phpcr:register-system-node-types
