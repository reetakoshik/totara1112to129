@mod @mod_survey @javascript
Feature: Survey validation works as expected
  In order to submit a survey
  As a teacher
  I need to set survey activities and enable activity completion

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1 | 0 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Survey validates
    Given I add a "Survey" to section "1" and I fill the form with:
      | Name | Test survey name |
      | Survey type | COLLES (Actual) |
      | Description | Test survey description |
      | Completion tracking | Show activity as complete when conditions are met |
      | id_completionview | 1 |
    And I turn editing mode off
    When I follow "Test survey name"
    And I press "Click here to continue"
    Then I should see "Some of the multiple choice questions have not been answered"
    When I press "Cancel"
    And I set the following fields to these values:
      | q1_1 | 1 |
      | q2_1 | 1 |
      | q3_1 | 3 |
      | q4_1 | 2 |
      | q5_1 | 1 |
      | q6_1 | 3 |
      | q7_1 | 2 |
      | q8_1 | 1 |
      | q9_1 | 1 |
      | q10_1 | 1 |
      | q11_1 | 1 |
      | q12_1 | 2 |
      | q13_1 | 1 |
      | q14_1 | 1 |
      | q15_1 | 1 |
    And I press "Click here to continue"
    Then I should see "Some of the multiple choice questions have not been answered"
    When I press "Cancel"
    And I set the following fields to these values:
      | q16_1 | 1 |
      | q17_1 | 1 |
      | q18_1 | 1 |
      | q19_1 | 3 |
      | q20_1 | 1 |
      | q21_1 | 2 |
      | q22_1 | 1 |
      | q23_1 | 1 |
      | q24_1 | 1 |
    And I press "Click here to continue"
    Then I should not see "Some of the multiple choice questions have not been answered"
    And I should see "Thanks for answering this survey"