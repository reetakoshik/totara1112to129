@totara @totara_core @totara_core_menu
Feature: Test menu correctly highlights the course catalog page when enhanced catalog is disabled
  In order to understand the course catalog page I am currently viewing
  As a user
  I want to see the correct course catalogue page highlighted in the Totara menu

  @javascript
  Scenario: Enhanced catalog menu links should not be highlighted when it is disabled and viewing course index page
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enhanced catalog" to "0"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I press "Add new menu item"
    And I set the following fields to these values:
      | Menu title | Enhanced catalog |
      | Menu default url address | /totara/coursecatalog/courses.php |
    And I press "Add new menu item"
    Then I should see "Enhanced catalog" in the totara menu
    When I follow "Enhanced catalog"
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Enhanced catalog']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']//ul/li[a/text()='Find Learning']" "xpath_element" should not contain "selected"
    When I follow "Find Learning"
    Then the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Find Learning']" "xpath_element" should contain "selected"
    And the "class" attribute of "//*[@id='totaramenu']/ul/li[a/text()='Enhanced catalog']" "xpath_element" should not contain "selected"

