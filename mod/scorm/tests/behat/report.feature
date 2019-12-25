@mod @mod_scorm @_file_upload @_switch_iframe @javascript
Feature: Test scorm reports

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Erste     | Teacher  | teacher1@example.com |
      | teacher2 | Zweite    | Teacher  | teacher2@example.com |
      | student1 | Prvni     | Student  | student1@example.com |
      | student2 | Druhy     | Student  | student2@example.com |
      | student3 | Treti     | Student  | student3@example.com |
      | student4 | Ctvrty    | Student  | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Awesome SCORM package |
      | Description | Description |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Awesome SCORM package"
    And I log out

  Scenario: Basic usage of all scorm reports
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Awesome SCORM package"
    And I press "Enter"
    And I wait "2" seconds
    And I switch to "scorm_object" iframe
    And I set the following fields to these values:
      | key0b0 | 0 |
      | key1b0 | 0 |
      | key2b0 | 0 |
    And I click on "submitB" "button"
    # Must wait here to let it save results, otherwise alert may popup.
    And I wait "2" seconds
    And I switch to the main frame
    And I follow "Exit activity"
    And I follow "Awesome SCORM package"
    And I set the following fields to these values:
      | newattempt | 1 |
    And I press "Enter"
    And I wait "2" seconds
    And I switch to "scorm_object" iframe
    And I set the following fields to these values:
      | key0b0 | 0 |
      | key1b0 | 0 |
      | key2b0 | 0 |
      | key3b0 | 0 |
      | key4b0 | 0 |
    And I click on "submitB" "button"
    # Must wait here to let it save results, otherwise alert may popup.
    And I wait "2" seconds
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Awesome SCORM package"
    And I press "Enter"
    And I wait "2" seconds
    And I switch to "scorm_object" iframe
    And I set the following fields to these values:
      | key0b0 | 0 |
      | key1b0 | 0 |
    And I click on "submitB" "button"
    # Must wait here to let it save results, otherwise alert may popup.
    And I wait "2" seconds
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Awesome SCORM package"

    When I follow "Reports"
    Then I should see "Erste Teacher"
    And I should see "Prvni Student"
    And I should see "Druhy Student"
    And I should see "Treti Student"
    And I should not see "Zweite Teacher"
    And I should not see "Ctvrty Student"
    And I should see "60" in the "student1@example.com" "table_row"
    And I should see "40" in the "student2@example.com" "table_row"

    When I follow "Graph report"
    Then I should see "1" in the "40 - 50" "table_row"
    And I should see "1" in the "90 - 100" "table_row"

    When I follow "Interactions report"
    Then I should see "Erste Teacher"
    And I should see "Prvni Student"
    And I should see "Druhy Student"
    And I should see "Treti Student"
    And I should not see "Zweite Teacher"
    And I should not see "Ctvrty Student"
    And I should see "60" in the "student1@example.com" "table_row"
    And I should see "40" in the "student2@example.com" "table_row"
    And I should see "Not attempted" in the "student3@example.com" "table_row"
    And I should see "Not attempted" in the "teacher1@example.com" "table_row"

    When I follow "Objectives report"
    Then I should see "Erste Teacher"
    And I should see "Prvni Student"
    And I should see "Druhy Student"
    And I should see "Treti Student"
    And I should not see "Zweite Teacher"
    And I should not see "Ctvrty Student"
    And I should see "60" in the "student1@example.com" "table_row"
    And I should see "40" in the "student2@example.com" "table_row"
    And I should see "Not attempted" in the "student3@example.com" "table_row"
    And I should see "Not attempted" in the "teacher1@example.com" "table_row"

    And I log out
