#!/bin/bash

set -e

echo; echo "updating composer dependencies"
/usr/local/bin/composer.phar install --prefer-dist --no-progress

echo; echo "updating bower dependencies"
$(cd public && bower -q install)

echo; echo "compiling assets"
npm install
node ./node_modules/gulp/bin/gulp.js
