@mod @mod_scorm @javascript @totara
Feature: Guest access to SCORM activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | visitor  | Visiting  | Student  | visitor@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"
    And I set the following administration settings values:
      | guestloginbutton | Show |
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | SCORM guest test                   |
      | Description | Some test of guest access to SCORM |
      | ID number   | SCORM1                             |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I press "Save and return to course"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I log out

  Scenario: Confirm guest account access to SCORM is off by default and can be enabled with override
    When I press "Log in as a guest"
    And I am on "Course 1" course homepage
    Then I should see "SCORM guest test"

    When I follow "SCORM guest test"
    Then I should see "You are not allowed to launch SCORM content."
    And I should not see "Number of attempts you have made"
    And I should not see "Grade reported"

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:launch" "table_row"
    And I press "Guest"
    And I log out
    And I press "Log in as a guest"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should not see "Number of attempts you have made"
    And I should not see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I press "Enter"
    And I should see "Preview mode"
    And I am on site homepage

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:savetrack" "table_row"
    And I press "Guest"
    And I log out
    And I press "Log in as a guest"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should not see "Number of attempts you have made"
    And I should not see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I press "Enter"
    And I should see "Preview mode"
    And I am on site homepage

    When I log in as "admin"
    # Oh well, we should not be using generators here, but the permissions UI is not behat friendly.
    And the following "permission overrides" exist:
      | capability     | permission | role   | contextlevel    | reference |
      | mod/scorm:view | Prohibit   | guest  | Activity module | SCORM1    |
    And I log out
    And I press "Log in as a guest"
    And I am on "Course 1" course homepage
    Then I should not see "SCORM guest test"

  Scenario: Confirm course guest access to SCORM is off by default and can be enabled with override
    When I log in as "visitor"
    And I am on "Course 1" course homepage
    Then I should see "SCORM guest test"

    When I follow "SCORM guest test"
    Then I should see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made"
    And I should see "Grade reported"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:launch" "table_row"
    And I press "Guest"
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I should not see "Start a new attempt"
    And I press "Enter"
    And I should see "Preview mode"
    And I am on site homepage
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:savetrack" "table_row"
    And I press "Guest"
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should see "Mode:"
    And I should see "Preview"
    And I should see "Normal"
    And I press "Enter"
    And I should not see "Preview mode"
    And I am on site homepage

    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should see "Number of attempts you have made: 1"
    And I log out

    When I log in as "admin"
    # Oh well, we should not be using generators here, but the permissions UI is not behat friendly.
    And the following "permission overrides" exist:
      | capability     | permission | role   | contextlevel    | reference |
      | mod/scorm:view | Prohibit   | guest  | Activity module | SCORM1    |
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    Then I should not see "SCORM guest test"
    And I log out

  Scenario: Confirm SCORM guest access works when preview option is disabled
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:launch" "table_row"
    And I press "Guest"
    And I navigate to "Edit settings" node in "SCORM package administration"
    And I expand all fieldsets
    And I set the field "Disable preview mode" to "Yes"
    And I press "Save and display"
    Then I should not see "Mode:"
    And I should not see "Preview"
    And I should not see "Normal"
    And I log out

    When  I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I press "Enter"
    And I should see "Preview mode"
    And I am on site homepage
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:savetrack" "table_row"
    And I press "Guest"
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made: 0"
    And I should see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview"
    And I should not see "Normal"
    And I press "Enter"
    And I should not see "Preview mode"
    And I am on site homepage

    When I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should see "Number of attempts you have made: 1"
    And I log out

  Scenario: Test course guest access to SCORM when skipview is enabled
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Edit settings" node in "SCORM package administration"
    And I expand all fieldsets
    And I set the field "Learner skip content structure page" to "First"
    And I press "Save and display"
    Then I should see "Mode:"
    And I should see "Preview"
    And I should see "Normal"
    And I log out

    When I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should see "You are not allowed to launch SCORM content."
    And I should see "Number of attempts you have made"
    And I should see "Grade reported"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:launch" "table_row"
    And I press "Guest"
    And I click on "Allow" "link" in the "mod/scorm:skipview" "table_row"
    And I press "Guest"
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "Number of attempts you have made: 0"
    And I should not see "Grade reported"
    And I should not see "Mode:"
    And I should see "Preview mode"
    And I am on site homepage
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    And I navigate to "Permissions" node in "SCORM package administration"
    And I click on "Allow" "link" in the "mod/scorm:savetrack" "table_row"
    And I press "Guest"
    And I log out
    And I log in as "visitor"
    And I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should not see "You are not allowed to launch SCORM content."
    And I should not see "Number of attempts you have made: 0"
    And I should not see "Grade reported"
    And I should not see "Mode:"
    And I should not see "Preview mode"
    And I am on site homepage

    When I am on "Course 1" course homepage
    And I follow "SCORM guest test"
    Then I should see "Number of attempts you have made: 1"
    And I log out
