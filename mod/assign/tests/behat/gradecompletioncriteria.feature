@mod @mod_assign
Feature: Set the assignment activity grade completion criteria
  In order to set the grade completion criteria
  As an admin
  I need to enable a grade type or the default feedback type

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name      | Test assignment name        |
      | Description          | Test assignment description |
      | Online text          | 1                           |
      | Use marking workflow | Yes                         |
    And I follow "Test assignment name"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets

  Scenario: Assignment grade completion criteria cannot be set when grade type and default feedback type are both disabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | None                                              |
      | assignfeedback_comments_enabled | 0                                                 |
      | assignfeedback_offline_enabled  | 1                                                 |
      | assignfeedback_file_enabled     | 1                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"
    And I should not see "Add an activity or resource"

  Scenario: Assignment grade completion criteria can be set when a default feedback type is enabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | None                                              |
      | assignfeedback_comments_enabled | 1                                                 |
      | assignfeedback_offline_enabled  | 0                                                 |
      | assignfeedback_file_enabled     | 0                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "Add an activity or resource"
    And I should not see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"

  Scenario: Assignment grade completion criteria can be set when a grade type is enabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | Point                                              |
      | assignfeedback_comments_enabled | 0                                                 |
      | assignfeedback_offline_enabled  | 0                                                 |
      | assignfeedback_file_enabled     | 0                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "Add an activity or resource"
    And I should not see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"
