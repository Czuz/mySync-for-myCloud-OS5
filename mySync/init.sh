#!/bin/sh
# init.sh: Will create necessary symbolic links of installed App before being
# executed. (We suggest creating the symbolic link to /usr/bin or /usr/sbin.)
# If necessary, restore those configuration files that being backed up through
# preinst.sh back to App installed folder.

path=$1
volume=$(echo ${path:-/tmp} | cut -c 1-13)

# Create symbolic link for rclone
ln -s ${path}/bin/rclone /usr/bin/rclone

# Create symbolic link for web content
ln -s ${path}/web /var/www/apps/mySync

# Create location for persistent logs and configuration
mkdir -p ${volume:-/tmp}/.systemfile/mySync/log
mkdir -p ${volume:-/tmp}/.systemfile/mySync/etc

# Clean symlinks (if previous version has been used)
if [ -L ${volume:-/tmp}/.systemfile/mySync/etc/rclone.conf ]; then
    rm ${volume:-/tmp}/.systemfile/mySync/etc/rclone.conf
fi
if [ -L ${volume:-/tmp}/.systemfile/mySync/etc/rclone_job_def.conf ]; then
    rm ${volume:-/tmp}/.systemfile/mySync/etc/rclone_job_def.conf
fi

# Parametrization is managed through Web GUI
touch ${volume:-/tmp}/.systemfile/mySync/etc/rclone.conf
touch ${volume:-/tmp}/.systemfile/mySync/etc/rclone_job_def.conf
