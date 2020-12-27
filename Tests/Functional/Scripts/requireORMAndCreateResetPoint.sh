#!/bin/bash

cd build/testproject/
if [ "$SYMFONY_VERSION" == "3.4.*" ];
then
    composer require doctrine/orm:2.7.5 --no-scripts
else
    composer require doctrine/orm --no-scripts
fi
git add .
git commit -m"add orm"
git tag reset.orm
git reset --hard reset
cd ../../
