@totara @totara_core @totara_core_menu @javascript
Feature: A basic test of the Totara Main menu
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Add new menu item" "button"
    And I set the following Totara form fields to these values:
      | Parent item              | Top        |
      | Menu title               | Test item  |
      | Visibility               | Show       |
      | Menu url address         | /index.php |
    And I click on "Add" "button"
    And I should see "Test item" in the totara menu

  Scenario: Reset Main menu to default with custom backup
    Given I navigate to "Main menu" node in "Site administration > Navigation"
    When I click on "Reset menu to default configuration" "button"
    And I set the following Totara form fields to these values:
      | All custom items will be | hidden from menu and available in menu settings |
    And I press "Reset"
    Then I should see "Main menu has been reset to default configuration"
    And I should not see "Test item" in the totara menu
    And I should see "Test item" in the "#totaramenutable" "css_element"

  Scenario: Reset Main menu to default with full delete
    Given I navigate to "Main menu" node in "Site administration > Navigation"
    When I click on "Reset menu to default configuration" "button"
    And I set the following Totara form fields to these values:
      | All custom items will be | permanently deleted |
    And I press "Reset"
    Then I should see "Main menu has been reset to default configuration"
    And I should not see "Test item" in the totara menu
    And I should not see "Test item" in the "#totaramenutable" "css_element"

  Scenario: Change Main menu item parent
    Given I navigate to "Main menu" node in "Site administration > Navigation"
    When I click on "Edit" "link" in the "Test item" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item | Performance |
    And I click on "Save changes" "button"
    Then I should see "Main menu has been updated successfully"

  Scenario: Test Main menu item visibility using form
    Given I click on "Edit" "link" in the "Test item" "table_row"
    And I set the following Totara form fields to these values:
      | Visibility | Hide |
    And I click on "Save changes" "button"
    Then I should not see "Test item" in the totara menu
    When I click on "Edit" "link" in the "Test item" "table_row"
    And I set the following Totara form fields to these values:
      | Visibility | Show |
    And I click on "Save changes" "button"
    Then I should see "Test item" in the totara menu

  Scenario: Test visibility using table
    When I click on "Hide" "link" in the "Test item" "table_row"
    Then I should not see "Test item" in the totara menu
    When I click on "Show" "link" in the "Test item" "table_row"
    Then I should see "Test item" in the totara menu

  @javascript
  Scenario: Move Main menu items
    Given I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Add new menu item" "button"
    And I set the following Totara form fields to these values:
      | Parent item              | Top          |
      | Menu title               | Another item |
      | Visibility               | Show         |
      | Menu url address         | /index.php   |
    And I click on "Add" "button"
    And I should see "Another item" in the totara menu
    When I click on "Move up" "link" in the "Another item" "table_row"
    Then "Another item" "link" should appear before "Test item" "link"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Move down" "link" in the "Another item" "table_row"
    Then "Test item" "link" should appear before "Another item" "link"

  Scenario: Delete Main menu items
    Given I navigate to "Main menu" node in "Site administration > Navigation"
    When I click on "Delete" "link" in the "Test item" "table_row"
    And I click on "Delete" "button"
    Then I should see "Menu item has been deleted"
    And I should not see "Test item" in the totara menu
    And I should not see "Test item" in the "#totaramenutable" "css_element"

