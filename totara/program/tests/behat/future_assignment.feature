@totara @totara_program
Feature: User assignments with due date base on first login
  In order to view a program
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
      | user004  | fn_004    | ln_004   | user004@example.com |
      | user005  | fn_005    | ln_005   | user005@example.com |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user    | cohort |
      | user002 | aud1   |
      | user003 | aud1   |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname  |
      | Assignment Program Tests | assigntest |

  @javascript
  Scenario: Test first login due date criteria assigned immediately
    Given I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"

    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should see "Not yet known"
    And I should not see "No due date"
    And I should not see "Program assignment changes have been deferred"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    Then I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should not see "Not yet known"
    And I should not see "No due date"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"

  @javascript
  Scenario: Test first login due date criteria assigned to user who is already assigned
    Given I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"

    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds

    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "View dates"
    And I should see "No due date"
    And I should not see "Not yet set"
    And I should not see "Program assignment changes have been deferred"

    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should see "Not yet known"
    And I should not see "No due date"
    And I should not see "Program assignment changes have been deferred"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    Then I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should not see "Not yet known"
    And I should not see "No due date"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"

  @javascript
  Scenario: Test first login due date criteria assigned to user who has already logged in
    And I log in as "user001"
    And I log out
    And I log in as "user002"
    And I log out
    And I log in as "user003"
    And I log out

    Given I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"

    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should not see "Not yet known"
    And I should not see "No due date"
    And I should not see "Program assignment changes have been deferred"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"

  @javascript
  Scenario: Test first login due date criteria assigned to user who has already logged in and is assigned
    And I log in as "user001"
    And I log out
    And I log in as "user002"
    And I log out
    And I log in as "user003"
    And I log out

    Given I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"

    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds

    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "View dates"
    And I should see "No due date"
    And I should not see "Not yet set"
    And I should not see "Program assignment changes have been deferred"

    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I should see "Complete within 4 Week(s) of First login"
    And I should see "View dates"
    And I should not see "Not yet known"
    And I should not see "No due date"
    And I should not see "Program assignment changes have been deferred"

    When I click on "Completion" "link"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then the field "Status" matches value "Program incomplete"

    When I click on "Return to program" "link"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then the field "Status" matches value "Program incomplete"
