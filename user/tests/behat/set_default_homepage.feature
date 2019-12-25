@core @core_user
Feature: Set the site home page and dashboard as the default home page
  In order to set a page as my default home page
  As a user
  I need to go to the page I want and set it as my home page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |

  @javascript
  Scenario: Admin sets the site page and then the dashboard as the default home page
    Given I log in as "admin"
    And I navigate to "Navigation > Navigation settings" in site administration
    And I set the field "Allow default page selection" to "1"
    And I press "Save changes"
    And I am on site homepage
    And I follow "Dashboard"
    And I follow "Make Dashboard my default page"
    And I should not see "Make Dashboard my default page"
    And I should see "Dashboard" in the ".breadcrumb-nav" "css_element"
    And "//*[@class='breadcrumb-nav']//li/span/a/span[text()='Dashboard']" "xpath_element" should exist
    And I click on "Home" in the totara menu
    And I follow "Make Home my default page"
    And I should not see "Make Home my default page"
    When I am on "Course 1" course homepage
    Then "//*[@class='breadcrumb-nav']//li/span/a/span[text()='Home']" "xpath_element" should exist
