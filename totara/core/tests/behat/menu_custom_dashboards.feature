@totara @totara_core @totara_core_menu @totara_dashboard
Feature: Test menu correctly highlights the custom dashboard menu
  In order to ensure the Totara menu works as expected
  As a user
  I want to see the correct custom dashboard page highlighted in the Totara menu

  @javascript
  Scenario: Default Totara dashboard menu should not be highlighted when Custom dashboard menu is used and vice versa
    Given I am on a totara site
    And I log in as "admin"
    And the following totara_dashboards exist:
      | name        | published |
      | Dashboard 2 | 2         |
      | Dashboard 3 | 2         |
      | Dashboard 4 | 2         |

    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type                     | Parent        |
      | Menu title               | My dashboards |
    And I press "Add"

    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 2                      |
      | Menu url address         | /totara/dashboard/index.php?id=2 |
    And I press "Add"

    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 3                      |
      | Menu url address         | /totara/dashboard/index.php?id=3 |
    And I press "Add"

    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 4                      |
      | Menu url address         | /totara/dashboard/index.php?id=4 |
    And I press "Add"

    And I click on "Home" in the totara menu
    When I click on "Dashboard 2" in the totara menu
    Then Totara menu item "My dashboards" should be highlighted
    And Totara menu item "Dashboard" should not be highlighted
    And Totara sub menu item "Dashboard 2" should be highlighted
    And Totara sub menu item "Dashboard 3" should not be highlighted
    And Totara sub menu item "Dashboard 4" should not be highlighted

    When I click on "Dashboard 3" in the totara menu
    Then Totara menu item "My dashboards" should be highlighted
    And Totara menu item "Dashboard" should not be highlighted
    And Totara sub menu item "Dashboard 2" should not be highlighted
    And Totara sub menu item "Dashboard 3" should be highlighted
    And Totara sub menu item "Dashboard 4" should not be highlighted

    When I click on "Dashboard 4" in the totara menu
    Then Totara menu item "My dashboards" should be highlighted
    And Totara menu item "Dashboard" should not be highlighted
    And Totara sub menu item "Dashboard 2" should not be highlighted
    And Totara sub menu item "Dashboard 3" should not be highlighted
    And Totara sub menu item "Dashboard 4" should be highlighted

    When I click on "Dashboard" in the totara menu
    Then Totara menu item "Dashboard" should be highlighted
    Then Totara menu item "My dashboards" should not be highlighted
# collapsed subitems are accessible too, this is not about real 'visibility'
    And I should see "Dashboard 2" in the totara menu
    And I should see "Dashboard 3" in the totara menu
    And I should see "Dashboard 4" in the totara menu
