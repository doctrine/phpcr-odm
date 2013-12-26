#!/bin/bash

composer require jackalope/jackalope-jackrabbit:~1.0 --no-update
composer update --prefer-source

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

./vendor/jackalope/jackalope-jackrabbit/bin/jackrabbit.sh

cp ${SCRIPT_DIR}/../cli-config.jackrabbit.php.dist ${SCRIPT_DIR}/../cli-config.php
${SCRIPT_DIR}/../bin/phpcrodm doctrine:phpcr:register-system-node-types
