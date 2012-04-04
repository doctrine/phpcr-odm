#!/bin/bash

# Install libgda MySQL connector
sudo apt-get install -y libgda-4.0-mysql

# Create the database
mysql -e 'create database midgard2_test;'

# Install Midgard2
./tests/travis_midgard_install.sh

# Set up CLI and install namespaces
cp cli-config.midgard_mysql.php.dist cli-config.php
php bin/phpcr doctrine:phpcr:register-system-node-types
