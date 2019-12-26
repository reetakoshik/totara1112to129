@totara @totara_completioneditor @javascript
Feature: The course completion checker is functioning
  In order to see course completion records with problems
  I need to use the course completion checker

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
      | Course Two | course2   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |
      | user001 | course2 | student |

  Scenario: Completion checker link in course management page works
    When I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Check course completions" "link"
    Then I should see "Completion records with problems"
    And I should see "Total records: 2"
    And I should see "Problem records: 0"

  Scenario: Completion checker link in course completion editor works
    When I am on "Course One" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Check course completions" "link"
    Then I should see "Completion records with problems"
    And I should see "Filter by course: Course One"
    And I should see "Total records: 1"
    And I should see "Problem records: 0"
