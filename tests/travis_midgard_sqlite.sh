#!/bin/bash

# Install Midgard2
./tests/travis_midgard_install.sh

# Set up CLI and install namespaces
cp cli-config.midgard_sqlite.php.dist cli-config.php
php bin/phpcr doctrine:phpcr:register-system-node-types

