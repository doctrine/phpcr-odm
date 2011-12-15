#!/bin/bash

git submodule update --init --recursive

# Install Midgard2
./lib/vendor/Midgard/PHPCR/tests/travis_midgard.sh

# Copy PHPCR schemas to Midgard's global schema dir
sudo cp -r ./lib/vendor/Midgard/PHPCR/data/share/* /usr/share/midgard2
sudo cp ./data/share/odm_schemas.xml /usr/share/midgard2/schema
