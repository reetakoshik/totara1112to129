@core @totara @totara_core @javascript @totara_core_adminmenu
Feature: Totara admin main menu
  In order to quickly access admin functionality
  As a user
  I need to be able to use the admin main menu

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                  |
      | learner1 | learner   | one      | learnerone@example.com |
      | manager1 | manager   | one      | managerone@example.com |
    And the following "roles" exist:
      | name         | shortname |
      | User manager | userman   |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | userman | System       |           |
    And the following "permission overrides" exist:
      | capability                 | permission | role    | contextlevel | reference |
      | totara/plan:configureplans | Allow      | userman | System       |           |

  Scenario: Check admin main menu is shown for admin but not for learner
    Given I log in as "admin"
    And "[aria-label='Show admin menu window']" "css_element" should exist
    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Core platform" in the "#quickaccess-popover-content" "css_element"
    And I should see "Learning" in the "#quickaccess-popover-content" "css_element"
    And I should see "Performance" in the "#quickaccess-popover-content" "css_element"
    And I should see "Configuration" in the "#quickaccess-popover-content" "css_element"
    When I click on "[aria-label='Hide admin menu window']" "css_element"

    Then I should not see "Core platform" in the "#quickaccess-popover-content" "css_element"
    And I should not see "Learning" in the "#quickaccess-popover-content" "css_element"
    And I should not see "Performance" in the "#quickaccess-popover-content" "css_element"
    And I should not see "Configuration" in the "#quickaccess-popover-content" "css_element"
    And I log out

    When I log in as "manager1"
    And "[aria-label='Show admin menu window']" "css_element" should exist
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Learning" in the "#quickaccess-popover-content" "css_element"
    And I should see "Learning Plans" in the "#quickaccess-popover-content" "css_element"
    And I log out

    When I log in as "learner1"
    Then "[aria-label='Show admin menu window']" "css_element" should not exist

  Scenario: Check I can navigate to pages using the main admin menu
    Given I log in as "admin"
    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Browse list of users"
    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Courses and categories" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Course and category management"

  Scenario: Check I can use the admin main menu search
    Given I log in as "manager1"
    When I click on "[aria-label='Show admin menu window']" "css_element"
    # Manager does not have the capability to use the admin search.
    Then ".totara_core__QuickAccess_menu_search-input" "css_element" should not exist
    And I log out

    When I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I set the field "totara_core__QuickAccess_search" to "audience-based visibility"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on ".totara_core__QuickAccess_menu_search-button" "css_element"
    Then I should see "Site administration"
    And I should see "Search results - Advanced features"
    And I should see "Enable audience-based visibility"
