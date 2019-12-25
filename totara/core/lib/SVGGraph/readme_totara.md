Description of SVGGraph import
==============================

1. Download latest version from http://www.goat1000.com/svggraph.php

2. Add commit with new version to Totara repository at https://github.com/totara/SVGGraph

3. Merge changes into the release branch

4. Copy a snapshot of release branch to this directory

5. Update version in totara/core/thirdpartylibs.xml

6. reapply and test RTL hack in SVGGraphLegend::Draw() from TL-6573

7. TL-16004 - Suppressed php 7.2 deprecation message on all uses of each()

8. Check all PHPUnit tests for PHPUnit 7.5.x compatibility:
    - Convert all non-namespaced PHPUnit classes to use namespaces, i.e. PHPUnit_Framework_TestCase to \PHPUnit\Framework\TestCase
    - Check for and convert deprecated methods ($this->setExpectedExceptions(), $this->getMock(), etc.) in test cases
    - More information about backwards compatibility issues:
        - https://phpunit.de/announcements/phpunit-6.html
        - https://phpunit.de/announcements/phpunit-7.html

Petr Skoda
