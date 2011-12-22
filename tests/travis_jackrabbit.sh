#!/bin/bash

git submodule update --init --recursive

./lib/vendor/jackalope/tests/travis_jackrabbit.sh

cp cli-config.jackrabbit.php.dist cli-config.php
./bin/phpcr doctrine:phpcr:register-system-node-types
