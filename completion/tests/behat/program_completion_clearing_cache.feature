@totara @core @core_completion @javascript
Feature: Program completion cache should be cleared after deletion
  Background:
    Given the following "courses" exist:
      | fullname  | shortname | category |
      | course101 | c101      | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | student1 | Sa Rang   | Kim      | kimsarang@example.com |
    And the following "programs" exist in "totara_program" plugin:
      | fullname        | shortname  |
      | prog_completion | comptest   |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user     |
      | comptest | student1 |
    And I add a courseset with courses "c101" to "comptest":
      | Set name              | set2        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I am on a totara site
    And I log in as "admin"

  Scenario: User untick the completion mark and it should clear the cache when the action has been completed
    Given I navigate to "Users > Browse list of users" in site administration
    And I follow "Sa Rang Kim"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I follow "prog_completion"
    And I follow "Not completed: course101. Select to mark as complete."
    And I click on "Save changes" "button"
    And I follow "prog_completion"
    When I follow "Completed: course101. Select to mark as not complete."
    And I follow "prog_completion"
    Then "Not completed: course101. Select to mark as complete." "link" in the "course101" "table_row" should be visible
