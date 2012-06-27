#!/bin/bash

php composer.phar require jackalope/jackalope-jackrabbit:1.*

./vendor/jackalope/jackalope-jackrabbit/bin/jackrabbit.sh

cp cli-config.jackrabbit.php.dist cli-config.php
./bin/phpcr doctrine:phpcr:register-system-node-types
