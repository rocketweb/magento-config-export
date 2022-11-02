# RocketWeb Config (Export)

This module is a simple tool to export specific sections of configuration into config.xml file to make it VCS 
transferable without locking them by using app/etc/config.php or app/etc/env.php

## Usage
Running the command will generate a file config.xml inside of var/config/ folder. If file doesn't exist, it will
create it, otherwise it will append/modify values to the XML structure.



> bin/magento config:data:export _scopes_ _paths_
