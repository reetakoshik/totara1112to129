@totara @totara_userdata @javascript
Feature: Manage user data purge types
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
      | totara/userdata:viewpurges  | Allow      | datamanager| System       |           |

  Scenario: Create user data purge type
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"

    When I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
    | Full name     | First purge type         |
    | idnumber      | pt1id                    |
    | Description   | Some first description   |
    | Available use | Manual data purging      |
    | User          | Preferences,User picture |
    And I press "Add"
    And I follow "First purge type"
    Then I should see "First purge type" in the "Full name" "definition_exact"
    And I should see "pt1id" in the "ID number" "definition_exact"
    And I should see "Active" in the "User status restriction" "definition_exact"
    And I should see "Some first description" in the "Description" "definition_exact"
    And I should see "Manual data purging" in the "Available use" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of purges" "definition_exact"
    And I should see "User picture"
    And I should see "Preferences"

    And I navigate to "Purge types" node in "Site administration > User data management"
    When I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Suspended"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Second purge type                                            |
      | idnumber      | pt2id                                                        |
      | Description   | Some second description                                      |
      | Available use | Manual data purging,Automatic purging once user is suspended |
      | User          | Preferences,User picture                                     |
    And I press "Add"
    And I follow "Second purge type"
    Then I should see "Second purge type" in the "Full name" "definition_exact"
    And I should see "pt2id" in the "ID number" "definition_exact"
    And I should see "Suspended" in the "User status restriction" "definition_exact"
    And I should see "Some second description" in the "Description" "definition_exact"
    And I should see "Manual data purging, Automatic purging once user is suspended" in the "Available use" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of purges" "definition_exact"
    And I should see "User picture"
    And I should see "Preferences"

    And I navigate to "Purge types" node in "Site administration > User data management"
    When I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Deleted"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Third purge type                                           |
      | idnumber      | pt2id                                                      |
      | Description   | Some third description                                     |
      | Available use | Manual data purging,Automatic purging once user is deleted |
      | User          | Username,Email                                             |
    And I press "Add"
    Then I should see "Same ID number already exists"
    And I set the following Totara form fields to these values:
      | idnumber      | pt3id                   |
    And I press "Add"
    And I follow "Third purge type"
    Then I should see "Third purge type" in the "Full name" "definition_exact"
    And I should see "pt3id" in the "ID number" "definition_exact"
    And I should see "Deleted" in the "User status restriction" "definition_exact"
    And I should see "Some third description" in the "Description" "definition_exact"
    And I should see "Manual data purging, Automatic purging once user is deleted" in the "Available use" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of purges" "definition_exact"
    And I should see "Username"
    And I should see "Email"

  Scenario: Update user data purge type
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"
    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | First purge type         |
      | idnumber      | pt1id                    |
      | Description   | Some first description   |
      | Available use | Manual data purging      |
      | User          | Preferences,User picture |
    And I press "Add"
    When I click on "Edit" "link" in the "First purge type" "table_row"
    And I should see the following Totara form fields having these values:
      | Full name     | First purge type         |
      | idnumber      | pt1id                    |
      | Description   | Some first description   |
      | Available use | Manual data purging      |
      | User          | Preferences,User picture |
    And I set the following Totara form fields to these values:
      | Full name     | Prvni purge                        |
      | idnumber      | xt1id                              |
      | Description   | Some prvni description             |
      | Available use |                                    |
      | User          | Interests                          |
    And I press "Update"
    And I follow "Prvni purge"
    Then I should see "Prvni purge" in the "Full name" "definition_exact"
    And I should see "xt1id" in the "ID number" "definition_exact"
    And I should see "Active" in the "User status restriction" "definition_exact"
    And I should see "Some prvni description" in the "Description" "definition_exact"
    And I should see "0" in the "Number of purges" "definition_exact"
    And I should not see "Manual data purging"
    And I should not see "Preferences"
    And I should not see "User picture"
    And I should see "Interests"

  Scenario: Duplicate user data purge type
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"
    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | First purge type         |
      | idnumber      | pt1id                    |
      | Description   | Some first description   |
      | Available use | Manual data purging      |
      | User          | Preferences,User picture |
    And I press "Add"
    When I click on "Duplicate" "link" in the "First purge type" "table_row"
    And I should see the following Totara form fields having these values:
      | Full name     | Copy of First purge type           |
      | idnumber      |                                    |
      | Description   | Some first description             |
      | Available use | Manual data purging                |
      | User          | Preferences,User picture           |
    And I set the following Totara form fields to these values:
      | Full name     | Prvni purge                        |
      | idnumber      | et1idx                             |
    And I press "Add"
    And I follow "Prvni purge"
    Then I should see "Prvni purge" in the "Full name" "definition_exact"
    And I should see "et1idx" in the "ID number" "definition_exact"
    And I should see "Active" in the "User status restriction" "definition_exact"
    And I should see "Some first description" in the "Description" "definition_exact"
    And I should see "Manual data purging" in the "Available use" "definition_exact"
    And I should see "Paul Manager" in the "Created by" "definition_exact"
    And I should see "0" in the "Number of purges" "definition_exact"
    And I should see "Preferences"
    And I should see "User picture"

  Scenario: Delete user data purge type
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"
    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | First purge type         |
      | idnumber      | pt1id                    |
      | Description   | Some first description   |
      | Available use | Manual data purging      |
      | User          | Preferences,User picture |
    And I press "Add"
    When I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Suspended"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Second purge type                                            |
      | idnumber      | pt2id                                                        |
      | Description   | Some second description                                      |
      | Available use | Manual data purging,Automatic purging once user is suspended |
      | User          | Preferences,User picture                                     |
    And I press "Add"
    When I click on "Delete" "link" in the "First purge type" "table_row"
    And I should see "Are you sure you want to delete purge type"
    And I press "Delete"
    Then I should not see "First purge type"
    And I should see "Second purge type"
