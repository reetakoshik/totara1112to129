@totara @auth @auth_approved @javascript
Feature: auth_approved: signup workflow
  In order to access courses in a Totara website
  As an external user
  I need to be able to successfully sign up for site access.

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
  Scenario: auth_approval_signup_0: successful signup with no hierarchy
    Given I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I log in as "itmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username   | jb007            |
      | Password   | spectre          |
      | Email      | bond@example.gov |
      | First name | James            |
      | Surname    | Bond             |
      | City       | London           |
      | Country    | United Kingdom   |
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.gov"

    # Successful signup outcome #1: plugin table has record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    # Successful signup outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Self-registration with approval" row "Event name" column of "reportlog" table should contain "User added new account request"
    And "Self-registration with approval" row "Description" column of "reportlog" table should contain "jb007 (bond@example.gov) registered for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: email sent to applicant but cannot test with Behat.

    # Successful signup outcome #5: approver gets notification
    When I log in as "itmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"
    When I follow "View full notification"
    Then I should see "they were asked to confirm their email address"
    Then I should see "jb007"
    Then I should see "bond@example.gov"


  # -------------------------------
  Scenario: auth_approval_signup_1: Successful signup with only freeform hierarchy entered
    Given I set these auth approval plugin settings:
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
    And I log in as "finmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007              |
      | Password               | spectre            |
      | Email                  | bond@example.gov   |
      | First name             | James              |
      | Surname                | Bond               |
      | City                   | London             |
      | Country                | United Kingdom     |
      | Manager free text      | M (Judi Dench)     |
      | Organisation free text | Secret Service MI5 |
      | Position free text     | Spy (First class)  |
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.gov"

    # Successful signup outcome #1: plugin table has record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I click on "Edit" "link" in the "jb007" "table_row"
    Then the field "Username" matches value "jb007"
    And the field "Email address" matches value "bond@example.gov"
    And the field "First name" matches value "James"
    And the field "Surname" matches value "Bond"
    And the field "City/town" matches value "London"
    And the field "Country" matches value "United Kingdom"
    And the field "Organisation free text" matches value "Secret Service MI5"
    And the field "Position free text" matches value "Spy (First class)"
    And the field "Manager free text" matches value "M (Judi Dench)"
    And ".mform [value='Choose organisation']" "css_element" should exist
    And ".mform [value='Choose position']" "css_element" should exist
    And ".mform [value='Choose manager']" "css_element" should exist

    # Successful signup outcome #2: audit trail created.
    When I press "Cancel"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Self-registration with approval" row "Event name" column of "reportlog" table should contain "User added new account request"
    And "Self-registration with approval" row "Description" column of "reportlog" table should contain "jb007 (bond@example.gov) registered for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: email sent to applicant but cannot test with Behat.

    # Successful signup outcome #5: approver gets notification
    When I log in as "finmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"

    When I follow "View full notification"
    Then I should see "they were asked to confirm their email address"
    Then I should see "jb007"
    Then I should see "bond@example.gov"


  # -------------------------------
  Scenario: auth_approval_signup_2: Successful signup with only hierarchy ids entered
    Given I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org fw       | OFW001, OFW002                          |
      | org freeform | false                                   |
      | pos fw       | PFW001, PFW002                          |
      | pos freeform | false                                   |
      | mgr org fw   | OFW002                                  |
      | mgr pos fw   | PFW003                                  |
      | mgr freeform | false                                   |
    And I log in as "salesmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007                       |
      | Password               | spectre                     |
      | Email                  | bond@example.gov            |
      | First name             | James                       |
      | Surname                | Bond                        |
      | City                   | London                      |
      | Country                | United Kingdom              |
      | Select an organisation | Deliveries                  |
      | Select a position      | Sales Engr                  |
    And I set the field "Select a manager" to "Manager Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.gov"

    # Successful signup outcome #1: plugin table has record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I click on "Edit" "link" in the "jb007" "table_row"
    Then the field "Username" matches value "jb007"
    And the field "Email address" matches value "bond@example.gov"
    And the field "First name" matches value "James"
    And the field "Surname" matches value "Bond"
    And the field "City/town" matches value "London"
    And the field "Country" matches value "United Kingdom"
    And I should not see "free text"
    And I should see "Deliveries"
    And I should see "Sales Engr"
    And I should see "Manager Sales - salesmgr ja"

    # Successful signup outcome #2: audit trail created.
    When I press "Cancel"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Self-registration with approval" row "Event name" column of "reportlog" table should contain "User added new account request"
    And "Self-registration with approval" row "Description" column of "reportlog" table should contain "jb007 (bond@example.gov) registered for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: email sent to applicant but cannot test with Behat.

    # Successful signup outcome #5: approver gets notification
    When I log in as "salesmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"

    When I follow "View full notification"
    Then I should see "they were asked to confirm their email address"
    Then I should see "jb007"
    Then I should see "bond@example.gov"


  # -------------------------------
  Scenario: auth_approval_signup_3: Successful signup with hierarchies
    Given I set these auth approval plugin settings:
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
    And I log in as "vp"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    And I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007                       |
      | Password               | spectre                     |
      | Email                  | bond@example.gov            |
      | First name             | James                       |
      | Surname                | Bond                        |
      | City                   | London                      |
      | Country                | United Kingdom              |
      | Manager free text      | M (Judi Dench)              |
      | Select an organisation | Deliveries                  |
      | Organisation free text | Secret Service MI5          |
      | Select a position      | Sales Engr                  |
      | Position free text     | Spy (First class)           |
    And I set the field "Select a manager" to "Manager Sales"
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.gov"

    # Successful signup outcome #1: plugin table has record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.gov"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    When I click on "Edit" "link" in the "jb007" "table_row"
    Then the field "Username" matches value "jb007"
    And the field "Email address" matches value "bond@example.gov"
    And the field "First name" matches value "James"
    And the field "Surname" matches value "Bond"
    And the field "City/town" matches value "London"
    And the field "Country" matches value "United Kingdom"
    And the field "Organisation free text" matches value "Secret Service MI5"
    And the field "Position free text" matches value "Spy (First class)"
    And the field "Manager free text" matches value "M (Judi Dench)"
    And I should see "Deliveries"
    And I should see "Sales Engr"
    And I should see "Manager Sales - salesmgr ja"

    # Successful signup outcome #2: audit trail created.
    When I press "Cancel"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Self-registration with approval" row "Event name" column of "reportlog" table should contain "User added new account request"
    And "Self-registration with approval" row "Description" column of "reportlog" table should contain "jb007 (bond@example.gov) registered for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: email sent to applicant but cannot test with Behat.

    # Successful signup outcome #5: approver gets notification
    When I log in as "vp"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"

    When I follow "View full notification"
    Then I should see "they were asked to confirm their email address"
    Then I should see "jb007"
    Then I should see "bond@example.gov"

