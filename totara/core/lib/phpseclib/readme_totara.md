Description of phpseclib 2.0.6 library import into Totara

Added:
 * index.html - prevent directory browsing on misconfigured servers
 * readme_totara.md - this file ;-)

Our changes:
 * All DOS new lines were converted to Unix (\r\n to \n)
 * Fixed file attributes
 * Removed bootstrap.php
 * Convert continue within switch statement to break for PHP 7.3 compatibility
   * totara/core/lib/phpseclib/File/X509.php