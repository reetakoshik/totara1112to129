@totara @totara_reportbuilder
Feature: Graphs in Report buidler
  In order to use graphs in Report builder
  As an admin
  I need to be able to set up the graphs

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And I log in as "admin"

  @javascript
  Scenario: Enable and disable graph in Report builder
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

    When I set the following fields to these values:
      | Graph type   | None |
    And I press "Save changes"
    Then I should see "Graph updated"
