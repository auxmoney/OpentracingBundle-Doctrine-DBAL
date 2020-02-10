#!/bin/bash

cd build/testproject/
composer require auxmoney/opentracing-bundle-doctrine-dbal:dev-${BRANCH}
cd ../../
