@totara @totara_menu @javascript
Feature: Test restricting Totara custom menu access by preset rules
  In order to limit access to menu items
  As a user
  I need to restrict by preset rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email               |
      | testuser    | user      | 1        | user@example.com    |
      | testfailure | failure   | 1        | failure@example.com |
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
    And I click on "Restrict access by preset rule" "text" in the "#fitem_id_preset_enable" "css_element"

  Scenario: Check visibility of menu for logged in user
    Given I click on "User is logged in" "text" in the "#fgroup_id_preset" "css_element"
    When I log out
    And I log in as "testuser"
    Then I should see "test item" in the totara menu

  Scenario: Check visibility of menu for logged out user
    Given I click on "User is not logged in" "text" in the "#fgroup_id_preset" "css_element"
    And I click on "Save changes" "button"
    And I set the following administration settings values:
      | forcelogin | 0 |
    When I log out
    Then I should see "test item" in the totara menu
    When I log in as "testuser"
    Then I should not see "test item" in the totara menu
