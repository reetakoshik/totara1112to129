@totara @totara_dashboard
Feature: Perform basic dashboard layout changes
  In order to ensure that dashboard work as expected
  As an admin
  I need to manage dashboard layout by adding/deleting blocks

  Background:
    Given I am on a totara site
    And the following totara_dashboards exist:
    | name | locked | published |
    | Dashboard for edit | 1 | 1 |

  @javascript
  Scenario: Add block to master dashboard
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Dashboard for edit" "link"
    And I press "Blocks editing on"
    And I add the "Latest announcements" block
    Then "Latest announcements" "block" should exist
    And I reload the page
    And "Latest announcements" "block" should exist

  @javascript
  Scenario: Delete block from master dashboard
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Dashboard for edit" "link"
    And I press "Blocks editing on"
    And I add the "Latest announcements" block
    And I should see "Latest announcements"
    And I open the "Latest announcements" blocks action menu
    When I click on ".editing_delete" "css_element" in the "Latest announcements" "block"
    And I press "Yes"
    Then "Latest announcements" "block" should not exist
    And I reload the page
    And "Latest announcements" "block" should not exist
