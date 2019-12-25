@totara @totara_plan
Feature: See that program visibility affects Record of Learning: Programs content correctly.
  Change the visibility settings of a program through several states and see that the program is correctly displayed in the RoL.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | mana003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    # Course enrolments ensure that the courses tab exists and is selected when navigating to the RoL.
    And the following "course enrolments" exist:
      | user    | course | role    |
      | user001 | C1     | student |
      | user002 | C1     | student |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                         | shortname |
      | RoLProgVisibility Test Program 1 | testprog1 |
      | RoLProgVisibility Test Program 2 | testprog2 |
    And the following "program assignments" exist in "totara_program" plugin:
      | user    | program   |
      | user001 | testprog1 |
      | user002 | testprog1 |
      | user002 | testprog2 |
    And the following job assignments exist:
      | user    | fullname       | manager |
      | user001 | jobassignment1 | mana003 |
      | user002 | jobassignment2 | mana003 |

  @javascript
  Scenario: Normal visibility (default), visible (default).
    # RoL: Progs tab should be shown and contains the program for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden.
    When I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should not be visible to learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    # Should be marked hidden.
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Programs"
    # Should be marked hidden.
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden, 2nd program assigned.
    When I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should be visible but not contain the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    # Should be marked hidden.
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be visible and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Programs"
    # Should be marked hidden.
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal vis hidden, switch to audience vis.
    When I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # To start, check that RoL: Progs tab is shown but does not contain the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    # Should be marked hidden.
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # Switch the site setting, program is now set to all users (default).
    When I log out
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # Then, check that RoL: Progs tab should is shown and contains the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    # Should NOT be marked hidden!!!
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be visible and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, all users (default).
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # RoL: Progs tab should be shown and contains the program for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users and auds.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should be shown and contains the program for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should be shown and contains the program for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should not be visible to learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Programs" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should not see "Programs" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users, 2nd program assigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Progs tab should be visible but not contain the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should not see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Programs"
    And I should not see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: ROL Program: Audience visibility, no users, 1st program not completed then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    Then I should see "Not complete" in the "fn_002 ln_002" "table_row"

    When I switch to "Assignments" tab
    And I click on "Delete" "link" in the "fn_002 ln_002" "table_row"
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program assignments saved successfully"

  # RoL: Progs tab should be visible but not contain the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should not see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Programs"
    And I should not see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: ROL Program: Audience visibility, no users, 1st program completed then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    And I set the following fields to these values:
      | Status                | Program complete |
      | timecompleted[day]    | 3                |
      | timecompleted[month]  | September        |
      | timecompleted[year]   | 2015             |
      | timecompleted[hour]   | 12               |
      | timecompleted[minute] | 30               |
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    When I follow "Return to program"
    And I switch to "Assignments" tab
    And I click on "Delete" "link" in the "fn_002 ln_002" "table_row"
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program assignments saved successfully"

    # RoL: Progs tab should be visible but not contain the program for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"

    # RoL: Progs tab should be shown and contains the program for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Programs"
    And I should see "RoLProgVisibility Test Program 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#dp-plan-content" "css_element"