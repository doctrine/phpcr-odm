#!/bin/bash

composer require jackalope/jackalope-doctrine-dbal:~1.0 --no-update
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

mysql -e 'create database IF NOT EXISTS phpcr_odm_tests;' -u root

cp ${SCRIPT_DIR}/../cli-config.doctrine_dbal.php.dist ${SCRIPT_DIR}/../cli-config.php
${SCRIPT_DIR}/../bin/phpcrodm jackalope:init:dbal --force
${SCRIPT_DIR}/../bin/phpcrodm doctrine:phpcr:register-system-node-types
