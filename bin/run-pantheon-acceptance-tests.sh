#!/bin/bash

set -e

cd "$( dirname "$0" )/.."

# See also https://github.com/pantheon-systems/pantheon-wordpress-upstream-tests

if [ -z "$ACCEPTANCE_PANTHEON_SITE" ]; then
  echo "ACCEPTANCE_PANTHEON_SITE environment variable not set"
  exit 1
elif [ -z "$ACCEPTANCE_PANTHEON_MACHINE_TOKEN" ]; then
  echo "ACCEPTANCE_PANTHEON_MACHINE_TOKEN environment variable not set"
  exit 1
elif [ -z "$ACCEPTANCE_PANTHEON_SITE_USERNAME" ]; then
  echo "ACCEPTANCE_PANTHEON_SITE_USERNAME environment variable not set"
  exit 1
elif [ -z "$ACCEPTANCE_PANTHEON_SITE_PASSWORD" ]; then
  echo "ACCEPTANCE_PANTHEON_SITE_PASSWORD environment variable not set"
  exit 1
elif [ -z "$ACCEPTANCE_PLUGIN_SLUG" ]; then
  echo "ACCEPTANCE_PLUGIN_SLUG environment variable not set"
  exit 1
fi

if [ -z "$ACCEPTANCE_PANTHEON_ENV" ]; then
  ACCEPTANCE_PANTHEON_ENV=$( echo "$TRAVIS_REPO_SLUG" | md5sum | cut -c1-11 )
fi
if [ -z "$ACCEPTANCE_LOCK_TIMEOUT" ]; then
  ACCEPTANCE_LOCK_TIMEOUT=600
fi
if [ -z "$ACCEPTANCE_PANTHEON_SITE_EMAIL" ]; then
  ACCEPTANCE_PANTHEON_SITE_EMAIL=$ACCEPTANCE_PANTHEON_ENV@testbed.example.com
fi

ACCEPTANCE_PANTHEON_SITEURL="http://$ACCEPTANCE_PANTHEON_ENV-$ACCEPTANCE_PANTHEON_SITE.pantheonsite.io"
echo "ACCEPTANCE_PANTHEON_SITE: $ACCEPTANCE_PANTHEON_SITE"
echo "ACCEPTANCE_PANTHEON_ENV: $ACCEPTANCE_PANTHEON_ENV"
echo "Site environment URL: $ACCEPTANCE_PANTHEON_SITEURL"


# @todo Grunt should be installed as part of the NPM package.
echo "Running grunt build:"
grunt build

echo "Configuring SSH:"
echo "StrictHostKeyChecking no" > ~/.ssh/config
# Next line provided by: travis encrypt-file .travis_id_rsa
openssl aes-256-cbc -K $encrypted_759fa8e3f8c8_key -iv $encrypted_759fa8e3f8c8_iv -in .travis_id_rsa.enc -out ~/.ssh/id_rsa -d
chmod 600 ~/.ssh/id_rsa
echo "IdentityFile ~/.ssh/id_rsa" >> ~/.ssh/config

if [ ! -e $HOME/terminus/terminus ]; then
  echo "Installing terminus:"
  mkdir -p $HOME/terminus
  curl -O https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar && php installer.phar install --install-dir=$HOME/terminus --bin-dir=$HOME/terminus
else
  echo "Terminus already installed"
fi
PATH="$HOME/terminus:$PATH"

if [ ! -e $HOME/.terminus/plugins/terminus-rsync-plugin ]; then
  echo "Installing terminus rsync plugin:"
  mkdir -p $HOME/.terminus/plugins
  curl https://github.com/pantheon-systems/terminus-plugin-example/archive/1.x.tar.gz -L | tar -C ~/.terminus/plugins -xvz
  composer create-project -d ~/.terminus/plugins pantheon-systems/terminus-rsync-plugin:~1
else
  echo "Terminus rsync plugin already installed"
fi

echo "Authenticating to terminus:"
terminus auth:login --machine-token="$ACCEPTANCE_PANTHEON_MACHINE_TOKEN"

# Create multidev environment.
if ! terminus multidev:list $ACCEPTANCE_PANTHEON_SITE --format=csv | tail -n+2 | grep -q "$ACCEPTANCE_PANTHEON_ENV"; then
  echo "Creating multidev environment for $ACCEPTANCE_PANTHEON_ENV"
  terminus multidev:create $ACCEPTANCE_PANTHEON_SITE.dev $ACCEPTANCE_PANTHEON_ENV
  MULTIDEV_ENV_CREATED=1
else
  echo "Multidev for $ACCEPTANCE_PANTHEON_ENV already created."
  MULTIDEV_ENV_CREATED=0
fi

echo "Checking for environment lock"
if terminus remote:wp "$ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV" -- core is-installed; then
  echo "Is installed, now checking if lock is present..."
  while [[ $( date +%s ) -lt $(( $( terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV option get testbed_lock_timestamp 2>/dev/null ) + $ACCEPTANCE_LOCK_TIMEOUT )) ]]; do
    echo "Waiting 15 seconds for testbed lock to clear (up to $ACCEPTANCE_LOCK_TIMEOUT seconds)..."
    sleep 15
  done
fi

echo "Ensure SFTP mode:"
terminus connection:set $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV sftp

echo "Wipe environment:"
if [ "$MULTIDEV_ENV_CREATED" == 1 ]; then
  terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV -- db reset --yes
else
  terminus env:wipe $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV --no-interaction --yes
fi

echo "Update from upstream:"
terminus upstream:updates:apply --updatedb --accept-upstream $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV

# TODO Consider uploading a pantheon.yml that defines the PHP version as 7.0.

echo "Install WordPress:"
terminus remote:wp --quiet $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV -- core install \
  --title="Testbed for $ACCEPTANCE_PLUGIN_SLUG" \
  --url="$ACCEPTANCE_PANTHEON_SITEURL" \
  --admin_user="$ACCEPTANCE_PANTHEON_SITE_USERNAME" \
  --admin_password="$ACCEPTANCE_PANTHEON_SITE_PASSWORD" \
  --admin_email="$ACCEPTANCE_PANTHEON_SITE_EMAIL" \
  --skip-email

terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV option set testbed_lock_timestamp $( date +%s )

echo "Upgrading to latest version of WP:"
# TODO: --version=nightly
terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV -- core update

echo "Upload plugin files:"
terminus rsync build/ $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV:code/wp-content/plugins/$ACCEPTANCE_PLUGIN_SLUG -- -avz --delete

# TODO: Actually, we can let the WP user activate the plugin instead.
echo "Activating plugin:"
terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV -- plugin activate $ACCEPTANCE_PLUGIN_SLUG

echo "Unlock environment from HTTP Auth so clients can freely connect"
terminus lock:disable $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV

phantomjs --webdriver=8643 --web-security=no &
sleep 3 # Wait for it to be ready.

exit_code=0
export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension": {"base_url": "'$ACCEPTANCE_PANTHEON_SITEURL'", "selenium2":{ "wd_host": "http://localhost:8643/wd/hub" }}} }}'
if ! ./vendor/bin/behat -c tests/behat/behat.yml --strict; then
	exit_code=1
fi

# Re-lock the environment.
echo "Re-lock environment to eliminate maintenance concerns"
terminus lock:enable $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV "$ACCEPTANCE_PANTHEON_SITE_USERNAME" "$ACCEPTANCE_PANTHEON_SITE_PASSWORD"

# Allow another build to proceed.
terminus remote:wp $ACCEPTANCE_PANTHEON_SITE.$ACCEPTANCE_PANTHEON_ENV option delete testbed_lock_timestamp

exit $exit_code
