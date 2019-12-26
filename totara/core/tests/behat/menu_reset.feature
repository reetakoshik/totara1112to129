@totara @totara_core @totara_core_menu @javascript
Feature: Main menu advanced reset tests
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent            |
      | Parent item       | Top               |
      | Menu title        | Top container     |
      | Visibility        | Show              |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent            |
      | Parent item       | Top container     |
      | Menu title        | Sub container     |
      | Visibility        | Show              |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent            |
      | Parent item       | Top               |
      | Menu title        | Hidden container  |
      | Visibility        | Hide              |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL               |
      | Parent item       | Top               |
      | Menu title        | Test top item     |
      | Visibility        | Show              |
      | Menu url address  | /index.php        |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL               |
      | Parent item       | Top container     |
      | Menu title        | Test sub item     |
      | Visibility        | Show              |
      | Menu url address  | /index.php        |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL               |
      | Parent item       | Top container     |
      | Menu title        | Test hidden item  |
      | Visibility        | Hide              |
      | Menu url address  | /index.php        |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL               |
      | Parent item       | Unused            |
      | Menu title        | Test unused item  |
      | Visibility        | Show              |
      | Menu url address  | /index.php        |
    And I press "Add"
    And I click on "Edit" "link" in the "Performance" "table_row"
    And I set the following Totara form fields to these values:
      | Override menu title | 1         |
      | Menu title          | Vykonnost |
    And I press "Save changes"
    And I click on "Edit" "link" in the "Reports" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item  | Unused        |
      | Visibility   | Hide          |
    And I press "Save changes"

    And "Top container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Sub container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Hidden container" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And "Test top item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Test sub item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Test hidden item" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And "Test unused item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Vykonnost" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Reports" row "Visibility" column of "totaramenutable" table should contain "Unused"

  Scenario: Reset advanced Main menu to default with full delete
    When I click on "Reset menu to default configuration" "button"
    And I set the following Totara form fields to these values:
      | All custom items will be | permanently deleted |
    And I press "Reset"
    Then I should see "Main menu has been reset to default configuration"
    And I should not see "Top container"
    And I should not see "Sub container"
    And I should not see "Hidden container"
    And I should not see "Test top item"
    And I should not see "Test sub item"
    And I should not see "Test hidden item"
    And I should not see "Test unused item"
    And I should not see "Vykonnost"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Reports" row "Visibility" column of "totaramenutable" table should contain "Show"

  Scenario: Reset advanced Main menu to default with custom backup
    When I click on "Reset menu to default configuration" "button"
    And I set the following Totara form fields to these values:
      | All custom items will be | hidden from menu and available in menu settings |
    And I press "Reset"
    Then I should see "Main menu has been reset to default configuration"
    And "Top container" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Sub container" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Hidden container" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Test top item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Test sub item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Test hidden item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Test unused item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Vykonnost"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Reports" row "Visibility" column of "totaramenutable" table should contain "Show"
