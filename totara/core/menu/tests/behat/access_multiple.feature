@totara @totara_menu @javascript
Feature: Test restricting Totara custom menu access with rule aggregation
  In order to limit access to menu items
  As a user
  I need to restrict by multiple types

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
      | user2    | user      | 2        | user2@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I click on "Add new menu item" "button"
    And I set the following fields to these values:
      | Parent item              | Top                     |
      | Menu title               | test item               |
      | Visibility               | Use custom access rules |
      | Menu default url address | /my/                    |
    And I click on "Add new menu item" "button"
    And I click on "Access" "link"
    And I expand all fieldsets
    And I click on "Restrict access by role" "text" in the "#fitem_id_role_enable" "css_element"
    And I click on "Manager" "text" in the "#fgroup_id_roles" "css_element"
    And I click on "Restrict access by audience" "text" in the "#fitem_id_audience_enable" "css_element"
    And I click on "Add audiences" "button"
    And I click on "aud1" "text" in the "#course-cohorts-visible-dialog .treeview" "css_element"
    And I click on "OK" "button"
    # We have to wait for the dialogue JS to finish.
    And I wait "1" seconds

  Scenario: Test menu access with multiple rules and using any for aggregation
    Given I click on "any" "text" in the "#fgroup_id_item_visibility" "css_element"
    And I click on "Save changes" "button"
    And the following "system role assigns" exist:
      | user  | role    |
      | user1 | manager |
    And I log out
    When I log in as "user1"
    Then I should see "test item" in the totara menu
    When I log out
    And I log in as "admin"
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
    And I log out
    And I log in as "user1"
    Then I should see "test item" in the totara menu

  Scenario: Test menu access with multiple rules and using all for aggregation
    Given I click on "Users matching all of the criteria below can view this menu item." "text" in the "#fgroup_id_item_visibility" "css_element"
    And I click on "Save changes" "button"
    And the following "system role assigns" exist:
      | user  | role    |
      | user1 | manager |
    And I log out
    When I log in as "user1"
    Then I should not see "test item" in the totara menu
    When I log out
    And I log in as "admin"
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
    And I log out
    And I log in as "user1"
    Then I should see "test item" in the totara menu
