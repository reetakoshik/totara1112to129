@totara @totara_reportbuilder @totara_cohort @javascript
Feature: Cohort report filter
  As an admin
  I should be able to filter using a cohort using the report builder

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user1 | CH2    |

  Scenario: Test cohort report builder filter
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User report  |
      | Source      | User         |
    And I click on "Create report" "button"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User is a member of audience"
    And I click on "Add" "button"
    And I click on "View This Report" "link"
    And I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I click on "Add audiences" "link"
    And I click on "Cohort 2" "link" in the "Choose audiences" "totaradialogue"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

    And I click on "Add audiences" "link"
    And I click on "Cohort 1" "link" in the "Choose audiences" "totaradialogue"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should see "User Two"
    And I should not see "User Three"
