@mod @mod_quiz @totara
Feature: Edit quiz settings - detect missing required gradepass
  In order to prevent problem with completion
  As a teacher
  If editing quiz settings and "Require passing grade" is checked there needs to be a "Grade to pass" value

  Background:
    Given I am on a totara site
    And the following config values are set as admin:
      | enablecompletion    | 1           |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on

  Scenario: Verify no error when adding and editing a quiz without completion.
    Given I navigate to "Edit settings" node in "Course administration"
    When I expand all fieldsets
    And I set the field "Enable completion tracking" to "No"
    And I press "Save and display"
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz name        |
      | Description | Test quiz description |
    And I follow "Test quiz name"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I press "Save and return to course"
    Then I should not see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"

  Scenario: Verify no error when adding and editing a quiz with completion, no Required passing grade.
    Given I add a "Quiz" to section "1" and I fill the form with:
      | Name                | Test quiz name                                    |
      | Description         | Test quiz description                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade       | 1                                                 |
    When I follow "Test quiz name"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I press "Save and display"
    Then I should not see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"

  Scenario: Verify no error when adding and editing a quiz with completion, with Required passing grade.
    Given I add a "Quiz" to section "1" and I fill the form with:
      | Name                  | Test quiz name                                    |
      | Description           | Test quiz description                             |
      | Completion tracking   | Show activity as complete when conditions are met |
      | Grade to pass         | 1                                                 |
      | Require passing grade | 1                                                 |
    When I follow "Test quiz name"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I press "Save and display"
    Then I should not see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"

  Scenario: Verify error pops up when adding quiz with Require passing grade, no Grade to pass
    Given I add a "Quiz" to section "1" and I fill the form with:
      | Name                  | Test quiz name                                    |
      | Description           | Test quiz description                             |
      | Completion tracking   | Show activity as complete when conditions are met |
      | Require passing grade | 1                                                 |
    Then I should see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"

  Scenario: Verify error pops up when editing quiz with Require passing grade, no Grade to pass
    Given I add a "Quiz" to section "1" and I fill the form with:
      | Name                  | Test quiz name                                    |
      | Description           | Test quiz description                             |
      | Completion tracking   | Show activity as complete when conditions are met |
      | Grade to pass         | 1                                                 |
      | Require passing grade | 1                                                 |
    When I follow "Test quiz name"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I set the field "Grade to pass" to "0"
    And I press "Save and display"
    Then I should see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"

