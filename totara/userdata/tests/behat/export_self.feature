@totara @totara_userdata @javascript
Feature: Own user data exporting
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                    |
      | manager   | Paul      | Manager  | manager@example.com      |
      | username1 | Bob1      | Learner  | bob1.learner@example.com |
      | username2 | Bob2      | Learner  | bob2.learner@example.com |
    And the following "roles" exist:
      | shortname   |
      | datamanager |
      | exporter    |
    And the following "role assigns" exist:
      | user      | role        | contextlevel | reference |
      | manager   | datamanager | System       |           |
      | username1 | exporter    | System       |           |
    And the following "permission overrides" exist:
      | capability                  | permission | role        | contextlevel | reference |
      | totara/userdata:config      | Allow      | datamanager | System       |           |
      | totara/userdata:viewexports | Allow      | datamanager | System       |           |
      | totara/userdata:viewinfo    | Allow      | datamanager | System       |           |
      | moodle/user:update          | Allow      | datamanager | System       |           |
      | totara/userdata:exportself  | Allow      | exporter    | System       |           |

  Scenario: Export own user data
    Given I log in as "manager"
    And I navigate to "Export types" node in "Site administration > User data management"

    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | Additional names export   |
      | idnumber      | etid1                     |
      | Permitted use | User exporting own data   |
      | User          | core_user-additionalnames |
    And I press "Add"
    And I press "Add export type"
    And I set the following Totara form fields to these values:
      | Full name     | Picture exporting        |
      | idnumber      | etid2                    |
      | Permitted use | User exporting own data  |
      | User          | core_user-picture        |
    And I press "Add"
    And I navigate to "Settings" node in "Site administration > User data management"
    And I set the field "Allow users to export their own data" to "1"
    And I press "Save changes"
    And I log out

    When I log in as "username1"
    And I follow "Profile" in the user menu
    And I follow "Request data export"
    And I set the "Export type" Totara form field to "Additional names export"
    And I press "Request data export"
    And I should see "Data export in progress. You will receive a notification once the file is available for download."
    And I run the adhoc scheduled tasks "totara_userdata\task\export"
    Then I should see "Your data export file is available for download:"
    And I follow "export.tgz"
    And I should see "behat export file access success"

    When I am on site homepage
    And I follow "Profile" in the user menu
    And I follow "Request data export"
    And I click on "Delete" "link"
    And I should see "Export file was deleted"
    Then I should see "Export type"
    And I set the "Export type" Totara form field to "Picture exporting"
    And I press "Request data export"
    And I should see "Data export in progress. You will receive a notification once the file is available for download."
    And I run the adhoc scheduled tasks "totara_userdata\task\export"
    Then I should see "Your data export file is available for download:"
    And I log out

    When I log in as "username2"
    And I follow "Profile" in the user menu
    Then I should not see "Request data export"
    And I log out

    When I log in as "manager"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User data" "link" in the "Bob1 Learner" "table_row"
    Then I should see "2" in the "Data export requests" "definition_exact"

    When I navigate to "Export types" node in "Site administration > User data management"
    And I click on "Edit" "link" in the "Picture exporting" "table_row"
    And I set the following Totara form fields to these values:
      | Permitted use |  |
    And I press "Update"
    And I log out
    And I log in as "username1"
    And I follow "Profile" in the user menu
    And I follow "Request data export"
    Then I should not see "Your data export file is available for download:"
