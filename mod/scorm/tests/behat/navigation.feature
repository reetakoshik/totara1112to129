@mod @mod_scorm @_file_upload @_switch_frame @javascript @totara
Feature: SCORM navigation
  Tests the multilevel navigation for the SCORM package works

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | Multi tier SCORM package |
      | Description | Multi tier SCORM package |
    And I upload "mod/scorm/tests/packages/multi_level.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Multi tier SCORM package"


  Scenario: SCORM forward and backward navigation buttons
    When I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    # Unfortunately, there are other buttons with ">" and "<" in the SCORM page;
    # hence the using of XPath to select exactly which button to press.
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_prev']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"
    # Go away from the scorm to stop background requests
    And I am on homepage


  Scenario: SCORM fast forward and backward navigation buttons
    When I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I press ">>"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I press ">>"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I press "<<"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I press "<<"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I press "<<"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I press "<<"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"
    # Go away from the scorm to stop background requests
    And I am on homepage


  Scenario: SCORM up navigation button
    When I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I press "^"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I press "^"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I press "<<"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"
    # Go away from the scorm to stop background requests
    And I am on homepage

