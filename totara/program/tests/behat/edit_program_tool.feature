@totara @totara_program @javascript
Feature: Program editing tool
  In order to edit certification completions
  I need to have the tool enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "programs" exist in "totara_program" plugin:
      | fullname | shortname |
      | Prog 1   | filtest   |
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Prog 1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "fn_002 ln_002" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Completion" tab

  Scenario: Confirm that you can not save an invalid state
    Given I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "Status" to "Invalid - select a valid status"
    Then the "Save changes" "button" should be disabled

  Scenario: Confirm that the program incomplete is saved correctly
    Given I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "Status" to "Program complete"
    And I set the following fields to these values:
      | timedue[enabled]       | 1      |
      | timedue[month]         | August |
      | timedue[day]           | 22     |
      | timedue[year]          | 2030   |
      | timedue[hour]          | 15     |
      | timedue[minute]        | 05     |
      | timecompleted[month]   | July   |
      | timecompleted[day]     | 30     |
      | timecompleted[year]    | 2029   |
      | timecompleted[hour]    | 12     |
      | timecompleted[minute]  | 10     |
    And I click on "Save changes" "button"
    Then I should see "Due date: 22 August 2030" in the "Transactions" "fieldset"
    And I should see "Completion date: 30 July 2029" in the "Transactions" "fieldset"
    And the following fields match these values:
      | timedue[enabled]       | 1      |
      | timedue[month]         | August |
      | timedue[day]           | 22     |
      | timedue[year]          | 2030   |
      | timedue[hour]          | 15     |
      | timedue[minute]        | 05     |
      | timecompleted[month]   | July   |
      | timecompleted[day]     | 30     |
      | timecompleted[year]    | 2029   |
      | timecompleted[hour]    | 12     |
      | timecompleted[minute]  | 10     |
    When I follow "Return to program"
    Then I should see "Complete" in the "fn_001 ln_001" "table_row"
    And I should see "Not complete" in the "fn_002 ln_002" "table_row"

    When I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "Status" to "Program incomplete"
    And I set the following fields to these values:
      | timedue[enabled] | 1      |
      | timedue[month]   | August |
      | timedue[day]     | 22     |
      | timedue[year]    | 2030   |
      | timedue[hour]    | 15     |
      | timedue[minute]  | 05     |
    And I click on "Save changes" "button"
    Then I should see "Due date: 22 August 2030" in the "Transactions" "fieldset"
    And I should see "Completion date: Not set" in the "Transactions" "fieldset"
    And the following fields match these values:
      | timedue[enabled] | 1      |
      | timedue[month]   | August |
      | timedue[day]     | 22     |
      | timedue[year]    | 2030   |
      | timedue[hour]    | 15     |
      | timedue[minute]  | 05     |
    When I follow "Return to program"
    Then I should see "Complete" in the "fn_001 ln_001" "table_row"
    And I should see "Not complete" in the "fn_002 ln_002" "table_row"
