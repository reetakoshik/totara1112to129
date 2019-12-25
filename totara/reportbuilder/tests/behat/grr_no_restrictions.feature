@totara @totara_reportbuilder @javascript
Feature: Create global report no restrictions
  In order to use Global report restrictions
  As a user
  I need to be able use restrictions

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
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report restrictions | 1 |
    And I press "Save changes"
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
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name        | test restriction      |
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

  Scenario: See no entries when logged in as a user who has access to no global report restrictions
    Given I set the following administration settings values:
      | Global restriction behaviour for users with no active restrictions | Show no records |
    And I log out
    And I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    Then I should see "There are no records in this report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist

  Scenario: See no entries when logged in as a user who has access to no global report restrictions
    Given I set the following administration settings values:
      | Global restriction behaviour for users with no active restrictions | Show all records |
    And I log out
    And I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should see "User Four" in the ".reportbuilder-table" "css_element"
    And I should see "User Five" in the ".reportbuilder-table" "css_element"
    And I should see "User Six" in the ".reportbuilder-table" "css_element"
