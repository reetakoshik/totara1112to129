@totara @totara_reportbuilder @javascript
Feature: View course completion name link
  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | enablecompletion |
      | Course 0  | C0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                     |
      | learner1 | Bob1      | Learner1 | bob1.learner1@example.com |
      | learner2 | Bob2      | Learner2 | bob2&learner2@example.com |
      | learner3 | Bob3      | Learner3 | bob3.learner3@example.com |
      | learner4 | Bob4      | Learner4 | bob4.learner4@example.com |
      | teacher5 | Bob5      | Learner5 | bob5.learner5@example.com |
    And the following "permission overrides" exist:
      | capability                                   | permission | role            | contextlevel | reference |
      | totara/completioneditor:editcoursecompletion | Allow      | editingteacher  | Course       | C0        |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | learner1  | C0     | student        |
      | learner2  | C0     | student        |
      | learner3  | C0     | student        |
      | learner4  | C0     | student        |
      | teacher5  | C0     | editingteacher |

  Scenario: Ability to view user's site profile when the user is un-enrolled
    Given I log in as "admin"
    And I am on "Course 0" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "id_criteria_self_value" to "1"
    And I press "Save changes"
    When I navigate to "Completion editor" node in "Course administration"
    And I click on "Bob2 Learner2" "link"
    Then I should see "Course 0"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Bob2 Learner2" "table_row"
    And I press "Continue"
    And I navigate to "Completion editor" node in "Course administration"
    And "Bob2 Learner2" "link" should exist in the "Bob2 Learner2" "table_row"
    When I click on "Bob3 Learner3" "link"
    Then I should see "Course 0"
    And I navigate to "Completion editor" node in "Course administration"
    When I click on "Bob2 Learner2" "link"
    Then I should not see "Course 0"
    And I log out
    And I log in as "teacher5"
    And I am on "Course 0" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And "Bob2 Leaner2" "link" should not exist in the "Bob2 Learner2" "table_row"