@javascript @totara @totara_dashboard
Feature: Perform basic dashboard administration
  In order to ensure that dashboard work as expected
  As an admin
  I need to manage master version of dashboard layout

  Background:
    Given I am on a totara site
    And the following totara_dashboards exist:
    | name | locked | published |
    | Dashboard for edit | 1 | 1 |

  Scenario: Add block to default dashboard
    When I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I press "Create dashboard"
    And I set the following fields to these values:
      | Name | Behat Test Dashboard |
    And I click on "Available only to the following audiences" "radio"
    And I press "Create dashboard"
    Then I should see "Behat Test Dashboard" in the ".generaltable" "css_element"
    And I should see "Dashboard saved" in the ".alert-success" "css_element"

  Scenario: Edit dashboard
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I should see "Dashboard for edit" in the ".generaltable" "css_element"
    And I click on ".edit" "css_element" in the "Dashboard for edit" "table_row"
    And I set the following fields to these values:
      | name | Edited Behat Test Dashboard |
      | Locked | 0 |
    And I click on "Available to no users" "radio"
    And I press "id_submitbutton"
    Then I should see "Edited Behat Test Dashboard" in the ".generaltable" "css_element"
    And I should see "Dashboard saved" in the ".alert-success" "css_element"

  Scenario: Check available to all dashboard management
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I press "Create dashboard"
    And I set the following fields to these values:
      | Name   | Public dashboard |
      | Locked | 0                |
    And I click on "Available to all logged in users" "radio"
    And I press "Create dashboard"
    And I should see "Available to all logged in users" in the "Public dashboard" "table_row"
    And I click on "Edit dashboard" "link" in the "Public dashboard" "table_row"
    When I press "Save changes"
    Then I should see "Available to all logged in users" in the "Public dashboard" "table_row"

  Scenario: Assign audience to dashboard and then make it public
    Given I log in as "admin"
    Given the following "cohorts" exist:
      | name    | idnumber |
      | Cohort1 | COHORT1  |
      | Cohort2 | COHORT2  |
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I press "Create dashboard"
    And I set the following fields to these values:
      | Name | Audience dashboard |
    And I click on "Available only to the following audiences" "radio"
    And I press "Assign new audiences"
    And I click on "Cohort1" "link"
    And I click on "Cohort2" "link"
    And I press "OK"
    Then I should see "Audience name"
    And I should see "Cohort1"
    And I should see "Cohort2"
    And I press "Create dashboard"
    And I should see "2" in the "Audience dashboard" "table_row"
    # Check saving changes
    And I click on "Edit dashboard" "link" in the "Audience dashboard" "table_row"
    And I click on "Available to all logged in users" "radio"
    When I press "Save changes"
    Then I should see "Available to all logged in users" in the "Audience dashboard" "table_row"

  Scenario: Delete dashboard
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I click on ".delete" "css_element" in the "Dashboard for edit" "table_row"
    And I should see "Do you really want to remove dashboard"
    And I press "Continue"
    And I click on ".delete" "css_element" in the "My Learning" "table_row"
    And I should see "Do you really want to remove dashboard"
    And I press "Continue"
    Then ".generaltable" "css_element" should not exist
    And I should see "No dashboards"

  @javascript
  Scenario: Move dashboard sort order up
    Given the following totara_dashboards exist:
        | name |
        | Dashboard 2 |
        | Dashboard 3 |
    And I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I click on ".up" "css_element" in the "Dashboard 2" "table_row"
    Then "Dashboard 2" "link" should appear before "Dashboard for edit" "link"
    And "Dashboard for edit" "link" should appear before "Dashboard 3" "link"

  @javascript
  Scenario: Move dashboard sort order down
    Given the following totara_dashboards exist:
        | name |
        | Dashboard 2 |
        | Dashboard 3 |
    And I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I click on ".down" "css_element" in the "Dashboard 2" "table_row"
    Then "Dashboard 3" "link" should appear before "Dashboard 2" "link"
    And "Dashboard for edit" "link" should appear before "Dashboard 3" "link"
