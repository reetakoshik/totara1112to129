@totara @totara_program @javascript
Feature: Program editing tool history
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
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    And I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[month]   | July   |
      | timecompleted[day]     | 30     |
      | timecompleted[year]    | 2029   |
      | timecompleted[hour]    | 12     |
      | timecompleted[minute]  | 10     |
    And I click on "Save changes" "button"

  @_alert
  Scenario: Test completion history date
    Given I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[month]   | August |
      | timecompleted[day]     | 22     |
      | timecompleted[year]    | 2015   |
      | timecompleted[hour]    | 5      |
      | timecompleted[minute]  | 30     |
    And I click on "Save changes" "button"
    And I should see "30 July 2029" in the "Completion history" "fieldset"
    And I should see "22 August 2015" in the "Completion history" "fieldset"

    # Test editing of a historical record
    When I click on "Edit" "link" in the "22 August 2015" "table_row"
    Then the following fields match these values:
      | timecompleted[month]   | August |
      | timecompleted[day]     | 22     |
      | timecompleted[year]    | 2015   |
      | timecompleted[hour]    | 5      |
      | timecompleted[minute]  | 30     |
    When I set the following fields to these values:
      | timecompleted[month]   | September |
      | timecompleted[day]     | 3         |
      | timecompleted[year]    | 2011      |
      | timecompleted[hour]    | 13        |
      | timecompleted[minute]  | 35        |
    And I click on "Save changes" "button"
    Then I should see "3 September 2011" in the "Completion history" "fieldset"
    And I should not see "22 August 2015" in the "Completion history" "fieldset"
    And I should see "30 July 2029" in the "Completion history" "fieldset"

    # And now a deletion
    When I click on "Delete" "link" in the "3 September 2011" "table_row" dismissing the dialogue
    Then I should see "30 July 2029" in the "Completion history" "fieldset"
    And I should see "3 September 2011" in the "Completion history" "fieldset"
    When I click on "Delete" "link" in the "3 September 2011" "table_row" confirming the dialogue
    Then I should see "30 July 2029" in the "Completion history" "fieldset"
    And I should not see "3 September 2011" in the "Completion history" "fieldset"
