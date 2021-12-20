#!/bin/bash

cd build/testproject/
VENDOR_VERSION=""
CURRENT_REF=${GITHUB_HEAD_REF:-$GITHUB_REF}
CURRENT_BRANCH=${CURRENT_REF#refs/heads/}
if [[ $CURRENT_BRANCH -ne "master" ]]; then
    composer config minimum-stability dev
    VENDOR_VERSION=":dev-${CURRENT_BRANCH}"
fi
composer require doctrine/orm${VENDOR_VERSION} --with-all-dependencies --no-scripts
git add .
git commit -m"add orm"
git tag reset.orm
git reset --hard reset
cd ../../
