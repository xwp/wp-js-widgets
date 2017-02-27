#!/bin/bash

set -e

cd "$( dirname "$0" )/"

phantomjs --webdriver=8643 &
pjspid=$!
sleep 3 # Wait for it to be ready.
echo $pjspid

export ACCEPTANCE_PANTHEON_SITE_USERNAME=admin ACCEPTANCE_PANTHEON_SITE_PASSWORD=admin BEHAT_PARAMS='{"extensions": {"Behat\\MinkExtension": {"base_url": "http://src.wordpress-develop.dev"} }}'

../vendor/bin/behat -c ../tests/behat/behat.yml

kill $pjspid
