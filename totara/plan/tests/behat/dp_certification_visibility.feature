@totara @totara_plan
Feature: See that certification visibility affects Record of Learning: Certifications content correctly.
  Change the visibility settings of a certification through several states and see that the certification is correctly displayed in the RoL.

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
    And the following "certifications" exist in "totara_program" plugin:
      | fullname                      | shortname |
      | RoLCertVisibility Test Cert 1 | testcert1 |
      | RoLCertVisibility Test Cert 2 | testcert2 |
    And the following "program assignments" exist in "totara_program" plugin:
      | user    | program   |
      | user001 | testcert1 |
      | user002 | testcert1 |
      | user002 | testcert2 |
    And the following job assignments exist:
      | user    | fullname       | manager |
      | user001 | jobassignment1 | mana003 |
      | user002 | jobassignment2 | mana003 |

  @javascript
  Scenario: Normal visibility (default), visible (default).
    # RoL: Certs tab should be shown and contains the certification for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden.
    When I log in as "admin"
    And I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should be shown and contains the certification for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    # Should be marked hidden.
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Certifications"
    # Should be marked hidden.
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden, 2nd certification assigned.
    When I log in as "admin"
    And I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should be visible and contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    # Should be marked hidden.
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be visible and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    # Should be marked hidden.
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal vis hidden, switch to audience vis.
    When I log in as "admin"
    And I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visible" to "0"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # To start, check that RoL: Certs tab is shown and contains the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    # Should be marked hidden.
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # Switch the site setting, certification is now set to all users (default).
    When I log out
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # Then, check that RoL: Certs tab should is shown and contains the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    # Should NOT be marked hidden!!!
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be visible and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, all users (default).
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # RoL: Certs tab should be shown and contains the certification for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users and auds.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should be shown and contains the certification for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should be shown and contains the certification for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should not be visible to learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Certifications" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should not see "Certifications" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users, 2nd certification assigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    # RoL: Certs tab should be visible but not contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should not see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should not see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Certification ROL: Audience visibility, no users, newly assigned in 1st certification and then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    Then I should see "Not certified" in the "fn_002 ln_002" "table_row"

    When I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "fn_002 ln_002" "table_row"
    And I click on "Remove" "button"

    # RoL: Certs tab should be visible but not contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should not see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should not see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Certification ROL: Audience visibility, no users, certified, before window opens in 1st certification and then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Certified, before window opens |
      | timecompleted[day]             | 1                              |
      | timecompleted[month]           | September                      |
      | timecompleted[year]            | 2030                           |
      | timecompleted[hour]            | 12                             |
      | timecompleted[minute]          | 30                             |
      | timewindowopens[day]           | 2                              |
      | timewindowopens[month]         | September                      |
      | timewindowopens[year]          | 2030                           |
      | timewindowopens[hour]          | 12                             |
      | timewindowopens[minute]        | 30                             |
      | timeexpires[day]               | 3                              |
      | timeexpires[month]             | September                      |
      | timeexpires[year]              | 2030                           |
      | timeexpires[hour]              | 12                             |
      | timeexpires[minute]            | 30                             |
      | baselinetimeexpires[day]       | 3                              |
      | baselinetimeexpires[month]     | September                      |
      | baselinetimeexpires[year]      | 2030                           |
      | baselinetimeexpires[hour]      | 12                             |
      | baselinetimeexpires[minute]    | 30                             |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    When I follow "Return to certification"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "fn_002 ln_002" "table_row"
    And I click on "Remove" "button"

    # RoL: Certs tab should be visible but not contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Certification ROL: Audience visibility, no users, certified, window is open in 1st certification and then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Certified, window is open |
      | timecompleted[day]             | 1                         |
      | timecompleted[month]           | September                 |
      | timecompleted[year]            | 2030                      |
      | timecompleted[hour]            | 12                        |
      | timecompleted[minute]          | 30                        |
      | timewindowopens[day]           | 2                         |
      | timewindowopens[month]         | September                 |
      | timewindowopens[year]          | 2030                      |
      | timewindowopens[hour]          | 12                        |
      | timewindowopens[minute]        | 30                        |
      | timeexpires[day]               | 3                         |
      | timeexpires[month]             | September                 |
      | timeexpires[year]              | 2030                      |
      | timeexpires[hour]              | 12                        |
      | timeexpires[minute]            | 30                        |
      | baselinetimeexpires[day]       | 3                         |
      | baselinetimeexpires[month]     | September                 |
      | baselinetimeexpires[year]      | 2030                      |
      | baselinetimeexpires[hour]      | 12                        |
      | baselinetimeexpires[minute]    | 30                        |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    When I follow "Return to certification"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "fn_002 ln_002" "table_row"
    And I click on "Remove" "button"
    Then I should see "'fn_002 ln_002' has been removed from the program"

    # RoL: Certs tab should be visible but not contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

    # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Certification ROL: Audience visibility, no users, expired in 2nd certification and then unassigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I set the field "Enable program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on "RoLCertVisibility Test Cert 1" certification homepage
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field "Visibility" to "No users"
    And I press "Save changes"
    Then I should see "Program details saved successfully"

    When I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Expired   |
      | timedue[day]                   | 3         |
      | timedue[month]                 | September |
      | timedue[year]                  | 2015      |
      | timedue[hour]                  | 12        |
      | timedue[minute]                | 30        |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    When I follow "Return to certification"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "fn_002 ln_002" "table_row"
    And I click on "Remove" "button"

  # RoL: Certs tab should be visible but not contain the certification for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"

  # RoL: Certs tab should be shown and contains the certification for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Certifications"
    And I should see "RoLCertVisibility Test Cert 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCertVisibility Test Cert 2" in the "#dp-plan-content" "css_element"
