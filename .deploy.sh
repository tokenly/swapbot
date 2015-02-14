#!/bin/bash

set -e

echo; echo "updating dependencies";
/usr/local/bin/composer.phar install --prefer-dist --no-progress
# ./artisan migrate

echo; echo "updating bower dependencies";
$(cd public && bower -q install)
