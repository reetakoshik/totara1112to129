@totara @totara_reportbuilder @javascript
Feature: Check that course completion reports don't show multiple enrolment types per course when only one has been selected.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | completionstartonenrol |
      | Course 1 | C1        | 1                | 1                      |
      | Course 2 | C2        | 1                | 1                      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | A1     |
    And I log in as "admin"

  Scenario: User is enrolled in a second course using a different method
    Given I follow "Course 2"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to "Create report" node in "Site administration > Reports > Report builder"
    And I set the following fields to these values:
      | Report Name | Course Completion Report |
      | Source      | Course Completion        |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Manual enrolments"
    And I should see "Audience sync"
    And I should not see "Audience sync, Manual enrolments"

  Scenario: User is enrolled in the same course using a different method
    Given I follow "Course 1"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to "Create report" node in "Site administration > Reports > Report builder"
    And I set the following fields to these values:
      | Report Name | Course Completion Report |
      | Source      | Course Completion        |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Audience sync" in the "Course 1" "table_row"
    And I should see "Manual enrolments" in the "Course 1" "table_row"
