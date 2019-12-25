@report @report_completion @totara @totara_courseprogressbar
Feature: Restore course completion report rpl
  If course completion RPL is deleted via recycle bin and restored, the course completion RPL must restored as before

  Background:
    Given I am on a totara site
    And the following config values are set as admin:
      | coursebinenable   | 1 | tool_recyclebin |
      | categorybinenable | 1 | tool_recyclebin |
      | autohide          | 0 | tool_recyclebin |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname |  enablecompletion |
      | Course 1 | C1        |  1                |
    And the following "activities" exist:
      | activity | name   | intro  | course | idnumber | completion |
      | label    | label1 | label1 | C1     | label1   | 1          |
      | label    | label2 | label2 | C1     | label2   | 1          |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I click on "criteria_activity_value[2]" "checkbox"
    And I press "Save changes"
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | student3 | C1     | student |
    And I log out

  @javascript
  Scenario: Restore the course with course completions and all completion results via Recycle bin
    # As a student, complete one activity
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I click on "Not completed: label1. Select to mark as complete." "link"
    And I click on "Not completed: label2. Select to mark as complete." "link"
    And I log out

    # Set course completion via RPL
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Student 2" with text "Completed via RPL 1"

    And I go to the courses management page
    And I click on "delete" action for "Course 1" in management course listing
    And I press "Delete"
    And I press "Continue"
    And I click on "Recycle bin" "link"
    And I click on "Restore" "link" in the "Course 1" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I should see "Completed" in the "Student 1" "table_row"
    And I click on "Show RPL" "link" in the "Student 2" "table_row"
    And I should see "Completed via RPL" in the "Student 2" "table_row"
    And I should see "Not completed" in the "Student 3" "table_row"
