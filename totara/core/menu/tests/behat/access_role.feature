@totara @totara_menu @javascript
Feature: Test restricting Totara custom menu access by roles
  In order to limit access to menu items
  As a user
  I need to restrict by role

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
    And I expand all fieldsets

  Scenario Outline: Test visibility of menu when user has role and when user does not
    Given I click on "Restrict access by role" "text" in the "#fitem_id_role_enable" "css_element"
    And I click on "<role>" "text" in the "#fgroup_id_roles" "css_element"
    And I set the following fields to these values:
      | Context | Users must have role in the system context |
    And I click on "Save changes" "button"
    And I log out
    And the following "system role assigns" exist:
      | user     | role            |
      | testuser | <roleshortname> |
    When I log in as "testuser"
    Then I should see "test item" in the totara menu
    When I log out
    And I log in as "testfailure"
    Then I should not see "test item" in the totara menu

  Examples:
    | role          | roleshortname |
    | Site Manager  | manager       |
    | Learner       | student       |
    | Guest         | guest         |

  Scenario: Check visibility of menu item for authenticated user
    Given I click on "Restrict access by role" "text" in the "#fitem_id_role_enable" "css_element"
    And I click on "Authenticated user" "text" in the "#fgroup_id_roles" "css_element"
    And I set the following fields to these values:
      | Context | Users must have role in the system context |
    And I click on "Save changes" "button"
    And I log out
    When I log in as "testuser"
    Then I should see "test item" in the totara menu
