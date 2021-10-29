#!/bin/sh
# start.sh: Will start App daemon.

path=$1

mkdir -p ~/.config/rclone/
ln -s /mnt/HD/HD_a2/.systemfile/mySync/etc/rclone.conf ~/.config/rclone/rclone.conf
cd ${path}/bin; ./my_sync_guardian.sh > /dev/null &
