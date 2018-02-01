#!/bin/sh
set -e

# This script rotates 3 keys:
#
#  1. The current key, which we use to sign tokens right now.
#  2. The newest key, which we are introducing to clients before we use it.
#  3. The oldest key, which is being phased out.
#
# These are all named `keyN.pem` in the current directory. Apply them in
# `settings.php` in this order, then configure this script to run daily.
#
# The script expects to be run in the correct directory, and to have write
# access to be able to move keys and write new ones.
#
# You should not rotate keys more than once a day. This has to do with cache
# headers we send out, which are currently set to allow caching for one day.
#
# To get started, you can run this script thrice so that the application already
# has some keys available, and you don't have to check existence in your
# `settings.php`.

# The old 'current' key becomes the 'oldest' key.
if [ -f key1.pem ]; then
  mv key1.pem key3.pem
fi

# The old 'newest' key becomes the 'current' key.
if [ -f key2.pem ]; then
  mv key2.pem key1.pem
fi

# Generate a new 'newest' key.
if ! openssl genrsa -out key2.pem 4096 2> /dev/null; then
  echo "Failed to generate new RSA key"
  exit 1
fi
