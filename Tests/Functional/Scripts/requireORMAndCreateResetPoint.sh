#!/bin/bash

cd build/testproject/
composer require doctrine/orm --no-scripts
git add .
git commit -m"add orm"
git tag reset.orm
git reset --hard reset
cd ../../
