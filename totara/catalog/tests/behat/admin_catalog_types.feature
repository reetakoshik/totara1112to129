@totara @totara_catalog @javascript @totara_core_menu
Feature: Admin can set catalog type in Advanced features
  As an administrator
  I need to be able to set the catalog type
  In order to choose the catalog that best fits my needs

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Switching between catalogs adjusts top navigation and site admin menu
    When I set the following administration settings values:
      | catalogtype | moodle |
    And I navigate to "Courses" node in site administration
    Then I should not see "Configure catalogue"
    And I should see "Courses" in the totara menu
    And I should see "Programs" in the totara menu
    And I should see "Certifications" in the totara menu
    When I click on "Courses" in the totara menu
    Then I should see the "moodle" catalog page

    When I set the following administration settings values:
      | catalogtype | enhanced |
    And I navigate to "Courses" node in site administration
    Then I should not see "Configure catalogue"
    And I should see "Courses" in the totara menu
    And I should see "Programs" in the totara menu
    And I should see "Certifications" in the totara menu
    And I click on "Courses" in the totara menu
    Then I should see the "enhanced" catalog page

    When I set the following administration settings values:
      | catalogtype | totara |
    And I navigate to "Courses" node in site administration
    Then I should see "Configure catalogue"
    When I start watching to see if a new page loads
    And I click on "Find Learning" in the totara menu
    Then a new page should have loaded since I started watching
    And I should see the "totara" catalog page

  Scenario Outline: Disabled totara catalog page shows info message and link to current catalog
    Given I set the following administration settings values:
      | catalogtype | <Catalog type> |
    When I am on totara catalog page
    Then I should see "The page you are looking for is no longer active. All courses can be found under Find Learning."
    When I click on "Find Learning" "link" in the ".alert-message" "css_element"
    Then I should see the "<Catalog type>" catalog page

    Examples:
      | Catalog type |
      | moodle       |
      | enhanced     |