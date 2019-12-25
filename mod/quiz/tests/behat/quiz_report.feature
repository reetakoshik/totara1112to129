@mod @mod_quiz @core_grades
Feature: Quiz report for all user and groups
  In order to ensure a trainer can review reports
  As a trainer
  I need to set a quiz, and review reports after they attempted it

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |
    And the following "activities" exist:
      | activity   | name           | course | idnumber | attempts | completion | completionattemptsexhausted | groupmode |
      | quiz       | Test quiz name | C1     | quiz1    | 2        | 2          | 1                           | 1         |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |


  Scenario: All users option disappear from report when grouping is selected
    Given the following "groups" exist:
      | name | course | idnumber |
      | Group A | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    # No groups selected: "All users..." option exists
    When I navigate to "Grades" node in "Quiz administration > Results"
    Then I should see "all users who have attempted the quiz"
    # Select group and confirmt "All users..." option does not exist
    When I select "Group A" from the "Separate groups" singleselect
    Then I should not see "all users who have attempted the quiz"
