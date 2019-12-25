@totara @totara_reportbuilder @javascript
Feature: Graph source columns in the report builder
  In order to fail graph columns in Report builder
  As an admin
  I need to be able to notice an user to fix the graph columns

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And I log in as "admin"

  Scenario: Add and delete graph column in Report builder
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Custom User report |
      | Source      | User               |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "User's Courses Started Count" column to the report
    And I switch to "Graph" tab

    When I press "Save changes"
    Then I should see "Graph updated"

    When I set the following fields to these values:
      | Graph type   | Column                       |
      | Data sources | User's Courses Started Count |
    And I press "Save changes"
    Then I should see "Graph updated"

    And I switch to "Columns" tab
    When I delete the "User's Courses Started Count" column from the report
    Then I should see "This column is the data source for Graph construction. Please delete the column first under Graph tab."

    When I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type   | |
    And I press "Save changes"
    And I switch to "Columns" tab
    And I delete the "User's Courses Started Count" column from the report
    Then I should not see "This column is the data source for Graph construction. Please delete the column first under Graph tab."
