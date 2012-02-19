#!/bin/bash

git submodule update --init --recursive

mysql -e 'create database IF NOT EXISTS phpcr_odm_tests;' -u root

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
php $DIR/vendor/vendors_doctrine_dbal.php

cp cli-config.doctrine_dbal.php.dist cli-config.php
./bin/phpcr jackalope:init:dbal
./bin/phpcr doctrine:phpcr:register-system-node-types
