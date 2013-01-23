#!/bin/bash

SCRIPT_NAME="${0##*/}"
SCRIPT_DIR="${0%/*}"

# if the script was started from the base directory, then the
# expansion returns a period
if test "$SCRIPT_DIR" == "." ; then
  SCRIPT_DIR="$PWD"
# if the script was not called with an absolute path, then we need to add the
# current working directory to the relative path of the script
elif test "${SCRIPT_DIR:0:1}" != "/" ; then
  SCRIPT_DIR="$PWD/$SCRIPT_DIR"
fi

# Install Midgard2
${SCRIPT_DIR}/../tests/travis_midgard_install.sh

# Set up CLI and install namespaces
cp ${SCRIPT_DIR}/../cli-config.midgard_sqlite.php.dist ${SCRIPT_DIR}/../cli-config.php
php ${SCRIPT_DIR}/../bin/phpcrodm doctrine:phpcr:register-system-node-types

