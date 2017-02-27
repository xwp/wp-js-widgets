#!/bin/bash

set -e

cd "$( dirname "$0" )/.."

phantomjs --webdriver=8643 --web-security=no &
pjspid=$!
sleep 3 # Wait for it to be ready.
echo $pjspid

export ACCEPTANCE_PANTHEON_SITE_USERNAME=admin
export ACCEPTANCE_PANTHEON_SITE_PASSWORD=admin
export BEHAT_PARAMS='{"extensions": {"Behat\\MinkExtension": {"base_url": "http://src.wordpress-develop.dev", "selenium2":{ "wd_host": "http://localhost:8643/wd/hub" }} }}'

vendor/bin/behat -c tests/behat/behat.yml

kill $pjspid
