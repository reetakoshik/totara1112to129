@javascript @totara @totara_cohort
Feature: User with totara/cohort:managerules can manage audience rules in category context
  In order to manage a category audience
  As a user with the totara/cohort:managerules permission
  I can change audience rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname    | lastname   | email                |
      | catman      | John         | Catmanager | catman1@example.com  |
    And the following "categories" exist:
      | name        | idnumber |
      | CategoryOne | cat1     |
    And the following "cohorts" exist:
      | name              | idnumber | description         | cohorttype | contextlevel | reference |
      | Cat1 Audience     | 1        | About this audience | 2          | Category     | cat1      |
    Given the following "roles" exist:
      | name                 | shortname  | description  | archetype    |
      | Audience cat manager | audmanage  | Aud cat man  | staffmanager |
      | Category manager     | catmanage  | Category man | staffmanager |
    And the following "role assigns" exist:
      | user   | role      | contextlevel | reference |
      | catman | audmanage | Category     | cat1      |
      | catman | catmanage | System       |           |
    And the following "permission overrides" exist:
      | capability                | permission | role      | contextlevel | reference |
      | totara/cohort:managerules | Allow      | audmanage | Category     | cat1      |
      | moodle/category:manage    | Allow      | catmanage | System       |           |

  @_alert
  Scenario: Try to change rules that require totara/cohort:managerules permission in category context
    Given I log in as "catman"
    And "Administration" "block" should be visible
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I follow "CategoryOne"
    And I navigate to "Audiences" node in "Category: CategoryOne"
    And I follow "Cat1 Audience"
    And I switch to "Rule sets" tab

    When I click on "Make a user a member when they meet rule sets criteria" "checkbox"
    And I click on "Remove a user's membership when they no longer meet the rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    Then I should see "Rule changes approved"

    And I select "Managers" from the "Add rule" singleselect
    And I click on "Admin User" "text" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "Admin User"
    When I click on "Delete rule item" "link" confirming the dialogue
    Then I should not see "Admin User"

