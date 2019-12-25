@totara @totara_core @totara_core_menu @javascript
Feature: Main menu error state testing
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top                           |
      | Menu title        | Dual item                     |
      | Visibility        | Show                          |
      | Menu url address  | /zzz                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Orphaned item                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Uninstalled item              |
      | Visibility        | Show                          |
      | Menu url address  | /yyy                          |
    And I press "Add"
    And I use magic for Main menu to make invalid menu items
    And I log out

  Scenario: Test deleting of orphaned main menu item after upgrade
    When I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Dual item" in the totara menu
    And I should not see "Orphaned item" in the totara menu
    And "Orphaned item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should not contain "Hide"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should contain "Edit"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should contain "Delete"

    When I click on "Delete" "link" in the "Orphaned item" "table_row"
    And I press "Delete"
    Then I should see "Menu item has been deleted"
    And I should not see "Orphaned item"

  Scenario: Test updating of orphaned main menu item after upgrade
    When I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Dual item" in the totara menu
    And I should not see "Orphaned item" in the totara menu
    And "Orphaned item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should not contain "Hide"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should contain "Edit"
    And "Orphaned item" row "Edit" column of "totaramenutable" table should contain "Delete"

    When I click on "Edit" "link" in the "Orphaned item" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item       | Top                           |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Orphaned item" in the totara menu
    And "Orphaned item" row "Visibility" column of "totaramenutable" table should contain "Show"

  Scenario: Test deleting of uninstalled main menu item
    When I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I should not see "Uninstalled item" in the totara menu
    And "\some_plugin\totara\menu\someitem" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "\some_plugin\totara\menu\someitem" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "\some_plugin\totara\menu\someitem" row "Edit" column of "totaramenutable" table should not contain "Hide"
    And "\some_plugin\totara\menu\someitem" row "Edit" column of "totaramenutable" table should not contain "Edit"
    And "\some_plugin\totara\menu\someitem" row "Edit" column of "totaramenutable" table should contain "Delete"

    When I click on "Delete" "link" in the "\some_plugin\totara\menu\someitem" "table_row"
    And I press "Delete"
    Then I should see "Menu item has been deleted"
    And I should not see "\some_plugin\totara\menu\someitem"
