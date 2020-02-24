#!/bin/bash

cd build/testproject/
composer require auxmoney/opentracing-bundle-doctrine-dbal:dev-${BRANCH} webmozart/assert --no-scripts
composer fix-recipes
cd ../../
