@report @report_completion @totara @totara_courseprogressbar
Feature: Completion report rpl
  If cousrse completion via RPL is set or removed, the course status needs to be adjusted accordingly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |  enablecompletion |
      | Course 1 | C1        |  1                |
    And the following "activities" exist:
      | activity   | name              | intro         | course               | idnumber    | completion   |
      | label      | label1            | label1        | C1                   | label1      | 1            |
      | label      | label2            | label2        | C1                   | label2      | 1            |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I click on "criteria_activity_value[2]" "checkbox"
    And I press "Save changes"

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted, with no learner activities completed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

    # Set course completion via RPL
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I complete the course via rpl for "Student 1" with text "Test 1"
    And I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    # Completionstatus detail have been deprecated. Will be replaced by information in the progressbar popover
    Then I should see "0%" in the "Course 1" "table_row"

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted, with one learner activity completed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log out

    # As a student, complete one activity
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I click on "Not completed: label1. Select to mark as complete." "link"
    And I log out

    # Set course completion via RPL
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I complete the course via rpl for "Student 1" with text "Test 1"
    And I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    # Completionstatus detail have been deprecated. Will be replaced by information in the progressbar popover
    Then I should see "0%" in the "Course 1" "table_row"
