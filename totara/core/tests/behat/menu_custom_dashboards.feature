@totara @totara_core @totara_core_menu @totara_dashboard
Feature: Test menu correctly highlights the custom dashboard menu
  In order to ensure the Totara menu works as expected
  As a user
  I want to see the correct custom dashboard page highlighted in the Totara menu

  @javascript
  Scenario: Default Totara dashboard menu should not be highlighted when Custom dashboard menu is used and vise versa
    Given I am on a totara site
    And I log in as "admin"
    And the following totara_dashboards exist:
      | name        | published |
      | Dashboard 2 | 2         |
      | Dashboard 3 | 2         |
      | Dashboard 4 | 2         |

    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I press "Add new menu item"
    And I set the following fields to these values:
      | Menu title               | My dashboards |
      | Menu default url address | /             |
    And I press "Add new menu item"

    And I press "Add new menu item"
    And I set the following fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 2                      |
      | Menu default url address | /totara/dashboard/index.php?id=2 |
    And I press "Add new menu item"

    And I press "Add new menu item"
    And I set the following fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 3                      |
      | Menu default url address | /totara/dashboard/index.php?id=3 |
    And I press "Add new menu item"

    And I press "Add new menu item"
    And I set the following fields to these values:
      | Parent item              | My dashboards                    |
      | Menu title               | Dashboard 4                      |
      | Menu default url address | /totara/dashboard/index.php?id=4 |
    And I press "Add new menu item"

    And I click on "Home" in the totara menu
    When I click on "Dashboard 2" in the totara menu
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "child-selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 2']" "xpath_element" should contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 3']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 4']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Dashboard']" "xpath_element" should not contain "selected"

    When I click on "Dashboard 3" in the totara menu
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "child-selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 2']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 3']" "xpath_element" should contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 4']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Dashboard']" "xpath_element" should not contain "selected"

    When I click on "Dashboard 4" in the totara menu
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should contain "child-selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 2']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 3']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//nav[@class='totara-menu-subnav']/ul/li[a/text()='Dashboard 4']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Dashboard']" "xpath_element" should not contain "selected"

    When I click on "Dashboard" in the totara menu
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Dashboard']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Dashboard']" "xpath_element" should not contain "child-selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='My dashboards']" "xpath_element" should not contain "child-selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li/ul/li[a/text()='Dashboard 2']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li/ul/li[a/text()='Dashboard 3']" "xpath_element" should not contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li/ul/li[a/text()='Dashboard 4']" "xpath_element" should not contain "selected"
