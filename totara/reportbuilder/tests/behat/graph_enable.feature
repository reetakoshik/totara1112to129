@totara @totara_reportbuilder @javascript
Feature: Graphs in Report builder
  In order to use graphs in Report builder
  As an admin
  I need to enable the report graph setting
  And I configure it to display as I want

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname | country |
      | trainer1 | Trainer   | NZ      |
      | learner1 | Learner1  | NZ      |
      | learner2 | Learner2  | NZ      |
      | learner3 | Learner3  | US      |
      | learner4 | Learner4  | US      |
      | learner5 | Learner5  | AU      |
      | learner6 | Learner6  | CZ      |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | My user report |
      | Source      | User           |
    And I press "Create report"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"

  Scenario: Enable/Disable report graph setting and display report with graph and/or without graph
    Given I am on a totara site
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable report builder graphs" to "Show"
    And I press "Save changes"

    And I click on "Reports" in the totara menu
    And I follow "My user report"
    And I click on "Edit this report" "button"
    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type | Pie |
      | Category   | User's Country |
    And I press "Save changes"

    When I click on "View This Report" "link"
    Then I should see "My user report: 5 records shown"
    And I should see "33.33%"
    And I should see "22.22%"
    And I should see "11.11%"

  Scenario: Enable report graph setting and display report without graph
    Given I am on a totara site
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable report builder graphs" to "Show"
    And I press "Save changes"

    When I click on "Reports" in the totara menu
    And I follow "My user report"
    Then I should see "My user report: 5 records shown"
    And I should not see "33.33%"
    And I should not see "22.22%"
    And I should not see "11.11%"

  Scenario: Disable report graph setting and display report with graph
    # Create a report graph first and check it is visible.
    Given I am on a totara site
    And I click on "Reports" in the totara menu
    And I follow "My user report"
    And I click on "Edit this report" "button"

    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type | Pie |
      | Category   | User's Country |
    And I press "Save changes"

    When I click on "View This Report" "link"
    Then I should see "My user report: 5 records shown"
    And I should see "33.33%"
    And I should see "22.22%"
    And I should see "11.11%"

    # Now disable the global report graph.
    And I click on "Home" in the totara menu
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "report builder graphs" to "Disable"
    And I press "Save changes"

    # Check the report does not display the graph.
    When I click on "Reports" in the totara menu
    And I follow "My user report"
    Then I should not see "33.33%"
    And I should not see "22.22%"
    And I should not see "11.11%"

  Scenario: Disable report graph setting and display report without graph
    # Create a report graph first and check it is visible.
    Given I am on a totara site
    And I click on "Reports" in the totara menu
    And I follow "My user report"
    And I click on "Edit this report" "button"

    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type | Pie            |
      | Category   | User's Country |
    And I press "Save changes"

    When I click on "View This Report" "link"
    Then I should see "My user report: 5 records shown"
    And I should see "33.33%"
    And I should see "22.22%"
    And I should see "11.11%"

    # Remove the graph from the report and check it display nothing.
    And I click on "Edit this report" "button"
    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type   | None |
    And I press "Save changes"

    When I click on "View This Report" "link"
    Then I should see "My user report: 5 records shown"
    And I should not see "33.33%"
    And I should not see "22.22%"
    And I should not see "11.11%"

    # Now disable the global report graph.
    And I click on "Home" in the totara menu
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable report builder graphs" to "Disable"
    And I press "Save changes"

    # Check the report still display nothing.
    When I click on "Reports" in the totara menu
    And I follow "My user report"
    Then I should not see "33.33%"
    And I should not see "22.22%"
    And I should not see "11.11%"
