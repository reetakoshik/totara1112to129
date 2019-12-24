Description of ADODB V5.20.12 library import into Totara

This library is now used only in enrol and auth db plugins.
The core DML drivers are not using ADODB any more.

Removed:
 * contrib/
 * cute_icons_for_site/
 * docs/
 * pear/
 * replicate/
 * scripts/
 * session/
 * tests/
 * composer.json
 * server.php
 * lang/* except en (because they were not in utf8)

Added:
 * index.html - prevent directory browsing on misconfigured servers
 * readme_moodle.txt - this file ;-)

Our changes:
 * MDL-41198 Removed random seed initialization from lib/adodb/adodb.inc.php:216 (see 038f546 and MDL-41198).
 * MDL-52286 Added muting errors in ADORecordSet::__destruct().
   Check if fixed upstream during the next upgrade and remove this note.
 * TL-14768 Added fix in ADODB_mssqlnative::_connect() to ensure that host and port are separated by ,

skodak, iarenaza, moodler, stronk7, abgreeve, lameze, rianar
