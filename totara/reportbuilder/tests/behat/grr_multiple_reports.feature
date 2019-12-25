@totara @totara_reportbuilder @javascript
Feature: Confirm global report restrictions work accross multiple reports
  In order to use Global report restrictions
  As a user
  I need to be able use restrictions accross multiple reports

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | user1    | User      | One      | user1@example.invalid |
      | user2    | User      | Two      | user2@example.invalid |
      | user3    | User      | Three    | user3@example.invalid |
      | user4    | User      | Four     | user4@example.invalid |
      | user5    | User      | Five     | user5@example.invalid |
      | user6    | User      | Six      | user6@example.invalid |
    And the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | System audience  | CH0      | 1          |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH0    |
      | user4 | CH0    |
      | user5 | CH0    |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report restrictions | 1 |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User report |
      | Source      | User        |
    And I press "Create report"
    And I switch to "Content" tab
    And I set the field "Global report restrictions" to "1"
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Audience report  |
      | Source      | Audience Members |
    And I press "Create report"
    And I switch to "Content" tab
    And I set the field "Global report restrictions" to "1"
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name        | test restriction 1    |
      | Description | This is a description |
      | Active      | 1                     |
    And I press "Save changes"
    And I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    And I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Three" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    And I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Three" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name        | test restriction 2    |
      | Description | This is a description |
      | Active      | 1                     |
    And I press "Save changes"
    And I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    And I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Six" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    And I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Three" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I log out

  Scenario: View multiple restriction names
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Audience report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    And I set the following fields to these values:
      | test restriction 2 | 1 |
      | test restriction 1 | 1 |
    And I click on "Save" "button" in the "Viewing records for:" "totaradialogue"
    Then I should see "Viewing records restricted by: test restriction 1, test restriction 2" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    And I set the following fields to these values:
      | test restriction 2 | 1 |
      | test restriction 1 | 0 |
    And I click on "Save" "button" in the "Viewing records for:" "totaradialogue"
    Then I should see "Viewing records restricted by: test restriction 2" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    And I set the following fields to these values:
      | test restriction 2 | 0 |
      | test restriction 1 | 1 |
    And I click on "Save" "button" in the "Viewing records for:" "totaradialogue"
    Then I should see "Viewing records restricted by: test restriction 1" in the ".globalrestrictionscontainer" "css_element"

  Scenario: View restrictions accross multiple reports
    Given I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Audience report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I click on "Reports" in the totara menu
    And I follow "User report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    Given I log in as "user2"
    And I click on "Reports" in the totara menu
    And I follow "Audience report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I click on "Reports" in the totara menu
    And I follow "User report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    Given I log in as "user3"
    And I click on "Reports" in the totara menu
    And I follow "Audience report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I click on "Reports" in the totara menu
    And I follow "User report"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I wait "1" seconds
    Then I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "test restriction" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out
