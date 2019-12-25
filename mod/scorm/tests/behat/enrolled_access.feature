@mod @mod_scorm @javascript @totara
Feature: Enrolled users access to SCORM activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | learner  | Some      | Learner  | learner@example.com |
      | trainer  | Some      | Trainer  | trainer@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | learner | C1     | student        |
      | trainer | C1     | editingteacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | SCORM enrolled test                         |
      | Description | Some test of enrolled users access to SCORM |
      | ID number   | SCORM1                                      |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I press "Save and return to course"
    And I log out

  Scenario: Confirm that students may access and launch SCORM by default
    When I log in as "learner"
    And I am on "Course 1" course homepage
    And I follow "SCORM enrolled test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should see "Mode:"
    And I should see "Preview"
    And I should see "Normal"
    And I press "Enter"
    And I should not see "Preview mode"
    And I am on site homepage

    When I am on "Course 1" course homepage
    And I follow "SCORM enrolled test"
    And I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 1"
    And I should see "Start a new attempt"
    And I set the field "Preview" to "1"
    And I press "Enter"
    Then I should see "Preview mode"
    And I am on site homepage

  Scenario: Confirm that enrolled user access to SCORM may be restricted
    When I log in as "trainer"
    And the following "permission overrides" exist:
      | capability          | permission | role    | contextlevel    | reference |
      | mod/scorm:savetrack | Prevent    | student | Activity module | SCORM1    |
    And I log out
    And I log in as "learner"
    And I am on "Course 1" course homepage
    And I follow "SCORM enrolled test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I should not see "Normal"
    And I press "Enter"
    And I should see "Preview mode"
    And I am on site homepage
    And I log out

    When I log in as "trainer"
    And the following "permission overrides" exist:
      | capability       | permission | role    | contextlevel    | reference |
      | mod/scorm:launch | Prevent    | student | Activity module | SCORM1    |
    And I log out
    And I log in as "learner"
    And I am on "Course 1" course homepage
    And I follow "SCORM enrolled test"
    Then I should see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I should not see "Normal"
    And I log out

    When I log in as "trainer"
    And the following "permission overrides" exist:
      | capability     | permission | role    | contextlevel    | reference |
      | mod/scorm:view | Prevent    | student | Activity module | SCORM1    |
    And I log out
    And I log in as "learner"
    And I am on "Course 1" course homepage
    And I should not see "SCORM enrolled test"
    And I log out

