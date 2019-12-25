@totara @totara_userdata @javascript
Feature: Manual user data purging
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                    | deleted | suspended |
      | manager   | Paul      | Manager  | manager@example.com      | 0       | 0         |
      | username1 | Bob1      | Learner  | bob1.learner@example.com | 0       | 0         |
      | username2 | Bob2      | Learner  | bob2.learner@example.com | 0       | 0         |
      | username3 | Bob3      | Learner  | bob3.learner@example.com | 0       | 1         |
      | username4 | Bob4      | Learner  | bob4.learner@example.com | 1       | 0         |
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
      | totara/userdata:purgemanual | Allow      | datamanager| System       |           |
      | totara/userdata:viewinfo    | Allow      | datamanager| System       |           |
      | totara/core:seedeletedusers | Allow      | datamanager| System       |           |
      | moodle/user:update          | Allow      | datamanager| System       |           |

  Scenario: Purge user data manually
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Additional names purging  |
      | idnumber      | ptid1                     |
      | Available use | Manual data purging       |
      | User          | core_user-additionalnames |
    And I press "Add"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Active"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Picture purging          |
      | idnumber      | ptid2                    |
      | Available use | Manual data purging      |
      | User          | core_user-picture        |
    And I press "Add"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Suspended"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Suspended user purging                                        |
      | idnumber      | ptid3                                                         |
      | Available use | Manual data purging,Automatic purging once user is suspended  |
      | User          | core_user-picture,core_user-picture                           |
    And I press "Add"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Deleted"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Deleted user purging                                        |
      | idnumber      | ptid4                                                       |
      | Available use | Manual data purging,Automatic purging once user is deleted  |
      | User          | core_user-username,core_user-email                          |
    And I press "Add"

    When I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I should see "bob4.learner@example.com"
    And I click on "User data" "link" in the "Bob4 Learner" "table_row"
    And I press "Select purge type"
    And I set the "Purge type" Totara form field to "Deleted user purging"
    And I press "Purge user data"
    And I should see "Are you sure you would like to delete this data?"
    And I press "Proceed with purge"
    And I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "1" in the "Pending purges" "definition_exact"
    And I run the adhoc scheduled tasks "totara_userdata\task\purge_manual"
    Then I should see "Bob4 Learner" in the "User full name" "definition_exact"
    And I should see "deleted_" in the "Username" "definition_exact"
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"
    And I should not see "bob4.learner@example.com"
    And I click on "1" "link" in the "All data purges" "definition_exact"
    And I should see "Success" in the "Deleted user purging" "table_row"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User data" "link" in the "Bob1 Learner" "table_row"
    And I press "Select purge type"
    And I set the "Purge type" Totara form field to "Additional names purging"
    And I press "Purge user data"
    And I should see "Are you sure you would like to delete this data?"
    And I press "Proceed with purge"
    And I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "1" in the "Pending purges" "definition_exact"
    And I press "Select purge type"
    And I set the "Purge type" Totara form field to "Additional names purging"
    And I press "Purge user data"
    And I should see "This data purge is already scheduled for execution"
    And I set the "Purge type" Totara form field to "Picture purging"
    And I press "Purge user data"
    And I should see "Are you sure you would like to delete this data?"
    And I press "Proceed with purge"
    And I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "2" in the "All data purges" "definition_exact"
    And I should see "2" in the "Pending purges" "definition_exact"
    And I run the adhoc scheduled tasks "totara_userdata\task\purge_manual"
    And I should see "2" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"
    And I click on "2" "link" in the "All data purges" "definition_exact"
    And I should see "Success" in the "Additional names purging" "table_row"
    And I should see "Success" in the "Picture purging" "table_row"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the following fields to these values:
    | User Status | any value |
    And I press "id_submitgroupstandard_addfilter"
    And I click on "User data" "link" in the "Bob3 Learner" "table_row"
    And I press "Select purge type"
    And I set the "Purge type" Totara form field to "Suspended user purging"
    And I press "Purge user data"
    And I should see "Are you sure you would like to delete this data?"
    And I press "Proceed with purge"
    And I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "1" in the "Pending purges" "definition_exact"
    And I click on "1" "link" in the "All data purges" "definition_exact"
    And I click on "Cancel" "link" in the "Suspended user purging" "table_row"
    And I should see "Purge was cancelled"
    And I should see "Cancelled" in the "Suspended user purging" "table_row"
    And I follow "Bob3 Learner"
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"
    And I press "Select purge type"
    And I set the "Purge type" Totara form field to "Suspended user purging"
    And I press "Purge user data"
    And I should see "Are you sure you would like to delete this data?"
    And I press "Proceed with purge"
    And I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "2" in the "All data purges" "definition_exact"
    And I should see "1" in the "Pending purges" "definition_exact"
    And I run the adhoc scheduled tasks "totara_userdata\task\purge_manual"
    Then I should see "Bob3 Learner" in the "User full name" "definition_exact"
    And I should see "2" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"
    And I click on "2" "link" in the "All data purges" "definition_exact"
    And I should see "Suspended user purging" in the "Success" "table_row"
    And I should see "Suspended user purging" in the "Cancelled" "table_row"



