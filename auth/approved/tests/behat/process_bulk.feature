@totara @auth @auth_approved @javascript
Feature: auth_approved: bulk signup ops
  As an approver
  I can do bulk operations on signups

  Background:
    Given I am on a totara site
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber |
      | Organisation Root 1| OFW001   |
      | Organisation Root 2| OFW002   |
      | Organisation Root 3| OFW003   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname         | shortname | idnumber |
      | OFW001        | Information Tech | IT        | ORG011   |
      | OFW001        | Finance          | Fin       | ORG012   |
      | OFW002        | Deliveries       | Del       | ORG021   |
      | OFW002        | Sales            | Sale      | ORG022   |
      | OFW003        | Top              | Brass     | ORG031   |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Position Root 1 | PFW001   |
      | Position Root 2 | PFW002   |
      | Position Root 3 | PFW003   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname             | shortname  | idnumber |
      | PFW001        | IT dept secretary    | IT Sec     | POS011   |
      | PFW001        | IT Developer         | IT Dev     | POS012   |
      | PFW001        | IT dept manager      | IT Mgr     | POS013   |
      | PFW001        | Fin dept manager     | Fin Mgr    | POS014   |
      | PFW002        | Sales dept secretary | Sales Sec  | POS021   |
      | PFW002        | Sales Engr           | Sales Engr | POS022   |
      | PFW002        | Sales dept manager   | Sales Mgr  | POS023   |
      | PFW003        | CxO                  | Cx0s       | POS031   |
    And the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | itsec     | Sec       | IT        | itsec@example.com     |
      | itdev     | Dev       | IT        | itdev@example.com     |
      | itmgr     | Manager   | IT        | manager1@example.com  |
      | finmgr    | Manager   | Fin       | manager2@example.com  |
      | salessec  | Sec       | Sales     | salessec@example.com  |
      | salesengr | Engr      | Sales     | salesengr@example.com |
      | salesmgr  | Manager   | Sales     | salesmgr@example.com  |
      | vp        | Vice      | President | vp@example.com        |
    And the following job assignments exist:
      | user      | fullname     | shortname    | manager  | position | organisation | idnumber |
      | itsec     | itsec ja     | itsec ja     | itmgr    | POS011   | ORG011       | JA0000   |
      | itdev     | itdev ja     | itdev ja     | itmgr    | POS012   | ORG011       | JA0001   |
      | itmgr     | itmgr ja     | itmgr ja     | vp       | POS013   | ORG011       | JA0002   |
      | finmgr    | finmgr ja    | finmgr ja    | vp       | POS014   | ORG012       | JA0003   |
      | salessec  | salessec ja  | salessec ja  | salesmgr | POS021   | ORG022       | JA0004   |
      | salesengr | salesengr ja | salesengr ja | salesmgr | POS022   | ORG022       | JA0005   |
      | salesmgr  | salesmgr ja  | salesmgr ja  | vp       | POS023   | ORG022       | JA0006   |
      | vp        | vp ja        | vp ja        |          | POS031   | ORG031       | JA0007   |
    And the following "roles" exist:
      | name             | shortname        | contextlevel |
      | AuthApprovalRole | AuthApprovalRole | System       |
    And the following "permission overrides" exist:
      | capability                          | permission | role             | contextlevel | reference |
      | auth/approved:approve               | Allow      | AuthApprovalRole | System       |           |
      | totara/hierarchy:assignuserposition | Allow      | AuthApprovalRole | System       |           |
    And the following "role assigns" exist:
      | user     | role             | contextlevel | reference |
      | itmgr    | AuthApprovalRole | System       |           |
      | finmgr   | AuthApprovalRole | System       |           |
      | salesmgr | AuthApprovalRole | System       |           |
      | vp       | AuthApprovalRole | System       |           |
    And the following config values are set as admin:
      | passwordpolicy  | 0     |
    And I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org fw       | OFW001, OFW002                          |
      | org freeform | true                                    |
      | pos fw       | PFW001, PFW002                          |
      | pos freeform | true                                    |
      | mgr org fw   | OFW002                                  |
      | mgr pos fw   | PFW003                                  |
      | mgr freeform | true                                    |


  # -------------------------------
  Scenario: auth_approval_process_bulk_0: bulk rejects
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 |
    And I log in as "itmgr"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Reject"
    And I press "Execute"
    Then I should see "Are you sure you want to bulk reject 2 requests?"

    When I set the field "Custom message for user" to "Sorry, mate"
    And I press "Reject"
    Then I should see "There are no records in this report"

    When I log out
    And I set the following fields to these values:
      | Username   | eve |
      | Password   | eve |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    When I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    When I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "jb007 (bond@example.gov) rejected for system access" row "Event name" column of "reportlog" table should contain "Account request was rejected"
    And "eve (mp@example.gov) rejected for system access" row "Event name" column of "reportlog" table should contain "Account request was rejected"


  # -------------------------------
  Scenario: auth_approval_process_bulk_1a: successful bulk approvals
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            | manager jaidnum | pos idnum | org idnum |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 | JA0006          | POS022    | ORG022    |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 | JA0006          | POS021    | ORG022    |
    And I log in as "itmgr"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Approve"
    And I press "Execute"
    Then I should see "Are you sure you want to bulk approve 2 requests?"

    When I set the field "Custom message for user" to "You're in"
    And I press "Approve"
    Then I should see "There are no records in this report"
    And I should see "Bulk approved 2 requests"

    When I log out
    And I set the following fields to these values:
      | Username   | eve |
      | Password   | eve |
    And I click on "Log in" "button"
    Then I should see "Eve Moneypenny"
    And I should see "Current Learning"

    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "James Bond"
    And I should see "Current Learning"

    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "jb007 (bond@example.gov) approved for system access" row "Event name" column of "reportlog" table should contain "Account request was approved"
    And "eve (mp@example.gov) approved for system access" row "Event name" column of "reportlog" table should contain "Account request was approved"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "James Bond"
    And I follow "Unnamed job assignment (ID: 1)"
    Then I should see "Sales Engr"
    And I should see "Manager Sales (salesmgr@example.com) - salesmgr ja"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Eve Moneypenny"
    And I follow "Unnamed job assignment"
    Then I should see "Sales dept secretary"
    And I should see "Manager Sales (salesmgr@example.com) - salesmgr ja"


  # -------------------------------
  Scenario: auth_approval_process_bulk_1b: unsuccessful bulk approvals
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            | manager jaidnum | pos idnum | org idnum |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 | JA0006          | POS022    | ORG022    |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 |                 | POS021    | ORG022    |
    And I log in as "itmgr"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Approve"
    And I press "Execute"
    Then I should see "Are you sure you want to bulk approve 2 requests?"

    When I set the field "Custom message for user" to "You're in"
    And I press "Approve"
    Then I should see "Bulk approved 1 requests"
    And I should see "Error bulk approving 1 requests"
    And I should not see "jb007"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I log out
    And I set the following fields to these values:
      | Username   | eve |
      | Password   | eve |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    When I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "James Bond"
    And I should see "Current Learning"

    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "jb007 (bond@example.gov) approved for system access" row "Event name" column of "reportlog" table should contain "Account request was approved"
    And I should not see "mp@example.gov"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "James Bond"
    And I follow "Unnamed job assignment"
    Then I should see "Sales Engr"
    And I should see "Manager Sales (salesmgr@example.com) - salesmgr ja"


  # -------------------------------
  Scenario: auth_approval_process_bulk_1c: successful bulk approvals, no hierarchy needed
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 |
    And I log in as "vp"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Approve"
    And I press "Execute"
    Then I should see "Are you sure you want to bulk approve 2 requests?"

    When I set the field "Custom message for user" to "You're in"
    And I press "Approve"
    Then I should see "There are no records in this report"
    And I should see "Bulk approved 2 requests"

    When I log out
    And I set the following fields to these values:
      | Username   | eve |
      | Password   | eve |
    And I click on "Log in" "button"
    Then I should see "Eve Moneypenny"
    And I should see "Current Learning"

    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "James Bond"
    And I should see "Current Learning"

    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "jb007 (bond@example.gov) approved for system access" row "Event name" column of "reportlog" table should contain "Account request was approved"
    And "eve (mp@example.gov) approved for system access" row "Event name" column of "reportlog" table should contain "Account request was approved"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "James Bond"
    Then I should see "This user has no job assignments"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Eve Moneypenny"
    Then I should see "This user has no job assignments"


  # -------------------------------
  Scenario: auth_approval_process_bulk_2: bulk messaging
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            | manager jaidnum | pos idnum | org idnum |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 | JA0006          | POS022    | ORG022    |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 |                 | POS021    | ORG022    |
    And I log in as "itmgr"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Send message"
    And I press "Execute"
    Then I should see "Are you sure you want to bulk send messages to 2 requests?"

    When I set the field "Message subject" to "Hi There"
    And I set the field "Message body" to "You're sure you want in?"
    And I press "Send message"
    Then I should see "Bulk sent messages to 2 requests"
    And "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I log out
    And I set the following fields to these values:
      | Username   | eve |
      | Password   | eve |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    When I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"


  # -------------------------------
  Scenario: auth_approval_process_bulk_3: bulk changing hierarchies
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname    | signup time | status   | confirmed | token                            | manager jaidnum | pos idnum | org idnum |
      | jb007    | spectre    | bond@example.gov | James      | Bond       | -3 days     | pending  | true      | this1has2to3be3thirtytwo4chars00 | JA0006          | POS022    | ORG022    |
      | eve      | eve        | mp@example.gov   | Eve        | Moneypenny | -2 days     | pending  | false     | this1has2to3be3thirtytwo4chars01 |                 | POS021    | ORG022    |
    And I log in as "itmgr"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"
    And "eve" row "User First Name" column of "auth_approved_pending_requests" table should contain "Eve"
    And "eve" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Moneypenny"
    And "eve" row "User's Email" column of "auth_approved_pending_requests" table should contain "mp@example.gov"
    And "eve" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I set the field "Bulk change 2 requests" to "Set position"
    And I press "Execute"
    And I click on "Choose position" "button"
    And I click on "IT dept secretary" "link" in the "position" "totaradialogue"
    And I click on "OK" "button" in the "position" "totaradialogue"
    And I press "Set position"
    Then I should see "Bulk set positions for 2 requests"

    When I set the field "Bulk change 2 requests" to "Set organisation"
    And I press "Execute"
    And I click on "Choose organisation" "button"
    And I click on "Information Tech" "link" in the "organisation" "totaradialogue"
    And I click on "OK" "button" in the "organisation" "totaradialogue"
    And I press "Set organisation"
    Then I should see "Bulk set organisations for 2 requests"

    When I set the field "Bulk change 2 requests" to "Set manager"
    And I press "Execute"
    And I click on "Choose manager" "button"
    And I click on "Vice President" "link" in the "Choose manager" "totaradialogue"
    And I click on "vp ja" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "manager" "totaradialogue"
    And I press "Set manager"
    Then I should see "Bulk set managers for 2 requests"

    When I click on "Edit" "link" in the "jb007" "table_row"
    Then the field "Username" matches value "jb007"
    And the field "Email address" matches value "bond@example.gov"
    And the field "First name" matches value "James"
    And the field "Surname" matches value "Bond"
    And I should see "Information Tech"
    And I should see "IT dept secretary"
    And I should see "Vice President - vp ja"

    When I press "Cancel"
    And I click on "Edit" "link" in the "eve" "table_row"
    Then the field "Username" matches value "eve"
    And the field "Email address" matches value "mp@example.gov"
    And the field "First name" matches value "Eve"
    And the field "Surname" matches value "Moneypenny"
    And I should see "Information Tech"
    And I should see "IT dept secretary"
    And I should see "Vice President - vp ja"


