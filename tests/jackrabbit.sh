#!/bin/bash

# phpcr-odm fails with jackrabbit 2.8, use this until https://github.com/jackalope/jackalope-jackrabbit/pull/157 is fixed

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

VERSION=2.20.4

JAR=jackrabbit-standalone-$VERSION.jar

# download jackrabbit jar from archive, as the dist only contains the latest
# stable versions
if [ ! -f "$DIR/$JAR" ]; then
    if ! [ -x "$(command -v wget)" ]; then
      echo 'Error: wget is not installed.' >&2
      exit 1
    fi

    wget http://archive.apache.org/dist/jackrabbit/$VERSION/$JAR
fi

java -jar $DIR/$JAR&

echo "Waiting until Jackrabbit is ready on port 8080"
while [[ -z `curl -s 'http://localhost:8080' ` ]]
do
    echo -n "."
    sleep 2s
done

echo "Jackrabbit is up"
