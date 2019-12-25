@core @totara @totara_core @javascript @totara_core_adminmenu
Feature: Totara settings for admin main menu
  In order to configure the admin main menu
  As a user
  I need to be able to add and remove items

  Background:
    Given I am on a totara site

  Scenario: Ensure only users with the correct capability can configure their own menu
    Given the following "users" exist:
      | username | firstname | lastname | email                    |
      | manager1 | manager1  | one      | managerone@example.com   |
      | manager2 | manager2  | two      | managertwo@example.com   |
      | manager3 | manager3  | three    | managerthree@example.com |
    And the following "roles" exist:
      | name           | shortname |
      | User manager 1 | userman1  |
      | User manager 2 | userman2  |
      | User manager 3 | userman3  |
    And the following "role assigns" exist:
      | user     | role     | contextlevel | reference |
      | manager1 | userman1 | System       |           |
      | manager2 | userman2 | System       |           |
      | manager3 | userman3 | System       |           |
    And the following "permission overrides" exist:
      | capability                         | permission | role     | contextlevel | reference |
      | totara/plan:configureplans         | Allow      | userman1 | System       |           |
      | totara/plan:configureplans         | Allow      | userman2 | System       |           |
      | totara/plan:configureplans         | Allow      | userman3 | System       |           |
      | totara/core:editownquickaccessmenu | Allow      | userman1 | System       |           |
      | totara/core:editownquickaccessmenu | Prohibit   | userman2 | System       |           |
      | totara/core:editownquickaccessmenu | Allow      | userman3 | System       |           |
      | moodle/user:editownprofile         | Allow      | userman1 | System       |           |
      | moodle/user:editownprofile         | Allow      | userman2 | System       |           |
      | moodle/user:editownprofile         | Prohibit   | userman3 | System       |           |

    When I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Administration navigation settings"
    And I log out

    When I log in as "manager1"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Administration navigation settings"
    And I log out

    When I log in as "manager2"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Menu settings"
    And I log out

    When I log in as "manager3"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Menu settings"

  Scenario: As a user I can add a new admin menu group
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Add a new group" "button"
    Then I should see "Untitled"

  Scenario: As a user I can rename an existing admin menu group
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Core platform"
    When I rename admin main menu group "Core platform" to "New group name"
    Then I should see "New group name"
    And I should not see "Core platform"
    When I am on homepage
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "New group name"
    And I should not see "Core platform"

  Scenario: As a user I delete a existing admin menu group
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Core platform"
    And I open the action menu in "//div[@aria-label='Core platform']" "xpath_element"
    And I choose "Delete group" in the open action menu
    And I am on homepage
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Core platform"

  Scenario: As a user I can add items into my admin menu
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I should not see "Audience global settings"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Add a new group" "button"
    Then I should see "Untitled"
    When I click on "//div/child::h3[contains(., 'Untitled')]/span" "xpath_element"
    And I click on "Add menu item..." "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I click on "Audiences" "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I click on "Audience global settings" "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I am on homepage
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Audience global settings"

  Scenario: As a user I can reset my admin menu
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Core platform"
    And I open the action menu in "//div[@aria-label='Core platform']" "xpath_element"
    And I choose "Delete group" in the open action menu
    And I click on "Add a new group" "button"
    And I should see "Untitled"
    And I click on "//div/child::h3[contains(., 'Untitled')]/span" "xpath_element"
    And I click on "Add menu item..." "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I click on "Audiences" "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I click on "Audience global settings" "link" in the "//div[@aria-label=\"Untitled\"]/.." "xpath_element"
    And I am on homepage
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Core platform"
    And I should see "Untitled"
    And I should see "Audience global settings"
    When I click on "Menu settings" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Reset admin menu"
    And I click on "Continue" "button"
    Then I should see "Your administration navigation preferences have been reset to default configuration."
    And I should see "Core platform"
    And I should see "Learning"
    And I should see "Performance"
    And I should see "Configuration"
    And I should not see "Untitled"
    And I should not see "Audience global settings"

  Scenario: As a user I can add and remove pages within the admin main menu from the page in question
    Given I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Users" in the "#quickaccess-popover-content" "css_element"
    When I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Browse list of users:"
    When I press "Remove from admin menu"
    Then I should see "Page removed from the admin menu"
    And I should not see "Remove from admin menu"
    And I should see "Add to admin menu"
    And I should see "Browse list of users:"
    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Users" in the "#quickaccess-popover-content" "css_element"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "group" to "Core platform"
    Then I should see "Page added to the admin menu"
    And I should not see "Add to admin menu"
    And I should see "Browse list of users:"
    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Users" in the "#quickaccess-popover-content" "css_element"
