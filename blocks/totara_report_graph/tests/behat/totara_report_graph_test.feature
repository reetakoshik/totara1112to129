@totara @javascript @block_totara_report_graph
Feature: Test the basic functionality of the Totara report graph block
  In order to test the Totara report graph block
  As an admin I add an instance
  And I configure it to display as I want

  Scenario: Test I can add and configure a Totara report graph block instance
    Given the following "users" exist:
      | username | firstname | country |
      | trainer1 | Trainer   | NZ      |
      | learner1 | Learner1  | NZ      |
      | learner2 | Learner2  | NZ      |
      | learner3 | Learner3  | US      |
      | learner4 | Learner4  | US      |
      | learner5 | Learner5  | AU      |
      | learner6 | Learner6  | CZ      |

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | My user report |
      | Source      | User           |
    And I press "Create report"
    Then I should see "Edit Report 'My user report'"

    When I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"
    Then I should see "Columns updated"

    When I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type | Pie |
      | Category   | User's Country |
    And I press "Save changes"
    Then I should see "Graph updated"

    When I click on "View This Report" "link"
    Then I should see "My user report: 5 records shown"
    And I should see "Search by"
    And I should see "User's Fullname"
    And I should see "New Zealand"
    And I should see "United States"
    And I should see "Australia"
    And I should see "Czechia"
    And I should see "33.33%"
    And I should see "22.22%"
    And I should see "11.11%"

    When I click on "Dashboard" "link"
    And I press "Customise this page"
    And I add the "Report graph" block
    And I configure the "Report graph" block
    And I set the following fields to these values:
     | Override default block title | Yes                        |
     | Block title                  | My user report graph block |
     | Report                       | My user report             |
     | Max height                   | 400px                      |
     | Max width                    | 800px                      |
    And I press "Save changes"
    Then I should see "My user report graph block"
    And I should see "Report" in the "My user report graph block" "block"
