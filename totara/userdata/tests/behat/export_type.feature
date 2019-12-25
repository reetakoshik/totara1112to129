@totara @totara_userdata @javascript
Feature: Manage user data export types
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                    |
      | manager   | Paul      | Manager  | manager@example.com      |
      | username1 | Bob1      | Learner  | bob1.learner@example.com |
      | username2 | Bob2      | Learner  | bob2.learner@example.com |
      | username3 | Bob3      | Learner  | bob3.learner@example.com |
      | username4 | Bob4      | Learner  | bob4.learner@example.com |
    And the following "roles" exist:
      | shortname   |
      | datamanager |
    And the following "role assigns" exist:
      | user    | role        | contextlevel | reference |
      | manager | datamanager | System       |           |
    And the following "permission overrides" exist:
      | capability                  | permission | role       | contextlevel | reference |
      | totara/userdata:config      | Allow      | datamanager| System       |           |
      | totara/userdata:viewexports | Allow      | datamanager| System       |           |

  Scenario: Create user data export type
    Given I log in as "manager"
    And I navigate to "Export types" node in "Site administration > User data management"
    When I press "Add export type"
    And I set the following Totara form fields to these values:
    | Full name     | First export type       |
    | idnumber      | et1id                   |
    | Description   | Some first description  |
    | Include files | 1                       |
    | Permitted use | User exporting own data |
    | User          | Username,Email          |
    And I press "Add"
    And I follow "First export type"
    Then I should see "First export type" in the "Full name" "definition_exact"
    And I should see "et1id" in the "ID number" "definition_exact"
    And I should see "Some first description" in the "Description" "definition_exact"
    And I should see "User exporting own data" in the "Permitted use" "definition_exact"
    And I should see "Yes" in the "Include files" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of exports" "definition_exact"
    And I should see "Username"
    And I should see "Email"

    # Test duplicate id detection.
    When I navigate to "Export types" node in "Site administration > User data management"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | First export type       |
      | idnumber      | Et1id                   |
      | Description   | Some first description  |
      | Include files | 1                       |
      | Permitted use | User exporting own data |
      | User          | Username,Email          |
    And I press "Add"
    Then I should see "Same ID number already exists"
    And I set the following Totara form fields to these values:
      | idnumber      | xt1id                   |
    And I press "Add"
    And I click on "First export type" "link" in the "xt1id" "table_row"
    Then I should see "First export type" in the "Full name" "definition_exact"
    And I should see "xt1id" in the "ID number" "definition_exact"
    And I should see "Some first description" in the "Description" "definition_exact"
    And I should see "User exporting own data" in the "Permitted use" "definition_exact"
    And I should see "Yes" in the "Include files" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of exports" "definition_exact"
    And I should see "Username"
    And I should see "Email"

  Scenario: Update user data export type
    Given I log in as "manager"
    And I navigate to "Export types" node in "Site administration > User data management"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | First export type                  |
      | idnumber      | et1id                              |
      | Description   | Some first description             |
      | Include files | 1                                  |
      | Permitted use | User exporting own data            |
      | User          | core_user-username,core_user-email |
    And I press "Add"
    When I click on "Edit" "link" in the "First export type" "table_row"
    And I should see the following Totara form fields having these values:
      | Full name     | First export type                  |
      | idnumber      | et1id                              |
      | Description   | Some first description             |
      | Include files | 1                                  |
      | Permitted use | User exporting own data            |
      | User          | core_user-username,core_user-email |
    And I set the following Totara form fields to these values:
      | Full name     | Prvni export                       |
      | idnumber      | et1id                              |
      | Description   | Some prvni description             |
      | Include files | 0                                  |
      | Permitted use |                                    |
      | User          | core_user-interests                |
    And I press "Update"
    And I follow "Prvni export"
    Then I should see "Prvni export" in the "Full name" "definition_exact"
    And I should see "et1id" in the "ID number" "definition_exact"
    And I should see "Some prvni description" in the "Description" "definition_exact"
    And I should see "No" in the "Include files" "definition_exact"
    And I should see "0" in the "Number of exports" "definition_exact"
    And I should not see "User exporting own data"
    And I should not see "Username"
    And I should not see "Email"
    And I should see "Interests"

  Scenario: Duplicate user data export type
    Given I log in as "manager"
    And I navigate to "Export types" node in "Site administration > User data management"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | First export type       |
      | idnumber      | et1id                   |
      | Description   | Some first description  |
      | Include files | 1                       |
      | Permitted use | User exporting own data |
      | User          | Username,Email          |
    And I press "Add"
    When I click on "Duplicate" "link" in the "First export type" "table_row"
    And I should see the following Totara form fields having these values:
      | Full name     | Copy of First export type          |
      | idnumber      |                                    |
      | Description   | Some first description             |
      | Include files | 1                                  |
      | Permitted use | User exporting own data            |
      | User          | core_user-username,core_user-email |
    And I set the following Totara form fields to these values:
      | Full name     | Prvni export                       |
      | idnumber      | et1idx                             |
    And I press "Add"
    And I follow "Prvni export"
    Then I should see "Prvni export" in the "Full name" "definition_exact"
    And I should see "et1idx" in the "ID number" "definition_exact"
    And I should see "Some first description" in the "Description" "definition_exact"
    And I should see "User exporting own data" in the "Permitted use" "definition_exact"
    And I should see "Yes" in the "Include files" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of exports" "definition_exact"
    And I should see "Username"
    And I should see "Email"

  Scenario: Delete user data export type
    Given I log in as "manager"
    And I navigate to "Export types" node in "Site administration > User data management"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | First export type       |
      | idnumber      | et1id                   |
      | Description   | Some first description  |
      | Include files | 1                       |
      | Permitted use | User exporting own data |
      | User          | Username,Email          |
    And I press "Add"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | Second export type      |
      | idnumber      | et2id                   |
      | Description   | Some Second description |
      | Include files | 1                       |
      | Permitted use | User exporting own data |
      | User          | Username,Email          |
    And I press "Add"
    When I click on "Delete" "link" in the "First export type" "table_row"
    And I should see "Are you sure you want to delete export type"
    And I press "Delete"
    Then I should not see "First export type"
    And I should see "Second export type"
