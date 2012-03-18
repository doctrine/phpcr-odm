#!/bin/bash

mysql -e 'create database IF NOT EXISTS phpcr_odm_tests;' -u root

cp cli-config.doctrine_dbal.php.dist cli-config.php
./bin/phpcr jackalope:init:dbal
./bin/phpcr doctrine:phpcr:register-system-node-types
