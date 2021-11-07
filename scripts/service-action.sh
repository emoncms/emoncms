#!/bin/bash
date=$(date +"%Y-%m-%d")
date
echo "$1 $2 by user $USER...."

sudo /bin/systemctl $2 $1 > /dev/null &
