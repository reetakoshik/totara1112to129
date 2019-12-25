@mod @mod_facetoface @totara
Feature: Give a grade to a student for a seminar
  In order to check that they are completed

  Background:
    Given I am on a totara site
    And the following "users" exist:
        | username | firstname | lastname | email               |
        | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
        | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
        | fullname | shortname | category | enablecompletion |
        | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
        | Name                | Test seminar name        |
        | Description         | Test seminar description |
        | Completion tracking | Show activity as complete when conditions are met |
        | Require grade       | 1 |
    And I click on "Course completion" "link" in the "Administration" "block"
    And I click on "Condition: Activity completion" "link"
    And I click on "Seminar - Test seminar name" "checkbox"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Set grade for student to complete seminar
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I set the field "Sam1 Student1 Test seminar name grade" to "100"

    And I press "Save changes"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I should see "Sam1 Student1"
    And "//tr[@id='user-4']/td[2]/span[contains(.,'Completed')]" "xpath_element" should exist
    And "//tr[@id='user-4']/td[3]/span[contains(.,'Completed')]" "xpath_element" should exist
