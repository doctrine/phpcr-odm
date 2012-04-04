#!/bin/bash

# Install libgda MySQL connector
sudo apt-get install -y libgda-4.0-mysql

# Create the database
mysql -e 'create database midgard2_test;'

# Install Midgard2
./travis_midgard_install.sh

# Copy PHPCR schemas to Midgard's global schema dir
sudo cp -r ./lib/vendor/Midgard/PHPCR/data/share/* /usr/share/midgard2
sudo cp ./data/share/odm_schemas.xml /usr/share/midgard2/schema

# Set up CLI and install namespaces
cp cli-config.midgard_mysql.php.dist cli-config.php
php bin/phpcr doctrine:phpcr:register-system-node-types
