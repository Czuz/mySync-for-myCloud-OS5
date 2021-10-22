#!/bin/sh
# remove.sh: Will remove the installed App from hard drive.

path=$1

rm -f /usr/bin/rclone 2> /dev/null
rm -f /var/www/apps/mySync 2> /dev/null
if echo ${path:-/tmp/nonexistentpath} | grep -q "mySync"; then
    rm -rf ${path:-/tmp/nonexistentpath}
fi
