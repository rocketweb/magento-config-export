## RocketWeb Config Export

This module is a simple tool to export specific sections of configuration into config.xml file to make it VCS 
transferable without locking them by using app/etc/config.php or app/etc/env.php

## Usage
Running the command will generate a file `config.xml` inside of `var/config/` folder. If file doesn't exist, it will
create it, otherwise it will append/modify values to the XML structure.

Command:
`bin/magento config:data:export scopes paths`

Example:
`bin/magento config:data:export default,stores trans_email/*/email`

Arguments:
- `scopes`: Scopes for which you want to export values for. CSV values are allowed. Options: all|default|websites|stores
- `paths`: Path(s) that you want to export. Wildcard support as asterisk for second and third section of the path (*) - partial wildcard is not supported (eg. trans_email/*_general/email)
