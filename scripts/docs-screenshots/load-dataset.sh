#!/bin/bash
# Load the testdataset module into one Emoncms account for docs screenshots.
# Usage: sudo ./load-dataset.sh <userid>
# Re-run to bring the data up to today.

set -e

if [ "$EUID" -ne 0 ]; then
    echo "Run as root: sudo $0 <userid>"
    exit 1
fi

userid="$1"
if ! [[ "$userid" =~ ^[0-9]+$ ]]; then
    echo "Usage: sudo $0 <userid>"
    exit 1
fi

dataset="${DATASET:-/opt/emoncms/modules/testdataset}"
phpfina_dir="${PHPFINA_DIR:-/var/opt/emoncms/phpfina}"
www_user="${WWW_USER:-www-data}"

if [ ! -d "$dataset/phpfina" ]; then
    echo "Dataset not found: $dataset/phpfina"
    echo "Clone https://github.com/emoncms/testdataset and unzip phpfina.zip"
    exit 1
fi

# Working directory with its own settings.php, so the dataset's settings are left alone
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT
ln -s "$dataset/phpfina" "$work/phpfina"
mkdir "$work/scripts"
for f in common.php add_feeds_to_account.php post_process.php; do
    ln -s "$dataset/scripts/$f" "$work/scripts/$f"
done
cat > "$work/scripts/settings.php" <<EOF
<?php
\$userid = $userid;
\$emoncms_data_dir = "$(dirname "$phpfina_dir")/";
\$phpfina_dir = "$phpfina_dir/";
EOF
touch "$work/start"

cd "$work"
php scripts/add_feeds_to_account.php
php scripts/post_process.php

# Feed files written by this run belong to the web server, so apps and feedwriter can update them
find "$phpfina_dir" -user root -newer "$work/start" -exec chown "$www_user" {} +

echo "Dataset loaded for user $userid"
