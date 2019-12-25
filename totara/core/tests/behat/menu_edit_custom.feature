@totara @totara_core @totara_core_menu @javascript
Feature: Main menu advanced edit custom items tests
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
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Top test item                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top container                 |
      | Menu title        | Sub test item                 |
      | Visibility        | Show                          |
      | Menu url address  | http://x.y.z/                 |
      | Open link in new window  | 0                      |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top container / Sub container |
      | Menu title        | Sub lower test item           |
      | Visibility        | Show                          |
      | Menu url address  | /yyyy                         |
      | Open link in new window  | 1                      |
    And I press "Add"

  Scenario: Edit custom items in Main menu

# Make sure forms have all necessary fields.

    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
      | Menu title        | Top test item                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
      | Open link in new window | 0                       |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"

    When I click on "Edit" "link" in the "Top container" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
      | Menu title        | Top container                 |
      | Visibility        | Show                          |
    And I should not see Totara form label "Menu url address"
    And I should not see Totara form label "Open link in new window"
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"

# Change titles.

    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu title        | Top test item                 |
    And I set the following Totara form fields to these values:
      | Menu title        | Special item                  |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Special item" in the totara menu
    When I click on "Edit" "link" in the "Special item" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu title        | Special item                  |
    And I set the following Totara form fields to these values:
      | Menu title        | Top test item                 |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Top test item" in the totara menu

    When I click on "Edit" "link" in the "Top container" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu title        | Top container                 |
    And I set the following Totara form fields to these values:
      | Menu title        | Special container             |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Special container" in the totara menu
    When I click on "Edit" "link" in the "Special container" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu title        | Special container             |
    And I set the following Totara form fields to these values:
      | Menu title        | Top container                 |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Top container" in the totara menu

# Change visibility.

    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Hide                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I should not see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Show                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Top test item" in the totara menu

    When I click on "Edit" "link" in the "Top container" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Hide                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top container" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I should not see "Top container" in the totara menu
    When I click on "Edit" "link" in the "Top container" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I set the following Totara form fields to these values:
      | Visibility        | Show                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Top container" in the totara menu

# Change parent.

    When I click on "Edit" "link" in the "Top test item" "table_row"
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
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container                 |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    And I should not see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Unused                        |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Unused                        |
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should see "Top test item" in the totara menu

    When I click on "Edit" "link" in the "Unused container" "table_row"
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
      | Parent item       | Unused                        |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container                 |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Show"
    When I click on "Edit" "link" in the "Unused container" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container                 |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    When I click on "Edit" "link" in the "Unused container" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    When I click on "Edit" "link" in the "Unused container" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top container / Sub container / Sub sub container |
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Show"
    When I click on "Edit" "link" in the "Unused container" "table_row"
    And I should see the following Totara form fields having these values:
      | Parent item       | Top                           |
    And I set the following Totara form fields to these values:
      | Parent item       | Unused                        |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Unused"

# Change URL.

    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu url address  | /xxx                          |
    And I set the following Totara form fields to these values:
      | Menu url address  | xxx                           |
    And I press "Save changes"
    Then I should see "Form could not be submitted, validation failed"
    When I set the following Totara form fields to these values:
      | Menu url address  | /yyy                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "URL address" column of "totaramenutable" table should contain "/yyy"
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu url address  | /yyy                          |
    And I set the following Totara form fields to these values:
      | Menu url address  | https://pokus.example.com/    |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "URL address" column of "totaramenutable" table should contain "https://pokus.example.com/"
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Menu url address  | https://pokus.example.com/    |
    And I set the following Totara form fields to these values:
      | Menu url address  | /xxx                          |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "URL address" column of "totaramenutable" table should contain "/xxx"

# Change new window opening.

    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Open link in new window  | 0                      |
    And I set the following Totara form fields to these values:
      | Open link in new window  | 1                      |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Top test item" in the totara menu
    When I click on "Edit" "link" in the "Top test item" "table_row"
    And I should see the following Totara form fields having these values:
      | Open link in new window  | 1                      |
    And I set the following Totara form fields to these values:
      | Open link in new window  | 0                      |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Top test item" in the totara menu
