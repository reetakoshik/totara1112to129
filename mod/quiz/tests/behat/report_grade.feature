@mod @mod_quiz @totara @totara_reportbuilder
Feature: Check that the Grade display in the Set a quiz to be marked complete when the student passes
  In order to ensure the correct grade is displayed in the report
  As a student
  I need to answer one of the 2 questions correctly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | enablecompletion    | 1           |
      | grade_item_advanced | hiddenuntil |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name            | questiontext               |
      | Test questions   | truefalse | First question  | Answer the first question  |
      | Test questions   | truefalse | Second question | Answer the second question |
    And the following "activities" exist:
      | activity   | name           | course | idnumber | attempts | gradepass | completion | completionpass |
      | quiz       | Test quiz name | C1     | quiz1    | 1        | 2.00      | 2          | 1              |
    And quiz "Test quiz name" contains the following questions:
      | question        | page |
      | First question  | 1    |
      | Second question | 2    |

  @javascript
  Scenario: Check grade in Record of Learning: Courses report source
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "Record of Learning: Courses (View)" "table_row"
    And I click on "Columns" "link" in the ".tabtree" "css_element"
    And I add the "Grade" column to the report
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Edit quiz" node in "Quiz administration"

    And I set the field "maxgrade" to "2.0"
    And I press "savechanges"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Next"
    And I set the field "False" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    And I click on "Record of Learning" in the totara menu
    Then I should see "50.0%" in the "Course 1" "table_row"
