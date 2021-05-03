#!/bin/sh
# install.sh: Will copy files and install App to an appropriate folder.

path_src=$1
path_des=$2

mv -f ${path_src:-$0} ${path_des:-.}
