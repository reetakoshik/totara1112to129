@totara_cohort @totara @javascript
Feature: Verify self registration updates audience membership and enrolled learning correctly.
  In order to compute the members of a cohort with dynamic membership
  As an admin
  I should be able to use menu custom field values for filter rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | city       | country |
      | manager   | fnameman  | lnameman | Sydney     | AU      |
      | manual001 | fname001  | lname001 | Wellington | NZ      |
      | manual002 | fname002  | lname002 | Wellington | NZ      |
      | manual003 | fname003  | lname003 | Wellington | NZ      |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | Crs1      | topics | 1                |
      | Course 2 | Crs2      | topics | 1                |
      | Course 3 | Crs3      | topics | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | prog1     |
      | Program 2 | prog1     |
      | Program 3 | prog1     |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname |
      | Certification 1 | cert1     |
      | Certification 2 | cert2     |
      | Certification 3 | cert3     |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber  |
      | Organisation Framework | oframe    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber  | org_framework |
      | Organisation One | org1      | oframe        |
      | Organisation Two | org2      | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
      | Position Two | pos2      | pframe        |
    And the following job assignments exist:
      | user      | fullname        | organisation | position | manager |
      | manager   | General Manager | org1           | pos1   | admin   |
      | manual001 | General User    | org1           | pos1   | manager |
      | manual002 | General User    | org1           | pos1   | manager |
      | manual003 | General User    | org1           | pos1   | manager |
    And the following "cohorts" exist:
      | name                | idnumber | cohorttype |
      | Username - manual   | A1       | 2          |
      | Username - selfie   | A2       | 2          |
      | City - Wellington   | A3       | 2          |
      | City - Wellywood    | A4       | 2          |
      | Country - NZ        | A5       | 2          |
      | Manager - man       | A6       | 2          |
      | Position - pos2     | A7       | 2          |
      | Organisation - org2 | A8       | 2          |
    And I log in as "admin"
    # Set rules for the A1(Username - manual) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - manual"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Username"
    And I set the field "equal" to "starts with"
    And I set the field "listofvalues" to "man"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fnameman lnameman" in the "#cohort_members" "css_element"
    And I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"
    # Set rules for the A2(Username - selfie) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - selfie"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Username"
    And I set the field "equal" to "starts with"
    And I set the field "listofvalues" to "self"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"
    # Set rules for the A3(City - Wellington) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellington"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "City"
    And I set the field "listofvalues" to "Wellington"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"
    # Set rules for the A4(City - Wellywood) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellywood"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "City"
    And I set the field "listofvalues" to "Wellywood"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"
    # Set rules for the A5(Country - NZ) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Country - NZ"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Country"
    And I set the field "listofvalues[]" to "New Zealand"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"
    # Set rules for the A6(Manager - man) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Manager - man"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Managers"
    And I click on "fnameman lnameman" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"
    # Set rules for the A7(Position - pos2) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Position - pos2"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Positions"
    And I click on "Position Two" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"
    # Set rules for the A8(organisation - org2) audience.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Organisation - org2"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Organisations"
    And I click on "Organisation Two" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"
    # Turn on email-based self-registration
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And I navigate to "Email-based self-registration" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
    | Position     | Yes |
    | Organisation | Yes |
    | Manager      | Yes |
    And I press "Save changes"
    And the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
    And I log out

  Scenario: Verify self registered users are added to audiences instantly when confirmed
    # Create the self auth user for positive testing.
    When I click on "Create new account" "button"
    And I set the field "Username" to "selfie001"
    And I set the field "Password" to "selfie001"
    And I set the field "Email address" to "selfie001@example.com"
    And I set the field "Email (again)" to "selfie001@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroOne"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie001@example.com"
    # Create a second self auth user for negative testing.
    When I click on "Continue" "button"
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie002"
    And I set the field "Password" to "selfie002"
    And I set the field "Email address" to "selfie002@example.com"
    And I set the field "Email (again)" to "selfie002@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroTwo"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie002@example.com"
    # Check audience membership pre-confirmation.
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - manual"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - selfie"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellington"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellywood"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Country - NZ"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Manager - man"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Position - pos2"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Organisation - org2"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"

    # Check audience membership post-confirmation.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Confirm" "link" in the "Selfie ZeroZeroOne" "table_row"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - manual"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Username - selfie"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellington"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "City - Wellywood"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Country - NZ"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Manager - man"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Position - pos2"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Organisation - org2"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"

  Scenario: Verify self registered users are added to courses instantly when confirmed
    # Create the self auth user for positive testing.
    When I click on "Create new account" "button"
    And I set the field "Username" to "selfie001"
    And I set the field "Password" to "selfie001"
    And I set the field "Email address" to "selfie001@example.com"
    And I set the field "Email (again)" to "selfie001@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroOne"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie001@example.com"
    # Create a second self auth user for negative testing.
    When I click on "Continue" "button"
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie002"
    And I set the field "Password" to "selfie002"
    And I set the field "Email address" to "selfie002@example.com"
    And I set the field "Email (again)" to "selfie002@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroTwo"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie002@example.com"
    # Create and confirm initial audience enrolments for courses.
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I click on "Add enrolled audiences" "button"
    And I click on "Username - manual" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "fnameman lnameman" in the "userenrolment" "table"
    And I should see "fname001 lname001" in the "userenrolment" "table"
    And I should see "fname002 lname002" in the "userenrolment" "table"
    And I should see "fname003 lname003" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"
    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I click on "Add enrolled audiences" "button"
    And I click on "Username - selfie" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should not see "fnameman lnameman" in the "userenrolment" "table"
    And I should not see "fname001 lname001" in the "userenrolment" "table"
    And I should not see "fname002 lname002" in the "userenrolment" "table"
    And I should not see "fname003 lname003" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"
    When I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I click on "Add enrolled audiences" "button"
    And I click on "City - Wellington" "link"
    And I click on "City - Wellywood" "link"
    And I click on "Position - pos2" "link"
    And I click on "Organisation - org2" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"
    And I trigger cron
    And I am on "Course 3" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should not see "fnameman lnameman" in the "userenrolment" "table"
    And I should see "fname001 lname001" in the "userenrolment" "table"
    And I should see "fname002 lname002" in the "userenrolment" "table"
    And I should see "fname003 lname003" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"

    # Confirm user 1 but not user 2.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Confirm" "link" in the "Selfie ZeroZeroOne" "table_row"
    And I navigate to "Audiences" node in "Site administration > Audiences"

    # Check course enrolments for user 1 (but not user 2) as admin
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "fnameman lnameman" in the "userenrolment" "table"
    And I should see "fname001 lname001" in the "userenrolment" "table"
    And I should see "fname002 lname002" in the "userenrolment" "table"
    And I should see "fname003 lname003" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"
    When I am on "Course 2" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should not see "fnameman lnameman" in the "userenrolment" "table"
    And I should not see "fname001 lname001" in the "userenrolment" "table"
    And I should not see "fname002 lname002" in the "userenrolment" "table"
    And I should not see "fname003 lname003" in the "userenrolment" "table"
    And I should see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"
    When I am on "Course 3" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should not see "fnameman lnameman" in the "userenrolment" "table"
    And I should see "fname001 lname001" in the "userenrolment" "table"
    And I should see "fname002 lname002" in the "userenrolment" "table"
    And I should see "fname003 lname003" in the "userenrolment" "table"
    And I should see "Selfie ZeroZeroOne" in the "userenrolment" "table"
    And I should not see "Selfie ZeroZeroTwo" in the "userenrolment" "table"

    # Log in as selfie001 and check display of RoL etc.
    When I log out
    And I log in as "selfie001"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Course 1" in the "plan_courses" "table"
    And I should see "Course 2" in the "plan_courses" "table"
    And I should see "Course 3" in the "plan_courses" "table"

  Scenario: Verify self registered users are added to programs instantly when confirmed
    # Create audience/pos/org/manager assignments for programs
    When I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    And I click on "Username - manual" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "'Username - manual' has been added to the program"
    And I should see "4 learner(s) assigned: 4 active, 0 exception(s)"

    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 2" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    And I click on "Username - selfie" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "'Username - selfie' has been added to the program"
    And I should see "0 learner(s) assigned: 0 active, 0 exception(s)"

    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 3" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation Two" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Positions"
    And I click on "Position Two" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "fnameman lnameman (manager@example.com) - General Manager" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds

    Then I should see "'fnameman lnameman - General Manager' has been added to the program"
    And I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    # Create the self auth user for positive testing.
    When I log out
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie001"
    And I set the field "Password" to "selfie001"
    And I set the field "Email address" to "selfie001@example.com"
    And I set the field "Email (again)" to "selfie001@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroOne"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie001@example.com"
    # Create a second self auth user for negative testing.
    When I click on "Continue" "button"
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie002"
    And I set the field "Password" to "selfie002"
    And I set the field "Email address" to "selfie002@example.com"
    And I set the field "Email (again)" to "selfie002@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroTwo"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie002@example.com"
    # Log in as admin and double check they aren't currently in any programs.
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 3" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link"
    Then I should not see "Selfie"
    # Confirm user 1 but not user 2.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Confirm" "link" in the "Selfie ZeroZeroOne" "table_row"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 3" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link"
    Then I should see "Selfie ZeroZeroOne"
    And I should not see "Selfie ZeroZeroTwo"
    When I click on "Edit completion records" "link" in the "Selfie ZeroZeroOne" "table_row"
    Then I should see "Hold position of 'Position Two'" in the ".alert-info" "css_element"
    And I should see "Member of organisation 'Organisation Two'." in the ".alert-info" "css_element"
    And I should see "Part of 'fnameman lnameman' team." in the ".alert-info" "css_element"
    And I log out
    And I log in as "selfie001"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Program 1" in the "plan_programs" "table"
    And I should see "Program 2" in the "plan_programs" "table"
    And I should see "Program 3" in the "plan_programs" "table"

  Scenario: Verify self registered users are added to certifications instantly when confirmed
    # Create audience/pos/org/manager assignments for programs
    When I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "Edit certification details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    And I click on "Username - manual" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds
    Then I should see "'Username - manual' has been added to the program"
    And I should see "4 learner(s) assigned: 4 active, 0 exception(s)"

    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 2" "link"
    And I click on "Edit certification details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    And I click on "Username - selfie" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds
    Then I should see "'Username - selfie' has been added to the program"
    And I should see "0 learner(s) assigned: 0 active, 0 exception(s)"

    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 3" "link"
    And I click on "Edit certification details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation Two" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"

    And I set the field "Add a new" to "Positions"
    And I click on "Position Two" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    And I wait "1" seconds

    And I set the field "Add a new" to "Management hierarchy"
    And I click on "fnameman lnameman (manager@example.com) - General Manager" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds

    Then I should see "'fnameman lnameman - General Manager' has been added to the program"
    And I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    # Create the self auth user for positive testing.
    When I log out
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie001"
    And I set the field "Password" to "selfie001"
    And I set the field "Email address" to "selfie001@example.com"
    And I set the field "Email (again)" to "selfie001@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroOne"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie001@example.com"
    # Create a second self auth user for negative testing.
    When I click on "Continue" "button"
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie002"
    And I set the field "Password" to "selfie002"
    And I set the field "Email address" to "selfie002@example.com"
    And I set the field "Email (again)" to "selfie002@example.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroTwo"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie002@example.com"
    # Log in as admin and double check they aren't currently in any programs.
    When I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 3" "link"
    And I click on "Edit certification details" "button"
    And I click on "Completion" "link"
    Then I should not see "Selfie"
    # Confirm user 1 but not user 2.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Confirm" "link" in the "Selfie ZeroZeroOne" "table_row"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 3" "link"
    And I click on "Edit certification details" "button"
    And I click on "Completion" "link"
    Then I should see "Selfie ZeroZeroOne"
    And I should not see "Selfie ZeroZeroTwo"
    When I click on "Edit completion records" "link" in the "Selfie ZeroZeroOne" "table_row"
    Then I should see "Hold position of 'Position Two'" in the ".alert-info" "css_element"
    And I should see "Member of organisation 'Organisation Two'." in the ".alert-info" "css_element"
    And I should see "Part of 'fnameman lnameman' team." in the ".alert-info" "css_element"
    And I log out
    And I log in as "selfie001"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should not see "Certification 1" in the "plan_certifications" "table"
    And I should see "Certification 2" in the "plan_certifications" "table"
    And I should see "Certification 3" in the "plan_certifications" "table"
