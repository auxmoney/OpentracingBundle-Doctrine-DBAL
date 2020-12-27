#!/bin/bash
shopt -s extglob

cd build/testproject/
composer require auxmoney/opentracing-bundle-doctrine-dbal webmozart/assert --no-scripts
composer fix-recipes
rm -fr vendor/auxmoney/opentracing-bundle-doctrine-dbal/*
cp -r ../../!(build|vendor) vendor/auxmoney/opentracing-bundle-doctrine-dbal
composer dump-autoload
cd ../../
