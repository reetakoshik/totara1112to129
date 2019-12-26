@totara @totara_core @totara_core_menu @javascript
Feature: Main menu advanced edit default items tests
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Top container                 |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top container                 |
      | Menu title        | Sub container                 |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top container / Sub container |
      | Menu title        | Sub sub container             |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Unused                        |
      | Menu title        | Unused container              |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Hidden container              |
      | Visibility        | Hide                          |
    And I press "Add"

  Scenario: Edit default items in Main menu

# Make sure forms have all necessary fields.

    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
      | Override menu title | 0                           |
      | Visibility        | Show when accessible          |
    And I should not see Totara form label "Menu title"
    And I should not see Totara form label "Menu url address"
    And I should not see Totara form label "Open link in new window"
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"

    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
      | Override menu title | 0                           |
      | Visibility        | Show                          |
    And I should not see Totara form label "Menu title"
    And I should not see Totara form label "Menu url address"
    And I should not see Totara form label "Open link in new window"
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"

# Change titles.

    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 0                           |
    And I set the following Totara form fields to these values:
      | Override menu title | 1                           |
      | Menu title        | Special item                  |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Special item" in the totara menu
    When I click on "Edit" "link" in the "Special item" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 1                           |
      | Menu title        | Special item                  |
    And I set the following Totara form fields to these values:
      | Override menu title | 0                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 0                           |
    And I set the following Totara form fields to these values:
      | Override menu title | 1                           |
    And I should see the following Totara form fields having these values:
      | Menu title        | Special item                  |
    And I press "Cancel"
    And I should see "Dashboard" in the totara menu

    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 0                           |
    And I set the following Totara form fields to these values:
      | Override menu title | 1                           |
      | Menu title        | Special container             |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Special container" in the totara menu
    When I click on "Edit" "link" in the "Special container" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 1                           |
      | Menu title        | Special container             |
    And I set the following Totara form fields to these values:
      | Override menu title | 0                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Performance" in the totara menu
    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Override menu title | 0                           |
    And I set the following Totara form fields to these values:
      | Override menu title | 1                           |
    And I should see the following Totara form fields having these values:
      | Menu title        | Special container             |
    And I press "Cancel"
    And I should see "Performance" in the totara menu

# Change visibility.

    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show when accessible          |
    And I set the following Totara form fields to these values:
      | Visibility        | Hide                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I should not see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Show when accessible          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"
    And I should see "Dashboard" in the totara menu

    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Hide                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I should not see "Performance" in the totara menu
    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Show                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Performance" in the totara menu

# Change parent.

    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And the "Parent item" select box should contain "Top"
    And the "Parent item" select box should contain "Performance"
    And the "Parent item" select box should contain "Find Learning (Legacy catalogues)"
    And the "Parent item" select box should contain "Top container"
    And the "Parent item" select box should contain "Top container / Sub container"
    And the "Parent item" select box should contain "Hidden container"
    And the "Parent item" select box should contain "Unused"
    And the "Parent item" select box should contain "Top container / Sub container / Sub sub container"
    And the "Parent item" select box should not contain "Unused / Unused container"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container                 |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"
    And I should see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container                 |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"
    And I should see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    And I should not see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Unused                        |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Dashboard" in the totara menu
    When I click on "Edit" "link" in the "Dashboard" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Unused                        |
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Dashboard" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"
    And I should see "Dashboard" in the totara menu

    When I click on "Edit" "link" in the "Performance" "table_row"
    And the "Parent item" select box should contain "Top"
    And the "Parent item" select box should contain "Performance"
    And the "Parent item" select box should contain "Find Learning (Legacy catalogues)"
    And the "Parent item" select box should contain "Top container"
    And the "Parent item" select box should contain "Top container / Sub container"
    And the "Parent item" select box should contain "Hidden container"
    And the "Parent item" select box should contain "Unused"
    And the "Parent item" select box should contain "Top container / Sub container / Sub sub container"
    And the "Parent item" select box should not contain "Unused / Unused container"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                          |
    And I set the following Totara form fields to these values:
      | Parent item       | Unused                       |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Unused"
    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Unused                        |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    When I click on "Edit" "link" in the "Performance" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Performance" row "Visibility" column of "totaramenutable" table should contain "Show"
