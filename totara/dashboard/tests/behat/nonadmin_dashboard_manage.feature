@totara @totara_dashboard
Feature: Dashboard management by non admin user with just enough capabilities
  In order to ensure that non admin user can manage dashboard
  As an user with minimal required capabilities
  I need to manage dashboard layout by adding/deleting blocks

  Background:
    Given I am on a totara site
    And the following totara_dashboards exist:
      | name | locked | published |
      | Dashboard for edit | 1 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | idnumber | email                |
      | student1 | Sam1      | Student1 | sid#1    | student1@example.com |

    Given the following "roles" exist:
      | name              | shortname |
      | Dashboard manager | dashman   |
    And the following "system role assigns" exist:
      | user     | role      |
      | student1 | dashman   |
    And I log in as "admin"
    And I set the following system permissions of "Dashboard manager" role:
      | capability                   | permission |
      | moodle/my:configsyspages     | Allow      |
      | totara/dashboard:manage      | Allow      |
      | block/news_items:addinstance | Allow      |
      | moodle/block:edit            | Allow      |
    And I log out

  @javascript
  Scenario: Add block to master dashboard
    Given I log in as "student1"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Dashboard for edit" "link"
    And I press "Blocks editing on"
    And I add the "Latest announcements" block
    Then "Latest announcements" "block" should exist
    And I reload the page
    And "Latest announcements" "block" should exist

  @javascript
  Scenario: Delete block from master dashboard
    Given I log in as "student1"
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
