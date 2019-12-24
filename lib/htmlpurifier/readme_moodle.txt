Description of HTML Purifier library import into Totara

* delete /lib/htmlpurifier/HTMLPurifier subdirectory
* copy library/HTMLPurifier/ folder into /lib/htmlpurifier/
* copy latest library/HTMLPurifier.php, library/HTMLPurifier.safe-includes.php, CREDITS, LICENSE to this directory
* fix permissions via: php totara/core/dev/fix_file_permissions.php --fix
* verify all git changes make sense
* add new version to lib/thirdpartylibs.xml
