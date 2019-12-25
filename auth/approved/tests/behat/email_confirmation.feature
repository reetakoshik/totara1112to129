@totara @auth @auth_approved @javascript
Feature: auth_approved: email confirmation
  Before I access courses in a Totara website
  As an external user
  I need confirm my email.

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


  # -------------------------------
  Scenario: auth_approval_email_confirm_0: successful confirmation
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname | signup time | status   | confirmed | token                            |
      | jb007    | spectre    | bond@example.gov | James      | Bond    | -3 days     | pending  | false     | this1has2to3be3thirtytwo4chars55 |
    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I log out
    And I confirm self-registration request from email "bond@example.gov"
    Then I should see "Thank you for confirming your account request, an email should have been sent to your address at bond@example.gov with information describing the account approval process."

    # Successful confirmation outcome #1: plugin table has updated record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"

    # Successful registration outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Self-registration with approval" row "Event name" column of "reportlog" table should contain "Account request email was confirmed"
    And "Self-registration with approval" row "Description" column of "reportlog" table should contain "jb007 (bond@example.gov) confirmed email address"

    # Successful confirmation outcome #2: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful registration outcome #3: email sent to applicant but cannot test with Behat.

    # Successful registration outcome #4: approver gets notification
    When I log in as "itmgr"
    And I open the notification popover
    Then I should see "New account request requires approval"

    When I follow "View full notification"
    Then I should see "has just confirmed their email address"
    Then I should see "jb007"
    Then I should see "bond@example.gov"


  # -------------------------------
  Scenario: auth_approval_email_confirm_1: trying to confirm an approved signup
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname | signup time | status   | confirmed | token                            |
      | jb007    | spectre    | bond@example.gov | James      | Bond    | -3 days     | approved | false     | this1has2to3be3thirtytwo4chars55 |
    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I confirm self-registration request from email "bond@example.gov"
    Then I should see "User account request was already approved"
    When I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then I should not see "Self-registration with approval"
    And I should not see "Account request email was confirmed"
    And I should not see "jb007 (bond@example.gov) confirmed email address"

    When I log out
    And I log in as "itmgr"
    And I open the notification popover
    Then I should not see "New account request requires approval"


  # -------------------------------
  Scenario: auth_approval_email_confirm_2: trying to confirm a rejected signup
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname | signup time | status   | confirmed | token                            |
      | jb007    | spectre    | bond@example.gov | James      | Bond    | -3 days     | rejected | false     | this1has2to3be3thirtytwo4chars55 |
    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I confirm self-registration request from email "bond@example.gov"
    Then I should see "User account request was already rejected"

    When I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then I should not see "Self-registration with approval"
    And I should not see "Account request email was confirmed"
    And I should not see "jb007 (bond@example.gov) confirmed email address"

    When I log out
    And I log in as "vp"
    And I open the notification popover
    Then I should not see "New account request requires approval"



