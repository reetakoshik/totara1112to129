@totara @auth @auth_approved @javascript
Feature: auth_approved: signup page fields
  In order to access courses in a Totara website
  As an external user
  I need to sign up for site access.

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
  Scenario: auth_approval_signup_page_0: not active
    When I set these auth approval plugin settings:
      | active | false |
    And I follow "Log in"
    Then I should not see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1a: minimal data entry page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email address"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City/town"
    And I should see "Country"

    And I should not see "Select an organisation"
    And I should not see "Organisation free text"
    And I should not see "If you are unable to find your organisation, please contact us"

    And I should not see "Select a position"
    And I should not see "Position free text"
    And I should not see "If you are unable to find your position, please contact us"

    And I should not see "Select a manager"
    And I should not see "Manager free text"
    And I should not see "If you are unable to find your manager, please contact us"

    When I press "Request account"
    Then I should see "Missing username"

    When I set the following fields to these values:
      | Username | jb007 |
    And I press "Request account"
    Then I should see "Missing password"

    When I set the following fields to these values:
      | Password | spectre |
    And I press "Request account"
    Then I should see "Missing email address"

    When I set the following fields to these values:
      | Email address | bond@example.gov |
    And I press "Request account"
    Then I should see "Missing given name"

    When I set the following fields to these values:
      | First name | James |
    And I press "Request account"
    Then I should see "Missing surname"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1b: password fails policy
    When the following config values are set as admin:
      | passwordpolicy | 1 |
    And I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Passwords must have at least 1 non-alphanumeric character(s) such as as *, -, or #."

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1c: duplicate of existing system user name
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | itmgr            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "This username already exists, choose another"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1d: duplicate existing system user email
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | itmgr             |
      | Password      | spectre           |
      | Email address | itdev@example.com |
      | First name    | James             |
      | Surname       | Bond              |
      | City          | London            |
      | Country       | United Kingdom    |
    And I press "Request account"
    Then I should see "This email address is already registered"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1e: duplicate of pending signup user name
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname | signup time | status   |
      | jb007    | spectre    | bond@example.com | James      | Bond    | -3 days     | pending  |
    And I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Pending request with the same username already exists"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1f: duplicate of pending signup email
    When the following "signups" exist in "auth_approved" plugin:
      | username | password   | email            | first name | surname | signup time | status   |
      | jb007    | spectre    | bond@example.gov | James      | Bond    | -3 days     | pending  |
    And I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb006            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Pending request with the same email address already exists"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_1g: single manager autocomplete selection
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | mgr org fw   | OFW002                                  |
      | mgr pos fw   | PFW003                                  |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb006            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I should see "No manager selected"
    When I set the field "Select a manager" to "Manager Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    Then I should see "Manager Sales - salesmgr ja"

    When I press "Cancel"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb006            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I should see "No manager selected"
    When I set the field "Select a manager" to "Engr Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    Then I should see "Engr Sales - salesengr ja"
    And I should not see "Manager Sales - salesmgr ja"

    When I press "Cancel"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    And I set the following fields to these values:
      | Username      | jb006            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I should see "No manager selected"
    When I set the field "Select a manager" to "Vice President"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    Then I should see "Vice President - vp ja"
    And I should not see "Manager Sales - salesmgr ja"
    And I should not see "Engr Sales - salesengr ja"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_2a: single hierarchy, freeform and select page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org freeform | true                                    |
      | org fw       | OFW001                                  |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email address"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City/town"
    And I should see "Country"

    And I should see "Organisation free text"
    And I should see "Select an organisation"
    And I should see "Information Tech"
    And I should see "Finance"
    And I should not see "Deliveries"
    And I should not see "Sales"
    And I should see "You must provide either an organisation or free text organisation"
    And I should not see "If you are unable to find your organisation, please contact us"

    And I should not see "Select a position"
    And I should not see "Position free text"
    And I should not see "You must provide either a position or free text position"
    And I should not see "If you are unable to find your position, please contact us"

    And I should not see "Select a manager"
    And I should not see "Manager free text"
    And I should not see "You must provide either a manager or free text manager"

    And I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing organisation"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_2b: single hierarchy, only select page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | pos fw       | PFW001                                  |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email address"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City/town"
    And I should see "Country"

    And I should not see "Select an organisation"
    And I should not see "Organisation free text"
    And I should not see "You must provide either an organisation or free text organisation"
    And I should not see "If you are unable to find your organisation, please contact us"

    And I should not see "Position free text"
    And I should see "Select a position"
    And I should see "IT dept secretary"
    And I should see "IT Developer"
    And I should see "IT dept manager"
    And I should see "Fin dept manager"
    And I should not see "Sales dept secretary"
    And I should not see "Sales Engr"
    And I should not see "Sales dept manager"
    And I should not see "You must provide either a position or free text position"
    And I should see "If you are unable to find your position, please contact us"

    And I should not see "Select a manager"
    And I should not see "Manager free text"

    When I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing position"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_2c: single hierarchy, only freeform page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | mgr freeform | true                                    |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email address"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City/town"
    And I should see "Country"

    And I should not see "Select an organisation"
    And I should not see "Organisation free text"
    And I should not see "You must provide either an organisation or free text organisation"
    And I should not see "If you are unable to find your organisation, please contact us"

    And I should not see "Select a position"
    And I should not see "Position free text"
    And I should not see "You must provide either a position or free text position"
    And I should not see "If you are unable to find your position, please contact us"

    And I should see "Manager free text"
    And I should not see "You must provide either a manager or free text manager"
    And I should not see "If you are unable to find your manager, please contact us"
    And I should not see "Select a manager"

    When I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing manager"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_3a: multiple hierarchies, all freeform page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org freeform | true                                    |
      | pos freeform | true                                    |
      | mgr freeform | true                                    |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email address"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City/town"
    And I should see "Country"

    And I should see "Organisation free text"
    And I should not see "You must provide either an organisation or free text organisation"
    And I should not see "If you are unable to find your organisation, please contact us"
    And I should not see "Select an organisation"

    And I should see "Position free text"
    And I should not see "You must provide either a position or free text position"
    And I should not see "If you are unable to find your position, please contact us"
    And I should not see "Select a position"

    And I should see "Manager free text"
    And I should not see "You must provide either a manager or free text manager"
    And I should not see "If you are unable to find your manager, please contact us"
    And I should not see "Select a manager"

    When I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing organisation"

    When I set the following fields to these values:
      | Organisation free text | Universal Exports |
    And I press "Request account"
    Then I should see "Missing position"

    When I set the following fields to these values:
      | Manager free text  | M |
    And I press "Request account"
    Then I should see "Missing position"

    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_3b: multiple hierarchies, all select page
    When I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
      | org fw       | OFW002                                  |
      | mgr org fw   | OFW002                                  |
      | mgr pos fw   | PFW003                                  |
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City"
    And I should see "Country"

    And I should not see "Organisation free text"
    And I should see "If you are unable to find your organisation, please contact us"
    And I should not see "You must provide either an organisation or free text organisation"
    And I should see "Select an organisation"
    And I should not see "Information Tech"
    And I should not see "Finance"
    And I should see "Deliveries"
    And I should see "Sales"

    And I should not see "Position free text"
    And I should not see "If you are unable to find your position, please contact us"
    And I should not see "You must provide either a position or free text position"
    And I should not see "Select a position"

    And I should not see "Manager free text"
    And I should not see "You must provide either a manager or free text manager"
    And I should see "If you are unable to find your manager, please contact us"
    And I should see "Select a manager"

    When I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing organisation"

    When I set the field "Select a manager" to "Manager Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Request account"
    Then I should see "Missing organisation"
    When I press "Cancel"
    Then I should see "Is this your first time here?"


  # -------------------------------
  Scenario: auth_approval_signup_page_3c: multiple hierarchies, freeform and select hierarchy id page
    When I set these auth approval plugin settings:
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
    And I follow "Log in"
    Then I should see "Is this your first time here?"

    When I click on "Create new account" "button"
    Then I should see "Nothing; everything is self explanatory"
    And I should see "Username"
    And I should see "Password"
    And I should see "Email"
    And I should see "First name"
    And I should see "Surname"
    And I should see "City"
    And I should see "Country"

    And I should see "Organisation free text"
    And I should see "You must provide either an organisation or free text organisation"
    And I should not see "If you are unable to find your organisation, please contact us"
    And I should see "Select an organisation"
    And I should see "Information Tech"
    And I should see "Finance"
    And I should see "Deliveries"
    And I should see "Sales"

    And I should see "Position free text"
    And I should see "You must provide either a position or free text position"
    And I should not see "If you are unable to find your position, please contact us"
    And I should see "Select a position"
    And I should see "IT dept secretary"
    And I should see "IT Developer"
    And I should see "IT dept manager"
    And I should see "Fin dept manager"
    And I should see "Sales dept secretary"
    And I should see "Sales Engr"
    And I should see "Sales dept manager"
    And I should not see "CxO"

    And I should see "Manager free text"
    And I should see "You must provide either a manager or free text manager"
    And I should not see "If you are unable to find your manager, please contact us"
    And I should see "Select a manager"

    When I set the following fields to these values:
      | Username      | jb007            |
      | Password      | spectre          |
      | Email address | bond@example.gov |
      | First name    | James            |
      | Surname       | Bond             |
      | City          | London           |
      | Country       | United Kingdom   |
    And I press "Request account"
    Then I should see "Missing organisation"

    When I set the following fields to these values:
      | Manager free text | The Sales Guy               |
    And I set the field "Select a manager" to "Manager Sales"
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Request account"
    Then I should see "Missing organisation"

    When I set the following fields to these values:
      | Organisation free text | Universal Exports |
      | Select an organisation | Sales             |
    And I press "Request account"
    Then I should see "Missing position"

    When I press "Cancel"
    Then I should see "Is this your first time here?"
