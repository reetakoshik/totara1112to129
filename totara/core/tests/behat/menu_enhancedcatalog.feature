@totara @totara_core @totara_core_menu
Feature: Test menu correctly highlights the course catalog page when enhanced catalog is disabled
  In order to understand the course catalog page I am currently viewing
  As a user
  I want to see the correct course catalogue page highlighted in the Totara menu

  @javascript
  Scenario: Enhanced catalog menu links should not be highlighted when it is disabled and viewing course index page
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Catalogue default view" to "moodle"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Menu title | Enhanced catalog |
      | Menu url address | /totara/coursecatalog/courses.php |
    And I press "Add"
    Then I should see "Enhanced catalog" in the totara menu
    When I follow "Enhanced catalog"
    Then Totara menu item "Enhanced catalog" should be highlighted
    And Totara menu item "Find Learning" should not be highlighted
    When I follow "Find Learning"
    And I follow "Courses"
    Then Totara menu item "Find Learning" should be highlighted
    And Totara menu item "Enhanced catalog" should not be highlighted

