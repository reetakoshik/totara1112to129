@totara @totara_cohort @totara_certification @javascript
Feature: Test the certification status rule in dynamic audiences
  I need to be able to select an audience as a rule in a dynamic audience

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Audience 1 | AUD001   | 2          |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner1  | One      | learner1@example.com |
      | learner2 | Learner2  | Two      | learner2@example.com |
      | learner3 | Learner3  | Three    | learner3@example.com |
      | learner4 | Learner4  | Four     | learner4@example.com |
      | learner5 | Learner5  | Five     | learner5@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion | completionstartonenrol |
      | Course One | course1   | topics | 1                | 1                      |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | activeperiod | windowperiod | recertifydatetype |
      | Certification 1 | cert1     | 1 month      | 1 month      | 1                 |

  Scenario: Ensure defaults are set correctly in the certification status rule
    Given I log in as "admin"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification status"
    Then I set the following fields to these values:
      | certifstatus_currentlycertified   | 1 |
      | certifstatus_currentlyexpired     | 0 |
      | certifstatus_nevercertified       | 0 |
      | certifassignmentstatus_assigned   | 1 |
      | certifassignmentstatus_unassigned | 1 |
    When I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User's certification status is 'Currently certified' with assignment status 'Assigned', 'Not assigned' for certification \"Certification 1\"" in the "Ruleset #1" "fieldset"

  Scenario: Ensure rules are not created when certifications are not selected
    Given I log in as "admin"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification status"
    Then I set the following fields to these values:
      | certifstatus_currentlycertified   | 1 |
      | certifstatus_currentlyexpired     | 0 |
      | certifstatus_nevercertified       | 0 |
      | certifassignmentstatus_assigned   | 1 |
      | certifassignmentstatus_unassigned | 1 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should not see "User's certification status is"

  Scenario: Ensure certification status rule form validation is correct
    Given I log in as "admin"
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification status"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"

    Then I should not see "Please select one or more options" in the "certifstatus" "fieldset"
    And I should not see "Please select one or more options" in the "certifassignmentstatus" "fieldset"

    When I set the following fields to these values:
      | certifstatus_currentlycertified   | 0 |
      | certifstatus_currentlyexpired     | 0 |
      | certifstatus_nevercertified       | 0 |
      | certifassignmentstatus_assigned   | 0 |
      | certifassignmentstatus_unassigned | 0 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Please select one or more options" in the "certifstatus" "fieldset"
    And I should see "Please select one or more options" in the "certifassignmentstatus" "fieldset"

    When I set the following fields to these values:
      | certifstatus_currentlycertified | 1 |
      | certifassignmentstatus_assigned | 1 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User's certification status is 'Currently certified' with assignment status 'Assigned' for certification \"Certification 1\"" in the "Ruleset #1" "fieldset"

  Scenario: Set certification status rule for Never certified and Assigned
    Given I log in as "admin"

    # Assign learner1 and 2, leaving the others unassigned.
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Certification 1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Learner1 One" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Learner2 Two" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # Create the rule.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification status"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I set the following fields to these values:
      | certifstatus_currentlycertified   | 0 |
      | certifstatus_currentlyexpired     | 0 |
      | certifstatus_nevercertified       | 1 |
      | certifassignmentstatus_assigned   | 1 |
      | certifassignmentstatus_unassigned | 0 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User's certification status is 'Never certified' with assignment status 'Assigned' for certification \"Certification 1\"" in the "Ruleset #1" "fieldset"

    # Check the results.
    When I press "Approve changes"
    And I follow "Members"
    Then I should see "Admin User"
    And I should see "Learner1 One"
    And I should see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"

  Scenario: Set certification status rule for Never certified and Unassigned
    Given I log in as "admin"

    # Assign learner1 and 2, leaving the others unassigned.
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Certification 1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Learner1 One" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Learner2 Two" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # Create the rule.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification status"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I set the following fields to these values:
      | certifstatus_currentlycertified   | 0 |
      | certifstatus_currentlyexpired     | 0 |
      | certifstatus_nevercertified       | 1 |
      | certifassignmentstatus_assigned   | 0 |
      | certifassignmentstatus_unassigned | 1 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User's certification status is 'Never certified' with assignment status 'Not assigned' for certification \"Certification 1\"" in the "Ruleset #1" "fieldset"

    # Check the results.
    When I press "Approve changes"
    And I follow "Members"
    Then I should see "Admin User"
    And I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should see "Learner3 Three"
    And I should see "Learner4 Four"
    And I should see "Learner5 Five"
