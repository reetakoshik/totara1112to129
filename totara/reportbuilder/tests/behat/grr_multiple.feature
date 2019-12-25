@totara @totara_reportbuilder @javascript
Feature: Global report restrictions multiple interactions
  In order to use Global report restrictions
  As a user
  I need to be able to use multiple restrictions

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

    # Set up audiences.
    Given the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | System audience  | CH0      | 1          |
      | Dynamic audience | D1       | 2          |

    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH0    |
      | user2 | CH0    |
      | user3 | CH0    |
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I switch to "Rule sets" tab
    And I set the field "id_addrulesetmenu" to "Last name"
    And I wait "1" seconds
    And I set the field "listofvalues" to "F"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"

    # Set up organisations
    Given the following "organisation" frameworks exist:
      | fullname                    | idnumber | description           |
      | Test organisation framework | FW002    | Framework description |
    And the following "organisation" hierarchy exists:
      | framework | fullname           | idnumber | description             |
      | FW002     | Test Organisation  | ORG001   | This is an organisation |
    Given the following job assignments exist:
      | user  | organisation |
      | user1 | ORG001       |
      | user4 | ORG001       |
      | user5 | ORG001       |
    And I set the following administration settings values:
      | Enable report restrictions | 1 |
    And I press "Save changes"
    And the following "report_restrictions" exist in "totara_reportbuilder" plugin:
      | name           | description   | active | allrecords | allusers |
      | no restriction | Restriction 1 | 1      | 1          | 1        |
      | limited uses   | Restriction 2 | 1      | 1          | 0        |
      | limited user   | Restriction 3 | 1      | 0          | 1        |
      | dynamic static | Restriction 4 | 1      | 0          | 0        |
      | org dynamic    | Restriction 5 | 1      | 0          | 0        |
      | org static     | Restriction 6 | 1      | 0          | 0        |
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I click on "Edit" "link" in the "limited uses" "table_row"
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I follow "All Restrictions"

    And I click on "Edit" "link" in the "limited user" "table_row"
    And I switch to "View records related to" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I follow "All Restrictions"

    And I click on "Edit" "link" in the "dynamic static" "table_row"
    And I switch to "View records related to" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Dynamic audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Dynamic audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I follow "All Restrictions"

    And I click on "Edit" "link" in the "org dynamic" "table_row"
    And I switch to "View records related to" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "Dynamic audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I set the field "menugroupselector" to "Organisation"
    And I wait "1" seconds
    And I click on "Test Organisation" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "Dynamic audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I set the field "menugroupselector" to "Organisation"
    And I wait "1" seconds
    And I click on "Test Organisation" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I follow "All Restrictions"

    And I click on "Edit" "link" in the "org static" "table_row"
    And I switch to "View records related to" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I set the field "menugroupselector" to "Organisation"
    And I wait "1" seconds
    And I click on "Test Organisation" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "System audience" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I set the field "menugroupselector" to "Organisation"
    And I wait "1" seconds
    And I click on "Test Organisation" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I follow "All Restrictions"

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

  Scenario: Test complex global report visibility
    Given I click on "Reports" in the totara menu
    When I follow "User report"
    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    Then I should see "no restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "limited user" in the "Viewing records for:" "totaradialogue"
    And I should not see "limited uses" in the "Viewing records for:" "totaradialogue"
    And I should not see "dynamic static" in the "Viewing records for:" "totaradialogue"
    And I should not see "org dynamic" in the "Viewing records for:" "totaradialogue"
    And I should not see "org static" in the "Viewing records for:" "totaradialogue"
    When I click on "Select all" "link" in the "Viewing records for:" "totaradialogue"
    Then the field "no restriction" matches value "1"
    And the field "limited user" matches value "1"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user1"
    And I click on "Reports" in the totara menu
    When I follow "User report"
    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    Then I should see "no restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "limited user" in the "Viewing records for:" "totaradialogue"
    And I should see "limited uses" in the "Viewing records for:" "totaradialogue"
    And I should see "dynamic static" in the "Viewing records for:" "totaradialogue"
    And I should see "org dynamic" in the "Viewing records for:" "totaradialogue"
    And I should see "org static" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user2"
    And I click on "Reports" in the totara menu
    When I follow "User report"
    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    Then I should see "no restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "limited user" in the "Viewing records for:" "totaradialogue"
    And I should see "limited uses" in the "Viewing records for:" "totaradialogue"
    And I should see "dynamic static" in the "Viewing records for:" "totaradialogue"
    And I should not see "org dynamic" in the "Viewing records for:" "totaradialogue"
    And I should see "org static" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user4"
    And I click on "Reports" in the totara menu
    When I follow "User report"
    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    Then I should see "no restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "limited user" in the "Viewing records for:" "totaradialogue"
    And I should not see "limited uses" in the "Viewing records for:" "totaradialogue"
    And I should see "dynamic static" in the "Viewing records for:" "totaradialogue"
    And I should see "org dynamic" in the "Viewing records for:" "totaradialogue"
    And I should see "org static" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

    When I log in as "user6"
    And I click on "Reports" in the totara menu
    When I follow "User report"
    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    Then I should see "no restriction" in the "Viewing records for:" "totaradialogue"
    And I should see "limited user" in the "Viewing records for:" "totaradialogue"
    And I should not see "limited uses" in the "Viewing records for:" "totaradialogue"
    And I should not see "dynamic static" in the "Viewing records for:" "totaradialogue"
    And I should not see "org dynamic" in the "Viewing records for:" "totaradialogue"
    And I should not see "org static" in the "Viewing records for:" "totaradialogue"
    And I click on "Cancel" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    And I log out

  Scenario: Test complex global report display users
    Given I log out
    When I log in as "user1"
    And I click on "Reports" in the totara menu
    When I follow "User report"

    Then I should see "change" in the ".globalrestrictionscontainer" "css_element"
    And I should see "Viewing records restricted by: no restriction." in the ".globalrestrictionscontainer" "css_element"
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should see "User Four" in the ".reportbuilder-table" "css_element"
    And I should see "User Five" in the ".reportbuilder-table" "css_element"
    And I should see "User Six" in the ".reportbuilder-table" "css_element"

    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I set the field "no restriction" to ""
    And I set the field "dynamic static" to "1"
    And I click on "Save" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Viewing records restricted by: dynamic static." in the ".globalrestrictionscontainer" "css_element"
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should see "User Four" in the ".reportbuilder-table" "css_element"
    And I should see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"

    When I click on "change" "link" in the ".globalrestrictionscontainer" "css_element"
    And I set the field "dynamic static" to ""
    And I set the field "org static" to "1"
    And I click on "Save" "button" in the "Viewing records for:" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Viewing records restricted by: org static." in the ".globalrestrictionscontainer" "css_element"
    And I should see "User One" in the ".reportbuilder-table" "css_element"
    And I should see "User Two" in the ".reportbuilder-table" "css_element"
    And I should see "User Three" in the ".reportbuilder-table" "css_element"
    And I should see "User Four" in the ".reportbuilder-table" "css_element"
    And I should see "User Five" in the ".reportbuilder-table" "css_element"
    And I should not see "User Six" in the ".reportbuilder-table" "css_element"
