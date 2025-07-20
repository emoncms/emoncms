#!/usr/bin/env sh
dev_branch=master
cd "$WWW/emoncms"
git pull
git checkout $dev_branch
cd "$OEM_DIR"
make module name=graph
make module name=dashboard
make module name=app 
make module name=device
make symodule name=sync
make symodule name=postprocess
make symodule name=backup
cd "$WWW/emoncms/Modules/graph"
git remote set-url origin https://github.com/emoncms/graph
git pull
git checkout $dev_branch
cd "$WWW/emoncms/Modules/dashboard"
git remote set-url origin https://github.com/emoncms/dashboard
git pull
git checkout $dev_branch
cd "$WWW/emoncms/Modules/device"
git remote set-url origin https://github.com/emoncms/device
git pull
git checkout $dev_branch
cd "$EMONCMS_DIR/modules/sync"
git remote set-url origin https://github.com/emoncms/sync
git pull
git checkout $dev_branch
cd "$EMONCMS_DIR/modules/postprocess"
git remote set-url origin https://github.com/emoncms/postprocess
git pull
git checkout $dev_branch
cd "$EMONCMS_DIR/modules/backup"
git remote set-url origin https://github.com/emoncms/backup
git pull
git checkout $dev_branch