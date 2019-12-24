@totara @auth @auth_approved @javascript
Feature: auth_approved: email whitelist
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
  Scenario: auth_approval_whitelist_0: successful signup with no hierarchy
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
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "Automatic request approval notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "Automatic request approval notification" "table_row"
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
      | Email      | bond@example.org |
      | First name | James            |
      | Surname    | Bond             |
      | City       | London           |
      | Country    | United Kingdom   |
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.org"

    When I confirm self-registration request from email "bond@example.org"
    Then I should see "Thank you for confirming your account request, you can now log in using your requested username: jb007"

    # Successful signup outcome #1: plugin table has no "pending" record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    # Successful registration outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Reports"
    And I press "Get these logs"
    Then "User added new account request" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) registered for system access"
    And "Account request email was confirmed" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) confirmed email address"
    And "Account request was approved" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) approved for system access"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "James Bond"
    Then I should see "This user has no job assignments"

    # Successful signup outcome #3: applicant  can log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "James Bond"
    And I should see "Current Learning"

    # Successful signup outcome #4: approver does not get confirmation notification because the request was auto-approved
    When I log out
    And I log in as "itmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"
    And I should see "New account request was approved automatically"
    And I should not see "New account request requires approval"


  # -------------------------------
  Scenario: auth_approval_whitelist_1a: successful signup with no free form fields
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
    And I log in as "itmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "Automatic request approval notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "Automatic request approval notification" "table_row"
    And I wait until the page is ready
    And I log out

    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007                       |
      | Password               | spectre                     |
      | Email address          | bond@example.org            |
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
    Then I should see "An email should have been sent to your address at bond@example.org"

    When I confirm self-registration request from email "bond@example.org"
    Then I should see "Thank you for confirming your account request, you can now log in using your requested username: jb007"

    # Successful signup outcome #1: plugin table has no "pending" record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    # Successful registration outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Reports"
    And I press "Get these logs"
    Then "User added new account request" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) registered for system access"
    And "Account request email was confirmed" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) confirmed email address"
    And "Account request was approved" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) approved for system access"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "James Bond"
    And I follow "Unnamed job assignment"
    Then I should see "Sales Engr"
    And I should see "Deliveries"
    And I should see "Manager Sales (salesmgr@example.com) - salesmgr ja"

    # Successful signup outcome #3: applicant can log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "James Bond"
    And I should see "Current Learning"

    # Successful signup outcome #4: approver does not get confirmation notification because the request was auto-approved
    When I log out
    And I log in as "itmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"
    And I should see "New account request was approved automatically"
    And I should not see "New account request requires approval"


  # -------------------------------
  Scenario: auth_approval_whitelist_1b: signup with one entered free form org field
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org fw       | OFW001, OFW002                          |
      | org freeform | true                                    |
      | pos fw       | PFW001, PFW002                          |
      | pos freeform | false                                   |
      | mgr org fw   | OFW002                                  |
      | mgr pos fw   | PFW003                                  |
      | mgr freeform | false                                   |
    And I log in as "itmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007                       |
      | Password               | spectre                     |
      | Email address          | bond@example.org            |
      | First name             | James                       |
      | Surname                | Bond                        |
      | City                   | London                      |
      | Country                | United Kingdom              |
      | Select an organisation | Deliveries                  |
      | Organisation free text | Universal Exports           |
      | Select a position      | Sales Engr                  |
    And I set the field "Select a manager" to "Manager Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.org"

    When I confirm self-registration request from email "bond@example.org"
    Then I should see "Thank you for confirming your account request, an email should have been sent to your address at bond@example.org with information describing the account approval process."

    # Successful signup outcome #1: plugin table has "pending" record - because of free form entry.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.org"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"

    # Successful registration outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Reports"
    And I press "Get these logs"
    Then "User added new account request" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) registered for system access"
    And "Account request email was confirmed" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) confirmed email address"
    And I should not see "jb007 (bond@example.org) approved for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: approver gets notification
    When I log in as "itmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"
    And I should see "New account request requires approval"


  # -------------------------------
  Scenario: auth_approval_whitelist_1b: signup with multiple entered free form fields
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org freeform | true                                    |
      | pos freeform | true                                    |
      | mgr freeform | true                                    |
    And I log in as "itmgr"
    And I follow "Preferences" in the user menu
    And I click on "Notification preferences" "link" in the "#page-content" "css_element"
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I click on "//td[@data-processor-name='popup']//label[@title='When you are not logged into Totara']" "xpath_element" in the "New unconfirmed request notification" "table_row"
    And I wait until the page is ready
    And I log out

    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

    When I log out
    And I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username               | jb007                       |
      | Password               | spectre                     |
      | Email address          | bond@example.org            |
      | First name             | James                       |
      | Surname                | Bond                        |
      | City                   | London                      |
      | Country                | United Kingdom              |
      | Manager free text      | M                           |
      | Organisation free text | Universal Exports           |
      | Position free text     | Spy (First Class)           |
    And I press "Request account"
    Then I should see "An email should have been sent to your address at bond@example.org"

    When I confirm self-registration request from email "bond@example.org"
    Then I should see "Thank you for confirming your account request, an email should have been sent to your address at bond@example.org with information describing the account approval process."

    # Successful signup outcome #1: plugin table has "pending" record - because of free form entry.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "jb007" row "User First Name" column of "auth_approved_pending_requests" table should contain "James"
    And "jb007" row "User Last Name" column of "auth_approved_pending_requests" table should contain "Bond"
    And "jb007" row "User's Email" column of "auth_approved_pending_requests" table should contain "bond@example.org"
    And "jb007" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "Yes"

    # Successful registration outcome #2: audit trail created.
    When I navigate to "Logs" node in "Site administration > Reports"
    And I press "Get these logs"
    Then "User added new account request" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) registered for system access"
    And "Account request email was confirmed" row "Description" column of "reportlog" table should contain "jb007 (bond@example.org) confirmed email address"
    And I should not see "jb007 (bond@example.org) approved for system access"

    # Successful signup outcome #3: applicant still cannot log in
    When I log out
    And I set the following fields to these values:
      | Username   | jb007   |
      | Password   | spectre |
    And I click on "Log in" "button"
    Then I should see "Invalid login, please try again"

    # Successful signup outcome #4: approver gets notification
    When I log in as "itmgr"
    And I open the notification popover
    Then I should see "Account request awaits email confirmation"
    And I should see "New account request requires approval"
