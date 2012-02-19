#!/bin/bash

git submodule update --init --recursive

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
php $DIR/vendor/vendors_jackrabbit.php

./lib/vendor/jackalope-jackrabbit/tests/travis.sh

cp cli-config.jackrabbit.php.dist cli-config.php
./bin/phpcr doctrine:phpcr:register-system-node-types
