@totara @totara_reportbuilder @javascript
Feature: Create global report use restrictions
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
    And I navigate to "Create report" node in "Site administration > Reports > Report builder"
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
    And I navigate to "Global report restrictions" node in "Site administration > Reports > Report builder"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name        | test restriction      |
      | Description | This is a description |
      | Active      | 1                     |
    And I press "Save changes"
    Given I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    When I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Three" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds

  Scenario: Restrict restrictions based on individuals
    Given I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Individual assignment"
    And I wait "1" seconds
    When I click on "User One" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Two" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "User Three" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    Then I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    When I set the field "user-fullname" to "Two"
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should not see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should not see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user3"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    Then I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

    When I log in as "user6"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

  Scenario: Restrict restrictions based on a static audience
    Given the following "cohorts" exist:
      | name            | idnumber |
      | System audience | CH0      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH0    |
      | user2 | CH0    |
      | user3 | CH0    |
    And I navigate to "Global report restrictions" node in "Site administration > Reports > Report builder"
    And I click on "Edit" "link" in the "test restriction" "table_row"
    Given I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user3"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

    When I log in as "user6"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

  Scenario: Restrict restrictions based on a dynamic audience
    And the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | Dynamic audience | A1       | 2          |
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Dynamic audience"
    And I switch to "Rule sets" tab
    And I set the field "id_addrulesetmenu" to "Last name"
    And I wait "1" seconds
    And I set the field "listofvalues" to "F"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    And I navigate to "Global report restrictions" node in "Site administration > Reports > Report builder"
    And I click on "Edit" "link" in the "test restriction" "table_row"
    Given I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "Dynamic audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user5"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

    When I log in as "user6"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

  Scenario: Restrict restrictions based on an Orgainisation
    Given the following "organisation" frameworks exist:
      | fullname                    | idnumber | description           |
      | Test organisation framework | FW002    | Framework description |
    And the following "organisation" hierarchy exists:
      | framework | fullname           | idnumber | description             |
      | FW002     | Test Organisation  | ORG001   | This is an organisation |
    Given the following job assignments exist:
      | user  | organisation |
      | user1 | ORG001       |
      | user2 | ORG001       |
      | user3 | ORG001       |
    And I navigate to "Global report restrictions" node in "Site administration > Reports > Report builder"
    And I click on "Edit" "link" in the "test restriction" "table_row"
    Given I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Organisation"
    And I wait "1" seconds
    And I click on "Test Organisation" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user3"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    # Do not tell users what is going on, this is a required feature.
    Then ".globalrestrictionscontainer" "css_element" should not exist
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "User Four" in the ".reportbuilder-table" "css_element"
    And I should not see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
    And I log out

    When I log in as "user4"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out

    When I log in as "user6"
    And I click on "Reports" in the totara menu
    And I follow "User report"
    And ".globalrestrictionscontainer" "css_element" should not exist
    And I log out
