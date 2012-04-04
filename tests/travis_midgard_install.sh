#!/bin/bash

# Download installation script from phpcr-midgard2
wget https://raw.github.com/midgardproject/phpcr-midgard2/master/tests/travis_midgard.sh 
chmod +x ./travis_midgard.sh
./travis_midgard.sh

# Copy PHPCR schemas to Midgard's global schema dir
wget --directory-prefix=/usr/share/midgard2/schema https://github.com/midgardproject/phpcr-midgard2/raw/master/data/share/schema/midgard_namespace_registry.xml
wget --directory-prefix=/usr/share/midgard2/schema https://github.com/midgardproject/phpcr-midgard2/raw/master/data/share/schema/midgard_tree_node.xml

# Copy odm schema
sudo cp ./data/share/odm_schemas.xml /usr/share/midgard2/schema
