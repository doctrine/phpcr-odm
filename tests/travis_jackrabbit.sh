#!/bin/bash

./vendor/jackalope/jackalope-jackrabbit/tests/jackrabbit.sh

cp cli-config.jackrabbit.php.dist cli-config.php
./bin/phpcr doctrine:phpcr:register-system-node-types
