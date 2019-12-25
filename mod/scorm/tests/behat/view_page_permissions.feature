@mod @mod_scorm @javascript
Feature: mod_scorm: check view SCORM page permissions
  Depending on the permissions I have been granted
  As a user
  I should see different content on the SCORM viewing page.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | jr       | User      | One      | jr@example.com |
      | ji       | User      | Two      | ji@example.com |
      | bo       | User      | Three    | bo@example.com |
    And the following "roles" exist:
      | name        | shortname   | contextlevel | archetype    |
      | JustReports | JustReports | System       | staffmanager |
      | JustInfo    | JustInfo    | System       | user         |
      | Both        | Both        | System       | staffmanager |
    And the following "permission overrides" exist:
      | capability           | permission | role        | contextlevel | reference |
      | mod/scorm:view       | Allow      | JustReports | System       |           |
      | mod/scorm:savetrack  | Prohibit   | JustReports | System       |           |
      | mod/scorm:viewreport | Allow      | JustReports | System       |           |
      | mod/scorm:view       | Allow      | JustInfo    | System       |           |
      | mod/scorm:savetrack  | Allow      | JustInfo    | System       |           |
      | mod/scorm:viewreport | Prohibit   | JustInfo    | System       |           |
      | mod/scorm:view       | Allow      | Both        | System       |           |
      | mod/scorm:savetrack  | Allow      | Both        | System       |           |
      | mod/scorm:viewreport | Allow      | Both        | System       |           |
    And the following "role assigns" exist:
      | user | role        | contextlevel | reference |
      | jr   | JustReports | System       |           |
      | ji   | JustInfo    | System       |           |
      | bo   | Both        | System       |           |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user | course | role        |
      | jr   | C1     | JustReports |
      | ji   | C1     | JustInfo    |
      | bo   | C1     | Both        |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | SCORM viewing page permission test |
      | Description | SCORM viewing page permission test |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "SCORM viewing page permission test"


  # -------------------------------
  Scenario: SCORM view page content according to permissions
    When I log out
    And I log in as "jr"
    And I am on "Course 1" course homepage
    And I follow "SCORM viewing page permission test"
    # All users who can access the SCORM see the info tab. We also never redirect to reports by default.
    Then I should see "Info"
    And I should see "Grading method: Highest attempt"
    And I should see "Reports"
    And I should not see "Interactions report"

    When I follow "Reports"
    Then I should see "Info"
    And I should see "Reports"
    And I should see "Interactions report"

    When I log out
    And I log in as "ji"
    And I am on "Course 1" course homepage
    And I follow "SCORM viewing page permission test"
    # In Totara the Info tab is not displayed if it is the only tab present.
    Then I should not see "Info"
    And I should see "Grading method: Highest attempt"
    And I should not see "Reports"

    When I log out
    And I log in as "bo"
    And I am on "Course 1" course homepage
    And I follow "SCORM viewing page permission test"
    Then I should see "Info"
    And I should see "Grading method: Highest attempt"
    And I should see "Reports"

    When I switch to "Reports" tab
    And I should see "Interactions report"


  # -------------------------------
  Scenario: Guest users should be able to see SCORM but not reports
    Given I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"

    Given I set the following administration settings values:
      | guestloginbutton | Show |
    And I press "Save changes"

    When I log out
    And I log in as "guest"
    And I am on "Course 1" course homepage
    And I follow "SCORM viewing page permission test"
    # In Totara the Info tab is not displayed if it is the only tab present.
    Then I should not see "Info"
    And I should see "Grading method: Highest attempt"
    And I should not see "Reports"
