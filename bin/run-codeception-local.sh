#!/bin/bash

set -e

cd "$( dirname "$0" )/.."

webdriver_port=4444
phantomjs --webdriver=$webdriver_port &
phantomjs_pid=$!
echo "Waiting for PhantomJS..."
port_open_check_count=0
while ! nc -vz localhost $webdriver_port 2> /dev/null; do
  port_open_check_count=$(( $port_open_check_count + 1 ))
  if [[ $port_open_check_count -gt 5 ]]; then
    echo "Failed to open PhantomJS"
    exit 1
  fi
  sleep 1;
done

export ACCEPTANCE_WP_USER=admin
export ACCEPTANCE_WP_PASSWORD=admin
export CODECEPTION_ACCEPTANCE_URL=http://src.wordpress-develop.dev

exit_code=0
if ! ./vendor/bin/codecept run acceptance --debug; then
  exit_code=1
fi

kill $phantomjs_pid
exit $exit_code
