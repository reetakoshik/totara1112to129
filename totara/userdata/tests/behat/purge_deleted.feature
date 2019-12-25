@totara @totara_userdata @javascript
Feature: Deleted user data purging
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                    | deleted |
      | manager   | Paul      | Manager  | manager@example.com      | 0       |
      | username1 | Bob1      | Learner  | bob1.learner@example.com | 0       |
      | username2 | Bob2      | Learner  | bob2.learner@example.com | 0       |
      | username3 | Bob3      | Learner  | bob3.learner@example.com | 1       |
      | username4 | Bob4      | Learner  | bob4.learner@example.com | 0       |
      | username5 | Bob5      | Learner  | bob5.learner@example.com | 0       |
    And the following "roles" exist:
      | shortname   |
      | datamanager |
    And the following "role assigns" exist:
      | user    | role        | contextlevel | reference |
      | manager | datamanager | System       |           |
    And the following "permission overrides" exist:
      | capability                        | permission | role       | contextlevel | reference |
      | totara/userdata:config            | Allow      | datamanager| System       |           |
      | totara/userdata:viewpurges        | Allow      | datamanager| System       |           |
      | totara/userdata:purgesetdeleted   | Allow      | datamanager| System       |           |
      | totara/userdata:viewinfo          | Allow      | datamanager| System       |           |
      | totara/core:seedeletedusers       | Allow      | datamanager| System       |           |
      | moodle/user:update                | Allow      | datamanager| System       |           |
      | moodle/user:delete                | Allow      | datamanager| System       |           |

  Scenario: Automatic deleted user data purging
    Given I log in as "manager"
    And I navigate to "Purge types" node in "Site administration > User data management"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Deleted"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Minimal deleted user purging            |
      | idnumber      | ptid1                                   |
      | Available use | Automatic purging once user is deleted |
      | User          | core_user-picture,core_user-interests   |
    And I press "Add"

    And I press "Add purge type"
    And I set the "User status restriction" Totara form field to "Deleted"
    And I press "Continue"
    And I set the following Totara form fields to these values:
      | Full name     | Maximal deleted user purging            |
      | idnumber      | ptid2                                   |
      | Available use | Automatic purging once user is deleted |
      | User          | core_user-idnumber, core_user-email     |
    And I press "Add"

    When I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I click on "User data" "link" in the "Bob3 Learner" "table_row"
    And I should see "None" in the "All data purges" "definition_exact"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User data" "link" in the "Bob1 Learner" "table_row"
    And I click on "Edit" "link" in the "Automatic purging once user is deleted" "definition_exact"
    And I set the "Automatic purging once user is deleted" Totara form field to "Minimal deleted user purging"
    And I press "Update"
    And I should see "Minimal deleted user purging" in the "Purge type" "definition_exact"
    And I press "Save changes"
    And I should see "Minimal deleted user purging" in the "Automatic purging once user is deleted" "definition_exact"
    And I should see "None" in the "All data purges" "definition_exact"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete Bob1 Learner" "link" in the "Bob1 Learner" "table_row"
    And I press "Delete"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I click on "User data" "link" in the "Bob1 Learner" "table_row"
    And I should see "None" in the "All data purges" "definition_exact"
    And I run the scheduled task "totara_userdata\task\purge_deleted"
    Then I should see "1" in the "All data purges" "definition_exact"

    When I click on "1" "link" in the "All data purges" "definition_exact"
    And I should see "Success" in the "Minimal deleted user purging" "table_row"
    And I follow "Bob1 Learner"
    And I should see "purged" in the "Automatic purging once user is deleted" "definition_exact"
    And I click on "Edit" "link" in the "Automatic purging once user is deleted" "definition_exact"
    And I set the "Automatic purging once user is deleted" Totara form field to "Maximal deleted user purging"
    And I press "Update"
    And I should see "Maximal deleted user purging" in the "Purge type" "definition_exact"
    And I press "Save changes"
    And I should see "pending" in the "Automatic purging once user is deleted" "definition_exact"
    And I run the scheduled task "totara_userdata\task\purge_deleted"
    Then I should see "2" in the "All data purges" "definition_exact"
    And I should see "purged" in the "Automatic purging once user is deleted" "definition_exact"
    And I click on "2" "link" in the "All data purges" "definition_exact"
    And I should see "Success" in the "Maximal deleted user purging" "table_row"

    When I navigate to "Purge types" node in "Site administration > User data management"
    And I click on "Edit" "link" in the "Maximal deleted user purging" "table_row"
    And I set the "Reapply purging" Totara form field to "1"
    And I should see "This purge type will be reapplied to 1 users."
    And I press "Update"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I click on "User data" "link" in the "Bob1 Learner" "table_row"
    And I should see "pending" in the "Automatic purging once user is deleted" "definition_exact"
    And I run the scheduled task "totara_userdata\task\purge_deleted"
    Then I should see "3" in the "All data purges" "definition_exact"
    And I should see "purged" in the "Automatic purging once user is deleted" "definition_exact"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I click on "User data" "link" in the "Bob3 Learner" "table_row"
    And I should see "None" in the "All data purges" "definition_exact"
    And I should see "None" in the "Automatic purging once user is deleted" "definition_exact"

    When I click on "Edit" "link" in the "Automatic purging once user is deleted" "definition_exact"
    And I set the "Automatic purging once user is deleted" Totara form field to "None"
    And I press "Update"
    Then I should see "None" in the "Purge type" "definition_exact"
    And I should see "No additional data will be deleted."
    When I press "Save changes"
    Then I should see "None" in the "Automatic purging once user is deleted" "definition_exact"

    When I navigate to "Settings" node in "Site administration > User data management"
    And I set the field "Default purging type for deleted users" to "Minimal deleted user purging"
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete Bob2 Learner" "link" in the "Bob2 Learner" "table_row"
    And I press "Delete"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I click on "User data" "link" in the "Bob2 Learner" "table_row"
    And I should see "pending" in the "Automatic purging once user is deleted" "definition_exact"
    And I should see "Minimal deleted user purging" in the "Automatic purging once user is deleted" "definition_exact"
    And I run the scheduled task "totara_userdata\task\purge_deleted"
    Then I should see "1" in the "All data purges" "definition_exact"
    And I should see "purged" in the "Automatic purging once user is deleted" "definition_exact"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User data" "link" in the "Paul Manager" "table_row"
    Then I should see "None (Site default: Minimal deleted user purging)" in the "Automatic purging once user is deleted" "definition_exact"
