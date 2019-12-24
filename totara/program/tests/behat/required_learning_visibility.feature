@totara @totara_program
Feature: See that program visibility affects Required Learning content correctly.
  Change the visibility settings of a program through several states and see that the program is correctly displayed in the RL.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
      | user002 | fn_002 | ln_002 | user002@example.com |
      | user003 | fn_003 | ln_003 | user003@example.com |
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
      | idnumber     | fullname    | user      | manager   |
      | firstjob     | firstjob    | user001   | user003   |
    # Get back the removed dashboard item for now.
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Normal visibility (default), visible (default), RL should be shown and link to the program.
    When I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 1" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Normal visibility (default), visible (default), 2nd program assigned, RL should be shown.
    When I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Your required learning is shown below."
    And I should see "RoLProgVisibility Test Program 1" in the "#required-learning-list" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#required-learning-list" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden, RL should be shown and link to the program.
    When I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "RoLProgVisibility Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 1" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Audience visibility, all users (default), RL should be shown and link to the program.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 1" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users and auds, RL should be shown and link to the program.
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

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 1" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users, RL should be shown and link to the program.
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

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 1" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users, 2nd program assigned, RL should be shown.
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

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Your required learning is shown below."
    And I should see "RoLProgVisibility Test Program 1" in the "#required-learning-list" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the "#required-learning-list" "css_element"

  @javascript
  Scenario: Audience visibility, no users, RL should not be visible.
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

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning"

  @javascript
  Scenario: Audience visibility, no users, 2nd program assigned, RL should be visible and go straight to prog 2.
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

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Required Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "RoLProgVisibility Test Program 2" in the ".breadcrumb-nav" "css_element"

  Scenario: Manager can view their reports required programs
    When I log in as "user003"
    And I click on "Team" in the totara menu
    Then I should see "fn_001 ln_001"
    And I should not see "fn_002 ln_002"
    And I should see "1 record shown"

    When I click on "Required" "link"
    Then I should see "RoLProgVisibility Test Program 1"
    And I should not see "RoLProgVisibility Test Program 2"

    When I click on "RoLProgVisibility Test Program 1" "link"
    Then I should see "Required Learning"
    And I should see "RoLProgVisibility Test Program 1"

    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Reports"
    And I press "Get these logs"
    Then I should see "The program 1 was viewed by user 5."
    Then I should not see "The program 1 was viewed by user 3."
