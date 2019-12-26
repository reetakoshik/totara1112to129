Description of dompdf import
==============================

1. Download latest version from https://github.com/dompdf/dompdf

2. Reapply Totara hacks in following areas:
    - totara/appraisal/dompdf/src/Helpers.php - getFileContent() - add download restrictions
    - totara/appraisal/dompdf/lib/res/html.css - remove field set CSS
    - totara/appraisal/dompdf/lib/php-css-parser/ - remove directory, it is already included in the main /lib
    - totara/appraisal/dompdf/autoload.inc.php - remove php-css-parser autoloading
    - Check all PHPUnit tests for PHPUnit 7.5.x compatibility:
        - Convert all non-namespaced PHPUnit classes to use namespaces, i.e. PHPUnit_Framework_TestCase to \PHPUnit\Framework\TestCase
        - Check for and convert deprecated methods ($this->setExpectedExceptions(), $this->getMock(), etc.) in test cases
        - More information about backwards compatibility issues:
            - https://phpunit.de/announcements/phpunit-6.html
            - https://phpunit.de/announcements/phpunit-7.html

3. Bump up version in totara/appraisal/thirdpartylibs.xml

Petr Skoda
